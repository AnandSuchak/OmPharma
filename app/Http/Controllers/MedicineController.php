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
use Illuminate\Http\Response; 
use Illuminate\Http\JsonResponse;
use App\Models\Sale;
use Carbon\Carbon;


class MedicineController extends Controller
{
    /**
     * Display a listing of the medicines.
     * Handles AJAX search and pagination for the index page.
     */
public function index(Request $request): View|JsonResponse|Response // Add Response to the type hint
{
    $query = Medicine::withoutTrashed()->orderBy('name');

    if ($request->has('search')) {
        $searchTerm = $request->input('search');
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'like', "%{$searchTerm}%")
              ->orWhere('company_name', 'like', "%{$searchTerm}%")
              ->orWhere('hsn_code', 'like', "%{$searchTerm}%");
        });
    }

    $medicines = $query->paginate(15);

    if ($request->ajax()) {
        // Return a Response object containing the HTML string
        return response(view('medicines.partials.medicine_table', compact('medicines'))->render());
    }

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
            'name' => 'required|string|max:255',
            'hsn_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            // 'quantity' is likely not directly stored on Medicine model, but handled by Inventory
            // 'quantity' => 'nullable|numeric|min:0', // Removed if not directly on Medicine model
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'pack' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
        ]);

        Medicine::create($request->all());

        return redirect()->route('medicines.index')->with('success', 'Medicine added successfully.');
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
            'name' => 'required|string|max:255',
            'hsn_code' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            // 'quantity' is likely not directly stored on Medicine model, but handled by Inventory
            // 'quantity' => 'nullable|numeric|min:0', // Removed if not directly on Medicine model
            'gst_rate' => 'nullable|numeric|min:0|max:100',
            'pack' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
        ]);

        $medicine->update($request->all());

        return redirect()->route('medicines.index')->with('success', 'Medicine updated successfully.');
    }

    /**
     * Remove the specified medicine from storage.
     */
    public function destroy(Medicine $medicine): RedirectResponse
    {
        // Check for related records before allowing deletion (soft delete is preferred)
        if (
            $medicine->purchaseBillItems()->exists() ||
            $medicine->inventories()->exists() ||
            $medicine->saleItems()->exists()
        ) {
            return back()->withErrors(['error' => 'Cannot delete medicine that has related transactions.']);
        }

        $medicine->delete(); // Uses soft delete if configured on the model

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

      public function getBatches(Request $request, Medicine $medicine): JsonResponse
    {
        $saleId = $request->query('sale_id');

        $baseBatchesQuery = Inventory::query()
            ->select(
                'inventories.batch_number',
                'inventories.expiry_date',
                'inventories.quantity',
                'purchase_bill_items.sale_price',
                'purchase_bill_items.gst_rate',
                'purchase_bill_items.ptr'
            )
            ->join('purchase_bill_items', function ($join) {
                $join->on('inventories.medicine_id', '=', 'purchase_bill_items.medicine_id')
                     ->on('inventories.batch_number', '=', 'purchase_bill_items.batch_number');
            })
            ->where('inventories.medicine_id', $medicine->id)
            ->whereNull('purchase_bill_items.deleted_at');

        $batches = collect();

        if ($saleId) {
            $existingSaleItems = SaleItem::where('sale_id', $saleId)
                ->where('medicine_id', $medicine->id)
                ->get()
                ->keyBy('batch_number');

            $saleItemBatchesFromPastSaleQuery = SaleItem::query()
                ->select(
                    'sale_items.batch_number',
                    'purchase_bill_items.expiry_date',
                    DB::raw('0 as quantity'),
                    'purchase_bill_items.sale_price',
                    'purchase_bill_items.gst_rate',
                    'purchase_bill_items.ptr'
                )
                ->join('purchase_bill_items', function ($join) {
                     $join->on('sale_items.medicine_id', '=', 'purchase_bill_items.medicine_id')
                          ->on('sale_items.batch_number', '=', 'purchase_bill_items.batch_number');
                })
                ->where('sale_items.sale_id', $saleId)
                ->where('sale_items.medicine_id', $medicine->id)
                ->whereNull('purchase_bill_items.deleted_at')
                ->distinct();

            $combinedBatches = $baseBatchesQuery->union($saleItemBatchesFromPastSaleQuery)->get();

            foreach ($combinedBatches as $batch) {
                $batch->existing_sale_item = $existingSaleItems->get($batch->batch_number);
            }
            $batches = $combinedBatches;

        } else {
            $batches = $baseBatchesQuery->where('inventories.quantity', '>', 0)->get();
        }

        $mappedBatches = $batches->map(function ($batch) {
            return [
                'batch_number'        => $batch->batch_number,
                'expiry_date'         => $batch->expiry_date ? Carbon::parse($batch->expiry_date)->format('Y-m-d') : '',
                'quantity'            => (float)$batch->quantity,
                'sale_price'          => (float)($batch->sale_price ?? 0.0),
                'ptr'                 => (float)($batch->ptr ?? 0.0),
                'gst'                 => (float)($batch->gst_rate ?? 0.0),
                'existing_sale_item'  => $batch->existing_sale_item ?? null
            ];
        });

        return response()->json($mappedBatches->values());
    }

    /**
     * **MODIFIED** API endpoint to search medicines with available stock.
     * This now groups by medicine name and returns available packs.
     */
    public function searchWithQty(Request $request): JsonResponse
    {
        $query = $request->input('q');

        // Fetch all medicine variants that have stock and match the search query
        $medicinesWithStock = Medicine::query()
            ->join('inventories as i', 'medicines.id', '=', 'i.medicine_id')
            ->where('i.quantity', '>', 0)
            ->whereNull('i.deleted_at') // Ensure inventory item is not soft-deleted
            ->where(function($q) use ($query) {
                $q->where('medicines.name', 'like', "%{$query}%")
                  ->orWhere('medicines.company_name', 'like', "%{$query}%");
            })
            ->select(
                'medicines.id',
                'medicines.name',
                'medicines.company_name',
                'medicines.pack'
            )
            ->distinct() // Get unique medicine definitions
            ->get();

        // Group the results by the common medicine name
        $groupedByName = $medicinesWithStock->groupBy('name');

        $results = [];
        foreach ($groupedByName as $name => $packs) {
            $first = $packs->first(); // Get the first item to use its company name
            $companyName = $first->company_name ?? 'Generic';

            // If there's only one pack for this name, return it directly
            if ($packs->count() == 1) {
                $results[] = [
                    'id' => $name, // Use the name as the top-level ID for the group
                    'text' => "{$name} ({$companyName}) - {$first->pack}",
                    'packs' => [[
                        'medicine_id' => $first->id,
                        'pack' => $first->pack,
                        'text' => $first->pack,
                    ]]
                ];
            } else {
            // If there are multiple packs, create a nested structure
                $results[] = [
                    'id' => $name,
                    'text' => "{$name} ({$companyName}) - Multiple Packs",
                    'packs' => $packs->map(function($pack) {
                        return [
                            'medicine_id' => $pack->id,
                            'pack' => $pack->pack,
                            'text' => $pack->pack, // Text for the pack dropdown
                        ];
                    })->values()->all() // Ensure it's a zero-indexed array
                ];
            }
        }

        return response()->json($results);
    }

    /**
     * API endpoint to get GST rate for a specific medicine.
     */
    public function getGstRate(Medicine $medicine)
    {
        return response()->json(['gst_rate' => (float)($medicine->gst_rate ?? 0.0)]);
    }

    /**
     * API endpoint to search medicines by name and company.
     * This is used by the Sales Bill medicine selection,
     * formatting results as "Medicine Name (Company) - Pack".
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

    /*
     * NEW API endpoint to search medicines with available stock by name and company.
     *This will be used by the Sales Bill medicine selection.

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
            ->distinct('medicines.id') // Ensure unique medicine entries even if multiple batches exist
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
    }*/

    /**
     * Search for unique medicine names and companies.
     * This seems to be primarily used by the Purchase Bill flow if it has a 'pack selection' step.
     */
    public function searchNames(Request $request)
    {
        $query = $request->input('q');

        $medicines = Medicine::select('id', 'name', 'company_name', 'pack')
            ->where('name', 'like', "%{$query}%")
            ->distinct() // Ensure unique medicine entries by name/company
            ->limit(15)
            ->get();

        $results = $medicines->map(function ($med) {
            $companyName = $med->company_name ?? 'Generic';
            return [
                'id' => $med->id, // Use actual medicine ID, not compound ID
                'text' => $med->name . ' (' . ($companyName) . ')',
                'name' => $med->name, // Pass name for frontend use
                'company_name' => $med->company_name, // Pass company name for frontend use
                'pack' => $med->pack // Pass pack for frontend use
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
            'company_name' => 'nullable|string', // Company name might be needed for specific pack lookup
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