<?php

namespace App\Http\Controllers;

use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    protected SupplierService $supplierService;

    /**
     * SupplierController constructor.
     */
    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * Display a listing of the suppliers.
     */
    public function index(Request $request): View|\Illuminate\Http\JsonResponse
    {
        $suppliers = $this->supplierService->getAllSuppliers($request->all());

        if (! $suppliers instanceof LengthAwarePaginator) {
            Log::error('Unexpected type returned from SupplierService.');
            return response()->json(['error' => 'An internal error occurred.'], 500);
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('suppliers.partials.supplier_table_rows', compact('suppliers'))->render(),
                // FIXED: Removed the extra ->render() call. The links() method already returns the rendered HTML.
                'pagination' => $suppliers->links('pagination::bootstrap-5')
            ]);
        }

        return view('suppliers.index', compact('suppliers'));
    }

    public function create(): View
    {
        return view('suppliers.create');
    }

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
            $this->supplierService->createSupplier($validated);
            return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
        } catch (\Throwable $e) {
            Log::error("Supplier creation failed: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Something went wrong while saving the supplier.']);
        }
    }

    public function show(int $id): View
    {
        $supplier = $this->supplierService->getSupplierById($id);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(int $id): View
    {
        $supplier = $this->supplierService->getSupplierById($id);
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|unique:suppliers,phone_number,' . $id,
            'email' => 'nullable|email|unique:suppliers,email,' . $id,
            'gst' => 'required|string|unique:suppliers,gst,' . $id,
            'address' => 'nullable|string',
            'dln' => 'required|string|unique:suppliers,dln,' . $id,
        ]);

        try {
            $this->supplierService->updateSupplier($id, $validated);
            return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
        } catch (\Throwable $e) {
            Log::error("Supplier update failed: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'Update failed. Please try again.']);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $this->supplierService->deleteSupplier($id);
            return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
        } catch (\Throwable $e) {
            Log::error("Supplier deletion failed: " . $e->getMessage());
            return redirect()->back()->withErrors(['error' => 'Delete failed. Supplier may be linked to other records.']);
        }
    }

    public function search(Request $request)
    {
        $suppliers = $this->supplierService->searchSuppliersByName($request->get('q'));

        $results = $suppliers->map(fn($supplier) => [
            'id' => $supplier->id,
            'text' => $supplier->name,
        ]);

        return response()->json($results);
    }
}
