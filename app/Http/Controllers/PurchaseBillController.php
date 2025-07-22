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
 public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $query = PurchaseBill::with('supplier')
            ->withoutTrashed()
            ->orderByDesc('id');

        // NEW: Handle search query from AJAX
        if ($request->ajax() && $request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('bill_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('supplier', function($sq) use ($searchTerm) {
                      $sq->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        $purchaseBills = $query->paginate(15);

        if ($request->ajax()) {
            // Return JSON response for AJAX requests
            return response()->json([
                'html' => view('purchase_bills.partials.purchase_bill_table_rows', compact('purchaseBills'))->render(),
                'pagination' => $purchaseBills->links()->toHtml()
            ]);
        }

        // Standard view for initial page load
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
            // MODIFIED: Ensure quantities and prices are explicitly cast to float from request input
            $itemData['free_quantity'] = (float)($itemData['free_quantity'] ?? 0.0);
            $itemData['quantity'] = (float)($itemData['quantity'] ?? 0.0);
            $itemData['purchase_price'] = (float)($itemData['purchase_price'] ?? 0.0);
            $itemData['our_discount_percentage'] = (float)($itemData['our_discount_percentage'] ?? 0.0);
            $itemData['gst_rate'] = (float)($itemData['gst_rate'] ?? 0.0);
        }
        unset($itemData); // Unset reference after loop

        // Calculate totals (subtotal and totalGst are initial calculated values before rounding)
        $subtotal = 0.0; // MODIFIED: Initialize as float
        $totalGst = 0.0; // MODIFIED: Initialize as float
        foreach ($items as $itemData) {
            $itemBase = $itemData['quantity'] * $itemData['purchase_price'];
            $itemAfterDiscount = $itemBase * (1 - ($itemData['our_discount_percentage'] / 100));
            $itemGst = $itemAfterDiscount * ($itemData['gst_rate'] / 100);

            $subtotal += $itemAfterDiscount;
            $totalGst += $itemGst;
        }

        $extraDiscount = (float)($request->input('extra_discount_amount', 0.0)); // MODIFIED: Use 0.0
        $subtotal = max($subtotal - $extraDiscount, 0.0); // MODIFIED: Use 0.0

        $calculatedGrandTotal = $subtotal + $totalGst;

        // MODIFIED: Rounding Off Logic for storage
        $roundedGrandTotal = round($calculatedGrandTotal); // Round to nearest whole number
        $roundingOffAmount = $roundedGrandTotal - $calculatedGrandTotal; // Calculate the difference

        $billData = $request->except('purchase_items');
        $billData['extra_discount_amount'] = $extraDiscount;
        $billData['total_gst_amount'] = round($totalGst, 2); // MODIFIED: Round GST for storage
        $billData['total_amount'] = $roundedGrandTotal; // MODIFIED: Store the rounded total
        $billData['rounding_off_amount'] = round($roundingOffAmount, 2); // MODIFIED: Store the rounding off amount

        $purchaseBill = PurchaseBill::create($billData);

        foreach ($items as $itemData) {
            $purchaseBill->purchaseBillItems()->create($itemData);
            $this->adjustInventory(
                $itemData['medicine_id'],
                $itemData['batch_number'],
                $itemData['expiry_date'],
                $itemData['quantity'], // Now correctly float from above loop
                $itemData['free_quantity'] // Now correctly float from above loop
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
            // MODIFIED: Ensure float casts for quantities
            $this->adjustInventory($item->medicine_id, $item->batch_number, $item->expiry_date, -(float)$item->quantity, -(float)$item->free_quantity);
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
            // MODIFIED: Ensure quantities and prices are explicitly cast to float from request input
            $itemData['free_quantity'] = (float)($itemData['free_quantity'] ?? 0.0);
            $itemData['quantity'] = (float)($itemData['quantity'] ?? 0.0);
            $itemData['purchase_price'] = (float)($itemData['purchase_price'] ?? 0.0);
            $itemData['our_discount_percentage'] = (float)($itemData['our_discount_percentage'] ?? 0.0);
            $itemData['gst_rate'] = (float)($itemData['gst_rate'] ?? 0.0);

            $itemToUpdate = PurchaseBillItem::find($itemData['id']);
            if ($itemToUpdate) {
                $itemToUpdate->update(Arr::except($itemData, 'id'));
                $this->adjustInventory($itemData['medicine_id'], $itemData['batch_number'], $itemData['expiry_date'], $itemData['quantity'], $itemData['free_quantity']);
            }
        }
        unset($itemData);

        // Add new items
        foreach ($newItems as &$itemData) {
            // MODIFIED: Ensure quantities and prices are explicitly cast to float from request input
            $itemData['free_quantity'] = (float)($itemData['free_quantity'] ?? 0.0);
            $itemData['quantity'] = (float)($itemData['quantity'] ?? 0.0);
            $itemData['purchase_price'] = (float)($itemData['purchase_price'] ?? 0.0);
            $itemData['our_discount_percentage'] = (float)($itemData['our_discount_percentage'] ?? 0.0);
            $itemData['gst_rate'] = (float)($itemData['gst_rate'] ?? 0.0);

            $newItem = $purchaseBill->purchaseBillItems()->create($itemData);
            $this->adjustInventory($newItem->medicine_id, $newItem->batch_number, $newItem->expiry_date, $newItem->quantity, $newItem->free_quantity);
        }
        unset($itemData);

        // Combine all items for total calculation
        $allItemsData = array_merge(array_values($existingItems), $newItems);

        $subtotal = 0.0; // MODIFIED: Initialize as float
        $totalGst = 0.0; // MODIFIED: Initialize as float
        foreach ($allItemsData as $itemData) {
            $itemBase = $itemData['quantity'] * $itemData['purchase_price'];
            $itemAfterDiscount = $itemBase * (1 - ($itemData['our_discount_percentage'] / 100));
            $itemGst = $itemAfterDiscount * ($itemData['gst_rate'] / 100);

            $subtotal += $itemAfterDiscount;
            $totalGst += $itemGst;
        }
        $extraDiscount = (float)($request->input('extra_discount_amount', 0.0)); // MODIFIED: Use 0.0
        $subtotal = max($subtotal - $extraDiscount, 0.0); // MODIFIED: Use 0.0

        $calculatedGrandTotal = $subtotal + $totalGst;

        // MODIFIED: Rounding Off Logic for storage
        $roundedGrandTotal = round($calculatedGrandTotal); // Round to nearest whole number
        $roundingOffAmount = $roundedGrandTotal - $calculatedGrandTotal; // Calculate the difference

        $billData = $request->except(['existing_items', 'new_purchase_items', '_token', '_method']);
        $billData['extra_discount_amount'] = $extraDiscount;
        $billData['total_gst_amount'] = round($totalGst, 2); // MODIFIED: Round GST for storage
        $billData['total_amount'] = $roundedGrandTotal; // MODIFIED: Store the rounded total
        $billData['rounding_off_amount'] = round($roundingOffAmount, 2); // MODIFIED: Store the rounding off amount

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
                $this->adjustInventory($item->medicine_id, $item->batch_number, $item->expiry_date, -(float)$item->quantity, -(float)$item->free_quantity);
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
        "$key.*.quantity"           => 'nullable|numeric|min:0', // MODIFIED: Changed from integer to numeric, and min:1 to min:0
        "$key.*.free_quantity"      => 'nullable|numeric|min:0',
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

   private function adjustInventory(?int $medicineId, ?string $batchNumber, ?string $expiryDate, float $paidQuantity, float $freeQuantity = 0.0): void // MODIFIED: Type hints to float
{
    if (!$medicineId) {
        return;
    }

    // Total change is the sum of paid and free items
    $totalQuantityChange = $paidQuantity + $freeQuantity; // Already float due to type hints

    if ($totalQuantityChange == 0.0) { // MODIFIED: Use float comparison
        return;
    }

    $inventory = Inventory::firstOrNew([
        'medicine_id'  => $medicineId,
        'batch_number' => $batchNumber,
        'expiry_date'  => $expiryDate,
    ]);

    // Ensure inventory->quantity is also treated as float/decimal
    $inventory->quantity = (float)($inventory->quantity ?? 0.0) + $totalQuantityChange; // MODIFIED: Use 0.0 for consistency
    $inventory->save();
}
}