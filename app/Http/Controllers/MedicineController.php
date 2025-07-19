<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\PurchaseBillItem;
use App\Models\SaleItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    /**
     * Display a listing of the medicines.
     */
    public function index(): View
    {
        $medicines = Medicine::withoutTrashed()->orderByDesc('id')->paginate(10);
        return view('medicines.index', compact('medicines'));
    }

    /**
     * Show the form for creating a new medicine.
     */
    public function create(): View
    {
        return view('medicines.create');
    }

    /**
     * Store a newly created medicine in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'hsn_code' => 'nullable',
            'description' => 'nullable',
            'quantity' => 'nullable|numeric|min:0',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'pack' => 'nullable',
            'company_name' => 'nullable',
        ]);

        Medicine::create($request->all());

        return redirect()->route('medicines.index')->with('success', 'Medicine created successfully.');
    }

    /**
     * Display the specified medicine.
     */
    public function show(Medicine $medicine): View
    {
        return view('medicines.show', compact('medicine'));
    }

    /**
     * Show the form for editing the specified medicine.
     */
    public function edit(Medicine $medicine): View
    {
        return view('medicines.edit', compact('medicine'));
    }

    /**
     * Update the specified medicine in storage.
     */
    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'hsn_code' => 'nullable',
            'description' => 'nullable',
            'quantity' => 'nullable|numeric|min:0',
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'pack' => 'nullable',
            'company_name' => 'nullable',
        ]);

        $medicine->update($request->all());

        return redirect()->route('medicines.index')->with('success', 'Medicine updated successfully.');
    }

    /**
     * Remove the specified medicine from storage.
     */
    public function destroy(Medicine $medicine): RedirectResponse
    {
        if (
            $medicine->purchaseBillItems()->exists() ||
            $medicine->inventories()->exists() ||
            $medicine->saleItems()->exists()
        ) {
            return back()->withErrors(['error' => 'Cannot delete medicine that has related transactions.']);
        }

        $medicine->delete();

        return redirect()->route('medicines.index')->with('success', 'Medicine deleted successfully.');
    }

    /**
     * API endpoint to get batches for a specific medicine.
     * This method is now unified for both create and edit forms.
     * Fetches current available quantity from Inventory and purchase details.
     * Optionally fetches existing sale item data if a sale_id is provided (for edit mode).
     *
     * @param Request $request
     * @param int $medicineId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBatches(Request $request, $medicineId)
    {
        $saleId = $request->query('sale_id');

        // Base query for current inventory batches
        $inventoryBatchesQuery = Inventory::query()
            ->join('purchase_bill_items', function ($join) {
                $join->on('inventories.medicine_id', '=', 'purchase_bill_items.medicine_id')
                     ->on('inventories.batch_number', '=', 'purchase_bill_items.batch_number');
            })
            ->where('inventories.medicine_id', $medicineId)
            ->whereNull('purchase_bill_items.deleted_at')
            ->select(
                'inventories.batch_number',
                'inventories.expiry_date',
                'inventories.quantity', // Current inventory quantity
                'purchase_bill_items.sale_price',
                'purchase_bill_items.gst_rate',
                'purchase_bill_items.ptr'
            );

        $batches = collect();

        if ($saleId) {
            // Fetch existing sale items for this medicine and sale
            $existingSaleItems = SaleItem::where('sale_id', $saleId)
                ->where('medicine_id', $medicineId)
                ->get([
                    'batch_number',
                    'quantity', // Quantity sold in this sale
                    'free_quantity',
                    'sale_price',
                    'discount_percentage',
                    'applied_extra_discount_percentage',
                    'is_extra_discount_applied',
                    'expiry_date', // Also fetch expiry_date from SaleItem
                    'gst_rate',    // Also fetch gst_rate from SaleItem
                    'ptr'          // Also fetch ptr from SaleItem
                ]);

            // Create a query for batches from existing sale items,
            // ensuring we get their purchase details if available.
            $saleItemBatchesQuery = SaleItem::query()
                ->join('purchase_bill_items', function ($join) {
                    $join->on('sale_items.medicine_id', '=', 'purchase_bill_items.medicine_id')
                         ->on('sale_items.batch_number', '=', 'purchase_bill_items.batch_number');
                })
                ->where('sale_items.sale_id', $saleId)
                ->where('sale_items.medicine_id', $medicineId)
                ->whereNull('purchase_bill_items.deleted_at')
                ->select(
                    'sale_items.batch_number',
                    'purchase_bill_items.expiry_date', // Use expiry from purchase bill item for consistency
                    DB::raw('0 as quantity'), // Set current quantity to 0 as it's from a past sale item, not current inventory
                    'purchase_bill_items.sale_price',
                    'purchase_bill_items.gst_rate',
                    'purchase_bill_items.ptr'
                )
                ->distinct();

            // Union the current inventory batches with batches from existing sale items
            // This ensures that even if a batch is out of stock, if it was part of THIS sale, it's included.
            $batches = $inventoryBatchesQuery->union($saleItemBatchesQuery)->get();

            // Now, iterate through the combined batches and attach the existing_sale_item data
            foreach ($batches as $batch) {
                $match = $existingSaleItems->firstWhere('batch_number', $batch->batch_number);
                if ($match) {
                    $batch->existing_sale_item = $match;
                }
            }

        } else {
            // If no saleId is provided (i.e., new sale entry), only show batches with available stock
            $batches = $inventoryBatchesQuery->where('inventories.quantity', '>', 0)->get();
        }

        return response()->json($batches->values());
    }

    /**
     * API endpoint to get GST rate for a specific medicine.
     */
    public function getGstRate(Medicine $medicine)
    {
        return response()->json(['gst_rate' => $medicine->gst_rate]);
    }

    /**
     * API endpoint to search medicines by name and company.
     * This is used by the Sales Bill medicine selection,
     * formatting results as "Medicine Name - Pack".
     */
    public function search(Request $request)
    {
        $query = $request->input('q');

        $medicines = Medicine::where('name', 'like', "%{$query}%")
            ->orWhere('company_name', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'name', 'company_name', 'pack']);

        $results = $medicines->map(function ($item) {
            $companyName = $item->company_name ?? 'Generic';
            $packDisplay = $item->pack ? " - {$item->pack}" : '';

            return [
                'id' => $item->id,
                'text' => "{$item->name} ({$companyName}){$packDisplay}",
                'pack' => $item->pack
            ];
        });

        return response()->json($results);
    }

    /**
     * NEW API endpoint to search medicines with available stock by name and company.
     * This will be used by the Sales Bill medicine selection.
     */
    public function searchWithQty(Request $request)
    {
        $query = $request->input('q');

        $medicines = Medicine::query()
            ->join('inventories as i', 'medicines.id', '=', 'i.medicine_id')
            ->where('i.quantity', '>', 0)
            ->whereNull('i.deleted_at')
            ->where(function($q) use ($query) {
                $q->where('medicines.name', 'like', "%{$query}%")
                  ->orWhere('medicines.company_name', 'like', "%{$query}%");
            })
            ->select('medicines.id', 'medicines.name', 'medicines.company_name', 'medicines.pack')
            ->distinct('medicines.id')
            ->limit(20)
            ->get();

        $results = $medicines->map(function ($item) {
            $companyName = $item->company_name ?? 'Generic';
            $packDisplay = $item->pack ? " - {$item->pack}" : '';

            return [
                'id' => $item->id,
                'text' => "{$item->name} ({$companyName}){$packDisplay}",
                'pack' => $item->pack
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for unique medicine names and companies.
     * This seems to be primarily used by the Purchase Bill flow if it has a 'pack selection' step.
     */
    public function searchNames(Request $request)
    {
        $query = $request->input('q');

        $medicines = Medicine::select('id', 'name', 'company_name', 'pack')
            ->where('name', 'like', "%{$query}%")
            ->distinct()
            ->limit(15)
            ->get();

        $results = $medicines->map(function ($med) {
            $companyName = $med->company_name ?? 'Generic';
            return [
                'id' => $med->name . '|' . ($med->company_name ?? ''), // Compound ID for generic search
                'text' => $med->name . ' (' . ($companyName) . ')',
                'medicine_id' => $med->id,
                'pack' => $med->pack
            ];
        });

        return response()->json($results);
    }

    /**
     * Get all packs and their medicine IDs for a given name and company.
     * This seems specific to the Purchase Bill's workflow for selecting a pack.
     */
    public function getPacksForName(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
        ]);

        $packs = Medicine::where('name', $request->name)
            ->when($request->filled('company_name'), function ($query) use ($request) {
                return $query->where('company_name', $request->company_name);
            })
            ->whereNull('deleted_at')
            ->get(['id', 'pack']);

        return response()->json($packs);
    }

    /**
     * Get details for a specific medicine.
     */
    public function getDetails(Medicine $medicine)
    {
        return response()->json([
            'name_and_company' => $medicine->name . ' (' . ($medicine->company_name ?? 'Generic') . ')',
            'name_and_company_value' => $medicine->name . '|' . ($medicine->company_name ?? ''),
            'pack' => $medicine->pack,
        ]);
    }
}
