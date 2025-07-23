<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    /**
     * Display a listing of the suppliers.
     */
 public function index(Request $request): View|\Illuminate\Http\JsonResponse // MODIFIED: Added JsonResponse return type
    {
        $query = Supplier::latest();

        // NEW: Handle search query from AJAX
        if ($request->ajax() && $request->has('search')) {
            $searchTerm = $request->input('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('phone_number', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('address', 'like', "%{$searchTerm}%");
            });
        }

        $suppliers = $query->paginate(15);

        if ($request->ajax()) {
            // Return JSON response for AJAX requests
            return response()->json([
                'html' => view('suppliers.partials.supplier_table_rows', compact('suppliers'))->render(),
                'pagination' => $suppliers->links('pagination::bootstrap-5')->render()

            ]);
        }

        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * Show the form for creating a new supplier.
     */
    public function create(): View
    {
        return view('suppliers.create');
    }

    /**
     * Store a newly created supplier in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|unique:suppliers,phone_number',
            'email' => 'nullable|email|unique:suppliers,email',
            'gst' => 'required|string|unique:suppliers,gst',
            'address' => 'nullable|string',
            'dln' => 'required|string|unique:suppliers,dln',
        ]);

        try {
            Supplier::create($validated);
            return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
        } catch (\Throwable $e) {
            Log::error("Supplier creation failed: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Something went wrong while saving the supplier.']);
        }
    }

    /**
     * Display the specified supplier.
     */
    public function show(Supplier $supplier): View
    {
        return view('suppliers.show', compact('supplier'));
    }

    /**
     * Show the form for editing the specified supplier.
     */
    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    /**
     * Update the specified supplier in storage.
     */
    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|unique:suppliers,phone_number,' . $supplier->id,
            'email' => 'nullable|email|unique:suppliers,email,' . $supplier->id,
            'gst' => 'required|string|unique:suppliers,gst,' . $supplier->id,
            'address' => 'nullable|string',
            'dln' => 'required|string|unique:suppliers,dln,' . $supplier->id,
        ]);

        try {
            $supplier->update($validated);
            return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
        } catch (\Throwable $e) {
            Log::error("Supplier update failed: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Update failed. Please try again.']);
        }
    }

    /**
     * Remove the specified supplier from storage.
     */
    public function destroy(Supplier $supplier): RedirectResponse
    {
        try {
            $supplier->delete();
            return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
        } catch (\Throwable $e) {
            Log::error("Supplier deletion failed: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Delete failed. Supplier may be linked to other records.']);
        }
    }

        /**
     * Search for suppliers by name for AJAX requests.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $suppliers = \App\Models\Supplier::where('name', 'LIKE', "%{$query}%")
            ->limit(15)
            ->get(['id', 'name']);

        $results = $suppliers->map(function ($supplier) {
            return ['id' => $supplier->id, 'text' => $supplier->name];
        });

        return response()->json($results);
    }
}
