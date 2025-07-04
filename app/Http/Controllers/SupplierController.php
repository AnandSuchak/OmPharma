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
    public function index(): View
    {
        $suppliers = Supplier::all();
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
            'phone_number' => 'required|string|unique:suppliers,phone_number',
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
            'phone_number' => 'required|string|unique:suppliers,phone_number,' . $supplier->id,
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
}
