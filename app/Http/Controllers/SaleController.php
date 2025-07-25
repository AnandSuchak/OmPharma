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
use Illuminate\Validation\ValidationException; // Required for custom validation exceptions

class SaleController extends Controller
{
    /**
     * Display a listing of the sales.
     */
    public function index(): View
    {
        $sales = Sale::with('customer')
            ->latest()
            ->paginate(15);

        return view('sales.index', compact('sales'));
    }

    /**
     * Show the form for creating a new sale.
     */
    public function create(): View
    {
        return view('sales.create', [
            'customers' => Customer::all(),
            'sale' => null,
        ]);
    }

    /**
     * Store a newly created sale in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the main sale data and the new items
        $this->validateSale($request, 'new_sale_items');

        DB::beginTransaction();
        try {
            // Generate bill number using the dedicated private method
            $billNumber = $this->generateBillNumber();

            // Calculate totals for the new sale items
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
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);

                // Normalize applied_extra_discount_percentage before saving
                $itemData['applied_extra_discount_percentage'] = $this->normalizeAppliedExtraDiscount($itemData);
                $itemData['is_extra_discount_applied'] = $itemData['applied_extra_discount_percentage'] > 0 ? 1 : 0;

                // Adjust inventory (reduce stock for sold items)
                $this->adjustInventory($itemData, -$totalQty);

                // Create the SaleItem
                $sale->saleItems()->create($itemData);
            }

            DB::commit();
            return redirect()->route('sales.index')->with('success', 'Sale created successfully.');
        } catch (ValidationException $e) { // Catch ValidationException specifically for controlled errors
            DB::rollBack();
            return back()->withInput()->withErrors($e->errors()); // Pass validation errors back
        } catch (\Exception $e) { // Catch other general exceptions
            DB::rollBack();
            return back()->withErrors(['Sale creation failed: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Print the sale bill.
     */
    public function print($id): View
    {
        $sale = Sale::with(['customer', 'saleItems.medicine'])->findOrFail($id);
        return view('sales.bill', compact('sale'));
    }

    /**
     * Display the specified sale.
     */
    public function show(Sale $sale): View
    {
        $sale->load('saleItems.medicine', 'customer');
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified sale.
     */
    public function edit(Sale $sale): View
{
    $sale->load('saleItems.medicine', 'customer');
    $customers = Customer::all();

    return view('sales.create', [
        'sale' => $sale,   // make sure this line exists
        'customers' => $customers
    ]);
}

    /**
     * Update the specified sale in storage.
     */
   public function update(Request $request, Sale $sale): RedirectResponse
{
    \Log::info('SALE UPDATE REQUEST DATA', $request->all());

    // Validate main sale details
    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'sale_date' => 'required|date',
        'notes' => 'nullable|string',
    ]);

    // Validate existing and new sale items. `true` allows 0 items in a group if others are present.
    $this->validateSale($request, 'existing_sale_items', true);
    $this->validateSale($request, 'new_sale_items', true);

    // Check if at least one item remains after potential deletions and new additions
    $deletedItemIds = array_filter(explode(',', $request->input('deleted_items', '')));
    $remainingExistingItemsCount = $sale->saleItems()->whereNotIn('id', $deletedItemIds)->count();
    $newItemsCount = count($request->input('new_sale_items', []));

    if (($remainingExistingItemsCount + $newItemsCount) === 0) {
        return back()->withErrors(['A sale must contain at least one item after update.'])->withInput();
    }

    try {
        DB::beginTransaction();

        // Update the main Sale record
        $sale->update($request->only(['customer_id', 'sale_date', 'notes']) + [
            'customer_name' => Customer::find($request->customer_id)?->name ?? 'Unknown',
        ]);

        // Track original quantities for existing items BEFORE any updates/deletions
        $originalQuantities = $sale->saleItems->keyBy('id');

        // Handle deleted items first (add their original quantities back to inventory)
        $this->handleDeletedItems((string) $request->input('deleted_items', ''));

        // Process existing items: update their details and adjust inventory by the net change
        if ($request->has('existing_sale_items')) {
            foreach ($request->existing_sale_items as $itemData) {
                $itemId = $itemData['id'];
                $itemToUpdate = SaleItem::findOrFail($itemId);

                $originalTotalQty = (float)($originalQuantities[$itemId]->quantity ?? 0) + (float)($originalQuantities[$itemId]->free_quantity ?? 0);
                $newTotalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);
                $quantityDiff = $newTotalQty - $originalTotalQty;

                if ($quantityDiff !== 0.0) {
                    $this->adjustInventory($itemToUpdate, -$quantityDiff);
                }

                $itemData['applied_extra_discount_percentage'] = $this->normalizeAppliedExtraDiscount($itemData);
                $itemData['is_extra_discount_applied'] = $itemData['applied_extra_discount_percentage'] > 0 ? 1 : 0;

                $itemToUpdate->update($itemData);
            }
        }

        // Process new items: create new SaleItem records and reduce inventory
        if ($request->has('new_sale_items')) {
            foreach ($request->new_sale_items as $itemData) {
                $totalQty = (float)($itemData['quantity'] ?? 0) + (float)($itemData['free_quantity'] ?? 0);

                $itemData['applied_extra_discount_percentage'] = $this->normalizeAppliedExtraDiscount($itemData);
                $itemData['is_extra_discount_applied'] = $itemData['applied_extra_discount_percentage'] > 0 ? 1 : 0;

                $this->adjustInventory($itemData, -$totalQty);

                $sale->saleItems()->create($itemData);
            }
        }

        // Recalculate and update the overall sale totals
        $this->updateSaleTotals($sale);

        // --- DEBUG PATCH: Log update details ---
        \Log::info('SALE UPDATE DEBUG', [
            'sale_id' => $sale->id,
            'deleted_items' => $request->input('deleted_items', ''),
            'recalculated_totals' => [
                'total_amount' => $sale->total_amount,
                'total_gst_amount' => $sale->total_gst_amount,
            ],
            'current_sale_items' => $sale->saleItems()->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'medicine_id' => $item->medicine_id,
                    'batch_number' => $item->batch_number,
                    'quantity' => $item->quantity,
                    'free_quantity' => $item->free_quantity,
                    'sale_price' => $item->sale_price,
                    'gst_rate' => $item->gst_rate,
                    'discount_percentage' => $item->discount_percentage,
                    'applied_extra_discount_percentage' => $item->applied_extra_discount_percentage,
                ];
            }),
        ]);

        DB::commit();
        return redirect()->route('sales.index')->with('success', 'Sale updated successfully.');

    } catch (ValidationException $e) {
        DB::rollBack();
        return back()->withInput()->withErrors($e->errors());
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()->withErrors(['error' => 'Sale update failed: ' . $e->getMessage()]);
    }
}

    /**
     * Remove the specified sale from storage.
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        try {
            DB::beginTransaction();
            $sale->load('saleItems'); // Ensure sale items are loaded for inventory adjustment

            // Adjust inventory back for all items in the deleted sale
            foreach ($sale->saleItems as $item) {
                $this->adjustInventory($item, (float)$item->quantity + (float)$item->free_quantity); // Add stock back
            }

            $sale->delete(); // Soft delete the sale

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale deleted and inventory restored.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Delete failed: ' . $e->getMessage()]);
        }
    }
    
    // --- API / Helper Functions ---

    /**
     * Generates a PDF of the sale bill.
     */
    public function printPdf(Sale $sale): \Illuminate\Http\Response // Returns a Symfony Response for PDF stream
    {
        $sale->load('saleItems.medicine', 'customer');
        $pdf = PDF::loadView('sales.bill', compact('sale'))->setPaper('a5', 'landscape');
        return $pdf->stream('invoice-' . $sale->bill_number . '.pdf');
    }
    
    // --- Private Helper Functions ---

    /**
     * Adjusts the quantity of a specific medicine batch in inventory.
     * Throws ValidationException on insufficient stock when reducing.
     *
     * @param array|\App\Models\SaleItem $item The item data or SaleItem model.
     * @param float $adjustQty The quantity to add (positive) or reduce (negative).
     * @throws \Exception|\Illuminate\Validation\ValidationException
     */
    private function adjustInventory(array|SaleItem $item, float $adjustQty): void
{
    if ($adjustQty === 0.0) return; // No change needed

    $medicineId = is_array($item) ? $item['medicine_id'] : $item->medicine_id;
    $batchNumber = is_array($item) ? $item['batch_number'] : $item->batch_number;

    // Find or create the inventory record for the specific medicine and batch
    $inventory = Inventory::firstOrNew([
        'medicine_id' => $medicineId,
        'batch_number' => $batchNumber,
    ]);

    // If it's a new record, initialize quantity to 0
    if (!$inventory->exists) {
        $inventory->quantity = 0;
    }

    $currentInventoryQuantity = (float)($inventory->quantity ?? 0);

    // Prevent negative stock: This check applies only when reducing stock
    if ($adjustQty < 0 && $currentInventoryQuantity + $adjustQty < 0) {
        throw ValidationException::withMessages([
            'quantity' => "Insufficient stock for medicine ID {$medicineId}, batch '{$batchNumber}'. Current stock: {$currentInventoryQuantity}. Attempted to reduce by: " . abs($adjustQty) . "."
        ]);
    }

    // Update the inventory quantity
    $inventory->quantity = $currentInventoryQuantity + $adjustQty;
    $inventory->save();
}

    /**
     * Calculates total amount and total GST amount for a collection of sale items.
     *
     * @param iterable $items A collection or array of SaleItem data.
     * @return array Contains 'total' (grand total) and 'gst' (total GST amount).
     */
    private function calculateTotals(iterable $items): array
    {
        $subtotal = 0.0;
        $gst = 0.0;

        foreach ($items as $item) {
            // Access properties either from array (request input) or object (Eloquent model)
            $quantity = (float)($item['quantity'] ?? ($item->quantity ?? 0.0));
            $salePrice = (float)($item['sale_price'] ?? ($item->sale_price ?? 0.0));
            $discount = (float)($item['discount_percentage'] ?? ($item->discount_percentage ?? 0.0));
            $gstRate = (float)($item['gst_rate'] ?? ($item->gst_rate ?? 0.0));
            $extraDiscount = (float)($item['applied_extra_discount_percentage'] ?? ($item->applied_extra_discount_percentage ?? 0.0));

            $lineTotal = $quantity * $salePrice;

            // Apply normal discount
            $discountAmount = ($lineTotal * $discount) / 100;
            $afterDiscount = $lineTotal - $discountAmount;

            // Apply extra discount if enabled and value > 0
            if ($extraDiscount > 0.0) {
                $afterDiscount -= ($afterDiscount * $extraDiscount) / 100;
            }

            // Calculate GST on the final discounted price
            $gstAmount = ($afterDiscount * $gstRate) / 100;

            $subtotal += $afterDiscount;
            $gst += $gstAmount;
        }

        return [
            'total' => round($subtotal + $gst, 2),
            'gst' => round($gst, 2),
        ];
    }

    /**
     * Normalizes the 'applied_extra_discount_percentage' from request input.
     * It handles cases where it might be an array (from certain form structures)
     * and ensures it's a float.
     *
     * @param array $itemData The item data array from the request.
     * @return float The normalized discount percentage.
     */
    private function normalizeAppliedExtraDiscount(array $itemData): float
    {
        if (isset($itemData['applied_extra_discount_percentage'])) {
            if (is_array($itemData['applied_extra_discount_percentage'])) {
                return (float)($itemData['applied_extra_discount_percentage'][0] ?? 0);
            } else {
                return (float)$itemData['applied_extra_discount_percentage'];
            }
        }
        return 0.0;
    }

    /**
     * Handles restoring inventory for items marked as deleted during sale update.
     *
     * @param string $deletedItemIds A comma-separated string of IDs of deleted SaleItems.
     */
    private function handleDeletedItems(string $deletedItemIds): void
    {
        $itemIds = array_filter(explode(',', $deletedItemIds));
        foreach ($itemIds as $itemId) {
            if (empty($itemId)) continue; // Skip empty strings from explode/filter
            $item = SaleItem::find($itemId);
            if ($item) {
                // Adjust inventory back by the original quantity sold for this item
                $this->adjustInventory($item, (float)$item->quantity + (float)$item->free_quantity);
                $item->delete(); // Soft delete the SaleItem
            }
        }
    }

    /**
     * Updates the main Sale's total_amount and total_gst_amount based on its current SaleItems.
     *
     * @param \App\Models\Sale $sale The Sale model instance.
     */
    private function updateSaleTotals(Sale $sale): void
    {
        $sale->load('saleItems'); // Reload sale items to get their latest state after updates/deletions

        $totals = $this->calculateTotals($sale->saleItems);

        $sale->update([
            'total_amount' => $totals['total'],
            'total_gst_amount' => $totals['gst'],
        ]);
    }

    /**
     * Generates a unique bill number for new sales.
     *
     * @return string The generated unique bill number.
     */
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

    /**
     * Validates the request data for sale items.
     *
     * @param \Illuminate\Http\Request $request The current request instance.
     * @param string $key The array key for the items (e.g., 'new_sale_items' or 'existing_sale_items').
     * @param bool $allowMinZeroItems If true, the item array can be empty (for update operations where items are deleted).
     */
    private function validateSale(Request $request, string $key, bool $allowMinZeroItems = false): void
    {
        // Ensure the array key exists in the request, even if empty, to prevent validation failures
        if (!$request->has($key)) {
            $request->merge([$key => []]);
        }

        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'sale_date' => 'required|date',
            "$key" => 'array', // Ensures the items group is an array
            "$key.*.medicine_id" => 'required|exists:medicines,id',
            "$key.*.batch_number" => 'required|string',
            "$key.*.quantity" => 'required|numeric|min:0.01', // Quantity must be at least 0.01
            "$key.*.free_quantity" => 'nullable|numeric|min:0', // Free quantity can be 0
            "$key.*.sale_price" => 'required|numeric|min:0',
            "$key.*.gst_rate" => 'nullable|numeric|min:0',
            "$key.*.discount_percentage" => 'nullable|numeric|min:0|max:100',
        ];

        // Apply a minimum of one item rule if not explicitly allowing zero items (e.g., for new sale creation)
        if (!$allowMinZeroItems) {
            $rules["$key"] .= '|min:1'; // The items array itself must have at least one element
        }

        $request->validate($rules);
    }
}