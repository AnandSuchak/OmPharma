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
    // Fetch batches from inventory (only where quantity > 0)
    $inventoryBatches = Inventory::where('medicine_id', $medicineId)
        ->where('quantity', '>', 0)
        ->orderBy('expiry_date', 'asc')
        ->get(['batch_number', 'expiry_date', 'quantity']);

    // Attach pricing details from PurchaseBillItem
    $result = $inventoryBatches->map(function ($inv) use ($medicineId) {
        $pricing = PurchaseBillItem::where('medicine_id', $medicineId)
            ->where('batch_number', $inv->batch_number)
            ->whereDate('expiry_date', $inv->expiry_date)
            ->latest('id') // In case multiple entries, take latest purchase
            ->first(['ptr', 'gst_rate', 'sale_price']);

        return [
            'batch_number'   => $inv->batch_number,
            'expiry_date'    => $inv->expiry_date,
            'quantity'       => $inv->quantity,
            'ptr'            => $pricing->ptr ?? null,
            'gst_rate'       => $pricing->gst_rate ?? null,
            'sale_price'  => $pricing->sale_price ?? null,
        ];
    });

    return response()->json($result);
}
    
    public function getGstRate(Medicine $medicine)
    {
        return response()->json(['gst_rate' => $medicine->gst_rate]);
    }
}