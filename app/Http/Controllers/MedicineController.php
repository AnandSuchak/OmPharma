<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Medicine;
use App\Models\PurchaseBillItem;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class MedicineController extends Controller
{
    /**
     * Display a listing of the medicines.
     */
    public function index(): View
    {
        $medicines =Medicine::withoutTrashed()->get();
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
            'quantity' => 'required|numeric|min:0',
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
            'quantity' => 'required|numeric|min:0',
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

public function getBatches($medicineId)
{
    // Fetch distinct batch entries from purchase_bill_items for this medicine
    $batches = PurchaseBillItem::where('medicine_id', $medicineId)
        ->orderBy('expiry_date', 'asc')
        ->get([
            'batch_number',
            'expiry_date',
            'quantity',
            'ptr',
            'gst_rate',
            'sale_price',
            'discount_percentage',
        ])
        ->groupBy(function ($item) {
            // Use batch + expiry as key
            return $item->batch_number . '_' . $item->expiry_date;
        })
        ->map(function ($group) {
            // Use the latest purchase for this batch
            $latest = $group->sortByDesc('id')->first();

            return [
                'batch_number'        => $latest->batch_number,
                'expiry_date'         => $latest->expiry_date,
                'quantity'            => $latest->quantity,
                'ptr'                 => $latest->ptr,
                'gst_rate'            => $latest->gst_rate,
                'sale_price'          => $latest->sale_price,
                'discount_percentage' => $latest->discount_percentage,
            ];
        })
        ->values(); // Reset keys to 0, 1, 2...

    return response()->json($batches);
}

    
    public function getGstRate(Medicine $medicine)
    {
        return response()->json(['gst_rate' => $medicine->gst_rate]);
    }
public function search(Request $request)
{
    $query = $request->input('q');

    $medicines = Medicine::where('name', 'like', "%{$query}%")
        ->orWhere('company_name', 'like', "%{$query}%")
        ->limit(20)
        ->get(['id', 'name', 'company_name']);

    return response()->json($medicines);
}

/**
 * Search for unique medicine names and companies.
 */
public function searchNames(Request $request)
{
    $query = $request->input('q');

    $medicines = Medicine::select('name', 'company_name')
        ->where('name', 'like', "%{$query}%")
        ->distinct()
        ->limit(15)
        ->get();

    // We need to format the response for Select2
    $results = $medicines->map(function ($med) {
        return [
            'id' => $med->name . '|' . ($med->company_name ?? ''), // Combine name and company as a unique ID
            'text' => $med->name . ' (' . ($med->company_name ?? 'Generic') . ')'
        ];
    });

    return response()->json($results);
}

/**
 * Get all packs and their medicine IDs for a given name and company.
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
        ->whereNull('deleted_at') // Ensure we only get active medicines
        ->get(['id', 'pack']);

    return response()->json($packs);
}

// In app/Http/Controllers/MedicineController.php
public function getDetails(Medicine $medicine)
{
    return response()->json([
        'name_and_company' => $medicine->name . ' (' . ($medicine->company_name ?? 'Generic') . ')',
        'name_and_company_value' => $medicine->name . '|' . ($medicine->company_name ?? ''),
    ]);
}

}