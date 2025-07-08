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
                                ->paginate(10); // 10 bills per page

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
            $purchaseBillData = $request->except('purchase_items', 'subtotal_amount');
            $purchaseBill = PurchaseBill::create($purchaseBillData);


            foreach ($request->purchase_items as $item) {
                $purchaseBill->purchaseBillItems()->create($item);
                $this->updateInventory($item);
            }

            DB::commit();
            return redirect()->route('purchase_bills.index')->with('success', 'Purchase bill created and inventory updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            // Add medicine text to the old input for repopulation on the frontend
            if ($request->has('purchase_items')) {
                foreach ($request->purchase_items as $i => $item) {
                    $medicine = Medicine::find($item['medicine_id']);
                    if ($medicine) {
                        $request->merge([
                            "purchase_items.$i.medicine_text" => $medicine->name . ' (' . ($medicine->company_name ?? 'Generic') . ')'
                        ]);
                    }
                }
            }
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
        $this->validatePurchaseBill($request, $purchaseBill);

        DB::beginTransaction();

        try {
            $purchaseBillData = $request->except(['existing_purchase_items', 'new_purchase_items', 'subtotal_amount']);
            $purchaseBill->update($purchaseBillData);

            if ($request->has('existing_purchase_items')) {
                $this->validateItems($request, 'existing_items');

                foreach ($request->existing_purchase_items as $itemId => $itemData) {
                    $item = PurchaseBillItem::findOrFail($itemData['id']);
                    
                    $originalQuantity = $item->quantity;
                    $newQuantity = $itemData['quantity'];
                    $quantityDifference = $newQuantity - $originalQuantity;

                    if ($quantityDifference != 0) {
                        $this->adjustInventoryOnUpdate($item->medicine_id, $item->batch_number, $item->expiry_date, $quantityDifference);
                    }

                    $item->update(Arr::except($itemData, ['id']));
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
            'bill_date' => 'required|date',
            'bill_number' => ['required', 'string', $billNumberRule],
            'status' => 'nullable|in:Pending,Received,Cancelled',
            'notes' => 'nullable|string',
        ], [
            'bill_number.unique' => 'This bill number already exists for the selected supplier.'
        ]);
    }

    private function validateItems(Request $request, string $key): void
    {
        $request->validate([
            "$key" => 'required|array|min:1',
            "$key.*.medicine_id" => 'required|exists:medicines,id',
            "$key.*.batch_number" => 'required|string',
            "$key.*.expiry_date" => 'nullable|date|after_or_equal:today',
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
        $inventory = Inventory::firstOrNew([
            'medicine_id' => $item['medicine_id'],
            'batch_number' => $item['batch_number'],
            'expiry_date' => $item['expiry_date'],
        ]);

        $inventory->quantity = ($inventory->quantity ?? 0) + $item['quantity'];
        $inventory->save();
    }
    
    private function adjustInventoryOnUpdate(int $medicineId, ?string $batchNumber, ?string $expiryDate, int $quantityDifference): void
    {
        if (!$batchNumber) return;

        $inventory = Inventory::where('medicine_id', $medicineId)
            ->where('batch_number', $batchNumber)
            ->where('expiry_date', $expiryDate)
            ->first();

        if ($inventory) {
            $inventory->increment('quantity', $quantityDifference);
        }
    }
}