<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(): View
    {
       $customers =Customer::withoutTrashed()->get();
        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): View
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateCustomer($request);

        Customer::create($validated);

        return redirect()->route('customers.index')->with('success', 'Customer added successfully.');
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): View
    {
        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in storage.
     */
    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $validated = $this->validateCustomer($request);

        $customer->update($validated);

        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        if ($customer->sales()->exists()) {
            return back()->withErrors([
                'error' => 'Cannot delete customer who has associated sales records.'
            ]);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }

    /**
     * Validate customer request and ensure at least one of GST or PAN is present.
     */
    protected function validateCustomer(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_number' => 'nullable|numeric',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'dln' => 'required|string',
            'gst_number' => 'nullable|string',
            'pan_number' => 'nullable|string',
        ]);

        if (empty($validated['gst_number']) && empty($validated['pan_number'])) {
            throw ValidationException::withMessages([
                'gst_number' => 'Please provide either GST Number or PAN Number.',
                'pan_number' => '',
            ]);
        }

        return $validated;
    }

      /**
     * Search for customers by name or phone for AJAX requests.
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        $customers = \App\Models\Customer::where('name', 'LIKE', "%{$query}%")
            ->orWhere('contact_number', 'LIKE', "%{$query}%")
            ->limit(15)
            ->get(['id', 'name']);

        $results = $customers->map(function ($customer) {
            return ['id' => $customer->id, 'text' => $customer->name];
        });

        return response()->json($results);
    }
}
