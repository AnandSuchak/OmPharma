<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\PurchaseBillItem;
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
        $medicines =Medicine::withoutTrashed()->orderByDesc('id')->get();
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
     * Fetches current available quantity from Inventory
     * and pricing details from the most recent PurchaseBillItem for that batch.
     * This method is used by the Sales Bill form.
     */
    public function getBatches($medicineId)
    {
        $batches = Inventory::query()
            ->join('purchase_bill_items', function ($join) {
                $join->on('inventories.medicine_id', '=', 'purchase_bill_items.medicine_id')
                     ->on('inventories.batch_number', '=', 'purchase_bill_items.batch_number');
            })
            ->where('inventories.medicine_id', $medicineId)
            ->where('inventories.quantity', '>', 0) // Crucial: Only show batches with available stock
            ->whereNull('purchase_bill_items.deleted_at') // Ensure purchase item is not soft-deleted
            ->select(
                'inventories.batch_number',
                'inventories.expiry_date',
                'inventories.quantity',   // IMPORTANT: This is the current available quantity from Inventory
                'purchase_bill_items.sale_price',
                'purchase_bill_items.gst_rate',
                'purchase_bill_items.ptr'
            )
            ->distinct() // Ensures unique batch number/expiry combinations
            ->orderBy('inventories.expiry_date') // Order by expiry date
            ->get();

        return response()->json($batches);
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
            ->get(['id', 'name', 'company_name', 'pack']); // IMPORTANT: Select 'pack' here

        // Map the results to the format expected by Select2 (id, text) and include 'pack'
        $results = $medicines->map(function ($item) {
            $companyName = $item->company_name ?? 'Generic'; // Pre-process for cleaner string interpolation
            $packDisplay = $item->pack ? " - {$item->pack}" : ''; // Format pack if it exists
            
            return [
                'id' => $item->id,
                'text' => "{$item->name} ({$companyName}){$packDisplay}", // Formatted as "Name (Company) - Pack"
                'pack' => $item->pack // Include the raw pack information
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