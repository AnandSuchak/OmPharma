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

class PurchaseBillController extends Controller
{
    public function index(): View
    {
        $purchaseBills = PurchaseBill::with('supplier')->latest()->get();
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
            $purchaseBill = PurchaseBill::create($request->except('purchase_items'));

            foreach ($request->purchase_items as $item) {
                $purchaseBill->purchaseBillItems()->create($item);
                $this->updateInventory($item);
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
            'suppliers' => Supplier::all(),
            'medicines' => Medicine::all()
        ]);
    }

    public function update(Request $request, PurchaseBill $purchaseBill): RedirectResponse
    {
        $this->validatePurchaseBill($request);

        DB::beginTransaction();

        try {
            $purchaseBill->update($request->except(['existing_purchase_items', 'new_purchase_items']));

            if ($request->has('existing_purchase_items')) {
                $this->validateItems($request, 'existing_purchase_items');

                foreach ($request->existing_purchase_items as $itemData) {
                    $item = PurchaseBillItem::findOrFail($itemData['id']);
                    $item->update(Arr::except($itemData, ['id']));
                    // Optional: handle inventory adjustment if quantity changes
                }
            }

            if ($request->has('new_purchase_items')) {
                $this->validateItems($request, 'new_purchase_items');

                foreach ($request->new_purchase_items as $item) {
                    $purchaseBill->purchaseBillItems()->create($item);
                    $this->updateInventory($item);
                }
            }

            DB::commit();
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill updated.');
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
                $inventory = Inventory::where('medicine_id', $item->medicine_id)
                    ->where('batch_number', $item->batch_number)
                    ->where('expiry_date', $item->expiry_date)
                    ->first();

                if ($inventory) {
                    $inventory->decrement('quantity', $item->quantity);
                    // Optionally delete if zero
                    // if ($inventory->quantity <= 0) $inventory->delete();
                }
            }

            $purchaseBill->delete();

            DB::commit();
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill deleted and inventory adjusted.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Deletion error: ' . $e->getMessage()]);
        }
    }

    // -------------------------------
    // ✅ PRIVATE HELPERS
    // -------------------------------

    private function validatePurchaseBill(Request $request): void
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'bill_date' => 'required|date',
            'bill_number' => 'required|string',
            'status' => 'nullable|in:Pending,Received,Cancelled',
            'total_amount' => 'nullable|numeric|min:0',
            'total_gst_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    }

    private function validateItems(Request $request, string $key): void
    {
        $request->validate([
            "$key" => 'required|array|min:1',
            "$key.*.medicine_id" => 'required|exists:medicines,id',
            "$key.*.batch_number" => 'required|string',
            "$key.*.expiry_date" => 'required|date|after_or_equal:today',
            "$key.*.quantity" => 'required|integer|min:1',
            "$key.*.purchase_price" => 'required|numeric|min:0',
            "$key.*.ptr" => 'nullable|numeric|min:0',
            "$key.*.sale_price" => 'required|numeric|min:0',
            "$key.*.gst_rate" => 'nullable|numeric|min:0|max:100',
            "$key.*.discount_percentage" => 'nullable|numeric|min:0|max:100',
        ]);
    }

    private function updateInventory(array $item): void
    {
        $inventory = Inventory::where('medicine_id', $item['medicine_id'])
            ->where('batch_number', $item['batch_number'])
            ->where('expiry_date', $item['expiry_date'])
            ->first();

        if ($inventory) {
            $inventory->increment('quantity', $item['quantity']);
        } else {
            Inventory::create([
                'medicine_id' => $item['medicine_id'],
                'batch_number' => $item['batch_number'],
                'expiry_date' => $item['expiry_date'],
                'quantity' => $item['quantity'],
                'sale_price' => $item['sale_price'],
                'ptr' => $item['ptr'] ?? null,
            ]);
        }
    }
}
