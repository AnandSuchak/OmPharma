<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\PurchaseBill;
use App\Models\Supplier;
use App\Models\Medicine;
use App\Models\PurchaseBillItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseBillController extends Controller
{
    public function index()
    {
        $purchaseBills = PurchaseBill::with('supplier')
            ->withoutTrashed()
            ->orderByDesc('id')
            ->paginate(10);

        return view('purchase_bills.index', compact('purchaseBills'));
    }

    public function create(): View
    {
        return view('purchase_bills.create', [
            'suppliers' => Supplier::all(),
            'medicines' => Medicine::all()
        ]);
    }

public function store(Request $request): RedirectResponse
{
    $this->validatePurchaseBill($request);
    $this->validateItems($request, 'purchase_items');

    DB::beginTransaction();

    try {
        $items = $request->purchase_items;

        foreach ($items as &$itemData) {
            $itemData['free_quantity'] = $itemData['free_quantity'] ?? 0;
        }
        unset($itemData);

        // Calculate totals
        $subtotal = 0;
        $totalGst = 0;
        foreach ($items as $itemData) {
            $itemSubtotal = ($itemData['quantity'] * $itemData['purchase_price']) * (1 - (($itemData['our_discount_percentage'] ?? 0) / 100));
            $itemGst = $itemSubtotal * (($itemData['gst_rate'] ?? 0) / 100);
            $subtotal += $itemSubtotal;
            $totalGst += $itemGst;
        }
$extraDiscount = floatval($request->input('extra_discount_amount', 0));
$subtotal = max($subtotal - $extraDiscount, 0);

$billData = $request->except('purchase_items');
$billData['extra_discount_amount'] = $extraDiscount;
$billData['total_gst_amount'] = $totalGst;
$billData['total_amount'] = $subtotal + $totalGst;

        $purchaseBill = PurchaseBill::create($billData);

        foreach ($items as $itemData) {
            $purchaseBill->purchaseBillItems()->create($itemData);
            $this->adjustInventory(
                $itemData['medicine_id'],
                $itemData['batch_number'],
                $itemData['expiry_date'],
                $itemData['quantity'],
                $itemData['free_quantity']
            );
        }

        DB::commit();
        return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill created and inventory updated.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
    }
}


    public function show(PurchaseBill $purchaseBill): View
    {
        $purchaseBill->load('supplier', 'purchaseBillItems.medicine');
        return view('purchase_bills.show', compact('purchaseBill'));
    }

    public function edit(PurchaseBill $purchaseBill): View
    {
        $purchaseBill->load('purchaseBillItems.medicine');
        return view('purchase_bills.edit', [
            'purchaseBill' => $purchaseBill,
            'suppliers'    => Supplier::all(),
            'medicines'    => Medicine::all()
        ]);
    }

public function update(Request $request, PurchaseBill $purchaseBill): RedirectResponse
{
    $this->validatePurchaseBill($request, $purchaseBill);

    $existingItems = $request->input('existing_items', []);
    $newItems = $request->input('new_purchase_items', []);

    if (!empty($existingItems)) {
        $this->validateItems($request, 'existing_items');
    }
    if (!empty($newItems)) {
        $this->validateItems($request, 'new_purchase_items');
    }

    DB::beginTransaction();

    try {
        // Rollback inventory for original items
        $originalItems = $purchaseBill->purchaseBillItems()->get();
        foreach ($originalItems as $item) {
            $this->adjustInventory($item->medicine_id, $item->batch_number, $item->expiry_date, -$item->quantity, -$item->free_quantity);
        }

        // Delete removed items
        $existingItemIds = Arr::pluck($existingItems, 'id');
        foreach ($originalItems as $item) {
            if (!in_array($item->id, $existingItemIds)) {
                $item->delete();
            }
        }

        // Update existing items
        foreach ($existingItems as &$itemData) {
            $itemData['free_quantity'] = $itemData['free_quantity'] ?? 0;
            $itemToUpdate = PurchaseBillItem::find($itemData['id']);
            if ($itemToUpdate) {
                $itemToUpdate->update(Arr::except($itemData, 'id'));
                $this->adjustInventory($itemData['medicine_id'], $itemData['batch_number'], $itemData['expiry_date'], $itemData['quantity'], $itemData['free_quantity']);
            }
        }
        unset($itemData);

        // Add new items
        foreach ($newItems as &$itemData) {
            $itemData['free_quantity'] = $itemData['free_quantity'] ?? 0;
            $newItem = $purchaseBill->purchaseBillItems()->create($itemData);
            $this->adjustInventory($newItem->medicine_id, $newItem->batch_number, $newItem->expiry_date, $newItem->quantity, $newItem->free_quantity);
        }
        unset($itemData);

        // Combine all items for total calculation
        $allItemsData = array_merge(array_values($existingItems), $newItems);

        $subtotal = 0;
        $totalGst = 0;
        foreach ($allItemsData as $itemData) {
            $itemSubtotal = ($itemData['quantity'] * $itemData['purchase_price']) * (1 - (($itemData['our_discount_percentage'] ?? 0) / 100));
            $itemGst = $itemSubtotal * (($itemData['gst_rate'] ?? 0) / 100);
            $subtotal += $itemSubtotal;
            $totalGst += $itemGst;
        }
$extraDiscount = floatval($request->input('extra_discount_amount', 0));
$subtotal = max($subtotal - $extraDiscount, 0);

$billData = $request->except(['existing_items', 'new_purchase_items', '_token', '_method']);
$billData['extra_discount_amount'] = $extraDiscount;
$billData['total_gst_amount'] = $totalGst;
$billData['total_amount'] = $subtotal + $totalGst;

        $purchaseBill->update($billData);

        DB::commit();
        return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill updated successfully.');

    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withInput()->withErrors(['error' => 'Update error: ' . $e->getMessage()]);
    }
}

    public function destroy(PurchaseBill $purchaseBill): RedirectResponse
    {
        DB::beginTransaction();
        try {
            foreach ($purchaseBill->purchaseBillItems as $item) {
                $this->adjustInventory($item->medicine_id, $item->batch_number, $item->expiry_date, -$item->quantity, -$item->free_quantity);
            }

            $purchaseBill->delete();

            DB::commit();
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill deleted and inventory adjusted.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Deletion error: ' . $e->getMessage()]);
        }
    }

    private function validatePurchaseBill(Request $request, PurchaseBill $purchaseBill = null): void
    {
        $billNumberRule = Rule::unique('purchase_bills')->where(function ($query) use ($request) {
            return $query->where('supplier_id', $request->supplier_id);
        });

        if ($purchaseBill) {
            $billNumberRule->ignore($purchaseBill->id);
        }

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'bill_date'   => 'required|date',
            'bill_number' => ['required', 'string', $billNumberRule],
            'status'      => 'nullable|in:Pending,Received,Cancelled',
            'notes'       => 'nullable|string',
        ], [
            'bill_number.unique' => 'This bill number already exists for the selected supplier.'
        ]);
    }

private function validateItems(Request $request, string $key): void
{
    $rules = [
        "$key"                       => 'required|array|min:1',
        "$key.*.medicine_id"        => 'required|exists:medicines,id',
        "$key.*.batch_number"       => 'nullable|string|max:255',
        "$key.*.expiry_date"        => ['nullable', 'date'],
        "$key.*.quantity"           => 'required|integer|min:1',
        "$key.*.free_quantity"      => 'nullable|integer|min:0',
        "$key.*.purchase_price"     => 'required|numeric|min:0',
        "$key.*.ptr"                => 'nullable|numeric|min:0',
        "$key.*.sale_price"         => 'required|numeric|min:0',
        "$key.*.gst_rate"           => 'nullable|numeric|min:0|max:100',
        "$key.*.discount_percentage"=> 'nullable|numeric|min:0|max:100',
        "$key.*.our_discount_percentage" => 'nullable|numeric|min:0|max:100',
    ];

    // ✅ Only apply "after_or_equal:today" to NEW items
    if ($key === 'new_purchase_items') {
        $rules["$key.*.expiry_date"][] = 'after_or_equal:today';
    }

    // For existing, also validate ID
    if ($key === 'existing_items') {
        $rules["$key.*.id"] = 'required|exists:purchase_bill_items,id';
    }

    $request->validate($rules, [
        "$key.required" => 'You must add at least one item to the bill.'
    ]);
}

    private function adjustInventory(?int $medicineId, ?string $batchNumber, ?string $expiryDate, int $paidQuantity, int $freeQuantity = 0): void
    {
        if (!$medicineId) {
            return;
        }

        // Total change is the sum of paid and free items
        $totalQuantityChange = $paidQuantity + $freeQuantity;

        if ($totalQuantityChange == 0) {
            return;
        }

        $inventory = Inventory::firstOrNew([
            'medicine_id'  => $medicineId,
            'batch_number' => $batchNumber,
            'expiry_date'  => $expiryDate,
        ]);

        $inventory->quantity = ($inventory->quantity ?? 0) + $totalQuantityChange;
        $inventory->save();
    }
}