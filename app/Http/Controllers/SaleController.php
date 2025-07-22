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
            ->paginate(15);

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
    // Validate the main sale data and the new items
    $this->validateSale($request, 'new_sale_items');

    DB::beginTransaction();
    try {
        // Generate bill number
        $lastSale = Sale::orderBy('id', 'desc')->first();
        $nextNumber = $lastSale ? ($lastSale->id + 1) : 1;
        $billNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Calculate totals for the new sale items (with extra discount handling)
        $totals = $this->calculateTotals($request->new_sale_items);

        // Create the Sale
        $sale = Sale::create([
            'customer_id' => $request->customer_id,
            'customer_name' => optional(Customer::find($request->customer_id))->name,
            'sale_date' => $request->sale_date,
            'bill_number' => $billNumber,
            'notes' => $request->notes,
            'total_amount' => $totals['total'],
            'total_gst_amount' => $totals['gst'],
        ]);

        // Save sale items and adjust inventory
        foreach ($request->new_sale_items as $itemData) {
            $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0); // MODIFIED: Cast to float
            $this->adjustInventory($itemData, -$totalQty);

            $sale->saleItems()->create($itemData);
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
    // Load both sale items and customer so the name is always available after validation errors
    $sale->load('saleItems.medicine', 'customer');
    $customers = Customer::all();

    return view('sales.create', compact('sale', 'customers'));
}

public function update(Request $request, Sale $sale): RedirectResponse
{
    // MODIFIED: Removed debugging try-catch block. Validation will now redirect on failure.
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'sale_date' => 'required|date',
        'notes' => 'nullable|string',
    ]);

    $this->validateSale($request, 'existing_sale_items', true);
    $this->validateSale($request, 'new_sale_items', true);

    $deletedItemIds = array_filter(explode(',', $request->input('deleted_items', '')));
    $remainingExistingItemsCount = $sale->saleItems()->whereNotIn('id', $deletedItemIds)->count();
    $newItemsCount = count($request->input('new_sale_items', []));

    if (($remainingExistingItemsCount + $newItemsCount) === 0) {
        // MODIFIED: Changed to redirect with errors for non-AJAX submission
        return back()->withErrors(['A sale must contain at least one item after update.'])->withInput();
    }

    try {
        DB::beginTransaction();

        $sale->update($request->only(['customer_id', 'sale_date', 'notes']) + [
            'customer_name' => Customer::find($request->customer_id)?->name ?? 'Unknown',
        ]);

        $this->handleDeletedItems((string) $request->input('deleted_items', ''));

        if ($request->has('existing_sale_items')) {
            $this->processSaleItems($request->existing_sale_items, $sale, true);
        }

        if ($request->has('new_sale_items')) {
            $this->processSaleItems($request->new_sale_items, $sale, false);
        }

        // Recalculate totals with extra discount handling
        $this->updateSaleTotals($sale);

        DB::commit();
        // MODIFIED: Changed to redirect for non-AJAX submission
        return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');
    } catch (\Throwable $e) {
        DB::rollBack();
        // MODIFIED: Changed to redirect with errors for non-AJAX submission
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
                $this->adjustInventory($item, (float)$item->quantity + (float)$item->free_quantity); // MODIFIED: Cast to float
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
                 $itemData['is_extra_discount_applied'] = isset($itemData['applied_extra_discount_percentage']) && $itemData['applied_extra_discount_percentage'] > 0 ? 1 : 0;
        $itemData['applied_extra_discount_percentage'] = $itemData['applied_extra_discount_percentage'] ?? 0;

            if ($isUpdate) {
                $item = SaleItem::findOrFail($itemData['id']);
                $originalTotalQty = (float)$item->quantity + (float)$item->free_quantity; // MODIFIED: Cast to float
                $newTotalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0); // MODIFIED: Cast to float
                $quantityDiff = $newTotalQty - $originalTotalQty;

                $this->adjustInventory($item, -$quantityDiff); // Negative diff for reduction, positive for increase
                $item->update($itemData);
            } else {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0); // MODIFIED: Cast to float
                $this->adjustInventory($itemData, -$totalQty); // Decrease inventory by total sold quantity
                $sale->saleItems()->create($itemData);
            }
        }
    }

    private function handleDeletedItems(string $deletedItemIds): void
    {
        // $deletedItemIds is guaranteed to be a string here due to the fix in update()
        $itemIds = array_filter(explode(',', $deletedItemIds)); // array_filter removes empty strings if $deletedItemIds was just ',' or ''
        foreach ($itemIds as $itemId) {
            if (empty($itemId)) continue; // Double check for robustness
            $item = SaleItem::find($itemId);
            if ($item) {
                $this->adjustInventory($item, (float)$item->quantity + (float)$item->free_quantity); // MODIFIED: Cast to float
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
    
    private function adjustInventory(array|SaleItem $item, float $adjustQty): void // MODIFIED: $adjustQty type hint to float
    {
        if ($adjustQty === 0.0) return; // MODIFIED: Use float comparison

        $medicineId = is_array($item) ? $item['medicine_id'] : $item->medicine_id;
        $batchNumber = is_array($item) ? $item['batch_number'] : $item->batch_number;

        $inventory = Inventory::where('medicine_id', $medicineId)
            ->where('batch_number', $batchNumber)
            ->first();

        if (!$inventory) {
            throw new \Exception("Inventory not found for medicine ID {$medicineId}, batch {$batchNumber}.");
        }

        // MODIFIED: Ensure comparisons and increments use float
        $currentInventoryQuantity = (float)($inventory->quantity ?? 0);
        
        if ($currentInventoryQuantity + $adjustQty < 0 && $adjustQty < 0) {
            throw new \Exception("Insufficient stock for medicine ID {$medicineId}, batch {$batchNumber}. Current stock: {$currentInventoryQuantity}. Attempted to reduce by: {$adjustQty}."); // Added more detail to error message
        }
        
        $inventory->quantity = $currentInventoryQuantity + $adjustQty; // Direct assignment for float
        $inventory->save();
    }
    
private function calculateTotals(iterable $items): array
{
    $subtotal = 0.0; // MODIFIED: Initialize as float
    $gst = 0.0; // MODIFIED: Initialize as float

    foreach ($items as $item) {
        $quantity = (float)($item['quantity'] ?? ($item->quantity ?? 0.0)); // MODIFIED: Cast to float
        $salePrice = (float)($item['sale_price'] ?? ($item->sale_price ?? 0.0)); // MODIFIED: Cast to float
        $discount = (float)($item['discount_percentage'] ?? ($item->discount_percentage ?? 0.0)); // MODIFIED: Cast to float
        $gstRate = (float)($item['gst_rate'] ?? ($item->gst_rate ?? 0.0)); // MODIFIED: Cast to float
        $extraDiscount = (float)($item['applied_extra_discount_percentage'] ?? ($item->applied_extra_discount_percentage ?? 0.0)); // MODIFIED: Cast to float

        $lineTotal = $quantity * $salePrice;

        // Apply normal discount
        $discountAmount = ($lineTotal * $discount) / 100;
        $afterDiscount = $lineTotal - $discountAmount;

        // Apply extra discount if present
        if ($extraDiscount > 0.0) { // MODIFIED: Use float comparison
            $afterDiscount -= ($afterDiscount * $extraDiscount) / 100;
        }

        // Calculate GST on the discounted price
        $gstAmount = ($afterDiscount * $gstRate) / 100;

        $subtotal += $afterDiscount;
        $gst += $gstAmount;
    }

    return [
        'total' => round($subtotal + $gst, 2),
        'gst' => round($gst, 2),
    ];
}


    
private function generateBillNumber(): string
{
    do {
        $latestId = Sale::withTrashed()->max('id') ?? 0;
        $billNumber = 'CASH-' . str_pad($latestId + 1, 5, '0', STR_PAD_LEFT);
    } while (
        Sale::withTrashed()->where('bill_number', $billNumber)->exists()
    );

    return $billNumber;
}


    private function validateSale(Request $request, string $key, bool $allowMinZeroItems = false): void
    {
        // Ensure this part is correct
        if (!$request->has($key)) {
            // Merge an empty array if the key is not present, so subsequent rules don't fail for missing input
            $request->merge([$key => []]);
        }

        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            "$key" => 'array', // Just ensure it's an array if present
            "$key.*.medicine_id" => 'required|exists:medicines,id',
            "$key.*.batch_number" => 'required|string',
            "$key.*.quantity" => 'required|numeric|min:0.01', // MODIFIED: Changed from integer to numeric and min:1 to min:0.01
            "$key.*.free_quantity" => 'nullable|numeric|min:0', // MODIFIED: Changed from integer to numeric
            "$key.*.sale_price" => 'required|numeric|min:0',
            "$key.*.gst_rate" => 'nullable|numeric|min:0',
            "$key.*.discount_percentage" => 'nullable|numeric|min:0|max:100',
        ];

        // Conditionally add the 'min:1' rule if not allowing zero items (e.g., for new sales)
        if (!$allowMinZeroItems) {
            $rules["$key"] .= '|min:1'; // This min:1 applies to the array itself, not the items' quantity
        }

        // Apply validation
        $request->validate($rules);
    }
}