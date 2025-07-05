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
        $medicines = Medicine::all();
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
            'unit' => 'required',
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
            'unit' => 'required',
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


}