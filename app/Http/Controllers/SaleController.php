<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    public function index(): View
    {
        $sales = Sale::with('customer')
            ->latest()
            ->paginate(10);

        return view('sales.index', compact('sales'));
    }

    public function create(): View
    {
        return view('sales.create', [
            'customers' => Customer::all(),
        ]);
    }

public function store(Request $request)
{
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'sale_date' => 'required|date',
        'new_sale_items' => 'required|array|min:1',
    ]);

    DB::beginTransaction();
    try {
        // ✅ Generate bill number (custom logic, adjust as needed)
        $lastSale = Sale::orderBy('id', 'desc')->first();
        $nextNumber = $lastSale ? ($lastSale->id + 1) : 1;
        $billNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // ✅ Create the Sale
        $sale = Sale::create([
            'customer_id' => $request->customer_id,
            'customer_name' => optional(Customer::find($request->customer_id))->name,
            'sale_date' => $request->sale_date,
            'bill_number' => $billNumber, // ✅ Required field
            'notes' => $request->notes,
        ]);

        foreach ($request->new_sale_items as $item) {
            $sale->saleItems()->create($item);
        }

        DB::commit();
        return redirect()->route('sales.index')->with('success', 'Sale created successfully.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['Sale creation failed: ' . $e->getMessage()])->withInput();
    }
}


    public function print($id)
{
    $sale = Sale::with(['customer', 'saleItems.medicine'])->findOrFail($id);
    return view('sales.bill', compact('sale'));
}

    public function show(Sale $sale): View
    {
        $sale->load('saleItems.medicine', 'customer');
        return view('sales.show', compact('sale'));
    }

    public function edit(Sale $sale): View
    {
        $sale->load('saleItems.medicine');
        $customers = Customer::all();
        return view('sales.create', compact('sale', 'customers'));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $this->validateSale($request, 'existing_sale_items');
        $this->validateSale($request, 'new_sale_items');

        try {
            DB::beginTransaction();

            $sale->update($request->only(['customer_id', 'sale_date', 'notes']) + [
                'customer_name' => Customer::find($request->customer_id)?->name ?? 'Unknown',
            ]);
            
            if ($request->has('deleted_items')) {
                $this->handleDeletedItems($request->deleted_items);
            }

            if ($request->has('existing_sale_items')) {
                $this->processSaleItems($request->existing_sale_items, $sale, true);
            }

            if ($request->has('new_sale_items')) {
                $this->processSaleItems($request->new_sale_items, $sale, false);
            }
            
            $this->updateSaleTotals($sale);

            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Sale update failed: ' . $e->getMessage()]);
        }
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $sale->load('saleItems');

            foreach ($sale->saleItems as $item) {
                // Adjust inventory back when a sale is deleted
                $this->adjustInventory($item, $item->quantity + $item->free_quantity);
            }

            $sale->delete();
            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale deleted and inventory restored.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }
    
    // --- API / Helper Functions ---

    // REMOVED: public function getBatchesForMedicine($medicineId)
    // This method is now handled by MedicineController@getBatches for Sales Bill

    public function printPdf(Sale $sale)
    {
        $sale->load('saleItems.medicine', 'customer');
        $pdf = PDF::loadView('sales.bill', compact('sale'))->setPaper('a5', 'landscape');
        return $pdf->stream('invoice-' . $sale->bill_number . '.pdf');
    }
    
    // --- Private Functions ---

    private function processSaleItems(array $items, Sale $sale, bool $isUpdate = false): void
    {
        foreach ($items as $itemData) {
            if ($isUpdate) {
                $item = SaleItem::findOrFail($itemData['id']);
                $originalTotalQty = $item->quantity + $item->free_quantity;
                $newTotalQty = ($itemData['quantity'] ?? 0) + ($itemData['free_quantity'] ?? 0);
                $quantityDiff = $newTotalQty - $originalTotalQty;

                $this->adjustInventory($item, -$quantityDiff); // Negative diff for reduction, positive for increase
                $item->update($itemData);
            } else {
                $totalQty = ($itemData['quantity'] ?? 0) + ($itemData['free_quantity'] ?? 0);
                $this->adjustInventory($itemData, -$totalQty); // Decrease inventory by total sold quantity
                $sale->saleItems()->create($itemData);
            }
        }
    }

    private function handleDeletedItems(string $deletedItemIds): void
    {
        $itemIds = explode(',', $deletedItemIds);
        foreach ($itemIds as $itemId) {
            if (empty($itemId)) continue;
            $item = SaleItem::find($itemId);
            if ($item) {
                $this->adjustInventory($item, $item->quantity + $item->free_quantity); // Restore inventory
                $item->delete();
            }
        }
    }

    private function updateSaleTotals(Sale $sale): void
    {
        $sale->load('saleItems');
        $totals = $this->calculateTotals($sale->saleItems);
        $sale->update([
            'total_amount' => $totals['total'],
            'total_gst_amount' => $totals['gst'],
        ]);
    }
    
    private function adjustInventory(array|SaleItem $item, int $adjustQty): void
    {
        if ($adjustQty === 0) return;

        $medicineId = is_array($item) ? $item['medicine_id'] : $item->medicine_id;
        $batchNumber = is_array($item) ? $item['batch_number'] : $item->batch_number;

        $inventory = Inventory::where('medicine_id', $medicineId)
            ->where('batch_number', $batchNumber)
            ->first();

        if (!$inventory) {
            // This should ideally not happen if inventory is strictly managed,
            // but for robustness, create if not found (though a sale implies it exists).
            // For sales, if inventory isn't found, it's usually an error.
            throw new \Exception("Inventory not found for medicine ID {$medicineId}, batch {$batchNumber}.");
        }

        // Prevent negative stock unless $adjustQty is positive (restoring)
        if ($inventory->quantity + $adjustQty < 0 && $adjustQty < 0) {
            throw new \Exception("Insufficient stock for medicine ID {$medicineId}, batch {$batchNumber}.");
        }
        
        $inventory->increment('quantity', $adjustQty);
    }
    
    private function calculateTotals(iterable $items): array
    {
        $subtotal = 0;
        $gst = 0;

        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? ($item->quantity ?? 0); // Handle both array and object
            $salePrice = $item['sale_price'] ?? ($item->sale_price ?? 0);
            $discount = $item['discount_percentage'] ?? ($item->discount_percentage ?? 0);
            $gstRate = $item['gst_rate'] ?? ($item->gst_rate ?? 0);

            $lineTotal = $quantity * $salePrice;
            $lineAfterDiscount = $lineTotal - ($lineTotal * $discount / 100);
            $gstAmount = ($lineAfterDiscount * $gstRate) / 100;

            $subtotal += $lineAfterDiscount;
            $gst += $gstAmount;
        }

        return [
            'total' => round($subtotal + $gst, 2),
            'gst' => round($gst, 2),
        ];
    }
    
    private function generateBillNumber(): string
    {
        $latestSaleId = Sale::latest('id')->value('id') ?? 0;
        return 'CASH-' . str_pad($latestSaleId + 1, 4, '0', STR_PAD_LEFT);
    }

 private function validateSale(Request $request, string $key = 'sale_items'): void
    {
        // Ensure this part is correct
        if (!$request->has($key)) {
            // If the key is not present, we need to ensure validation fails
            // Adding a rule that requires the key will handle this.
            $request->merge([$key => []]); // Merge an empty array to prevent 'null' if missing entirely
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            "$key" => 'required|array|min:1', // This rule should catch it if it's empty or null
            "$key.*.medicine_id" => 'required|exists:medicines,id',
            "$key.*.batch_number" => 'required|string',
            "$key.*.quantity" => 'required|integer|min:1',
            "$key.*.free_quantity" => 'nullable|integer|min:0',
            "$key.*.sale_price" => 'required|numeric|min:0',
            "$key.*.gst_rate" => 'nullable|numeric|min:0',
            "$key.*.discount_percentage" => 'nullable|numeric|min:0|max:100',
        ]);
    }
}