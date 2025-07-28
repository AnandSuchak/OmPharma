<?php

// File: app/Http/Controllers/CustomerController.php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class CustomerController extends Controller
{
    protected CustomerService $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Display a listing of the customers.
     */
    public function index(Request $request): View|JsonResponse
    {
        /** @var LengthAwarePaginator $customers */
        $customers = $this->customerService->getAllCustomers($request->all());

        if ($request->ajax()) {
            return response()->json([
                'html' => view('customers.partials.customer_table_rows', compact('customers'))->render(),
                'pagination' => $customers->links('pagination::bootstrap-5')->toHtml(),
            ]);
        }

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
     * Validation is now handled by the StoreCustomerRequest class.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        try {
            // The $request->validated() method returns only the data that passed validation.
            $this->customerService->createCustomer($request->validated());
            return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
        } catch (Exception $e) {
            Log::error("Customer creation failed: " . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to create customer.']);
        }
    }

    /**
     * Display the specified customer.
     * Laravel automatically finds the customer or throws a 404 error.
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
     * Validation is now handled by the UpdateCustomerRequest class.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        try {
            $this->customerService->updateCustomer($customer->id, $request->validated());
            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (Exception $e) {
            Log::error("Customer update failed for ID {$customer->id}: " . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Failed to update customer.']);
        }
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        try {
            $this->customerService->deleteCustomer($customer->id);
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
        } catch (Exception $e) {
            Log::error("Customer deletion failed for ID {$customer->id}: " . $e->getMessage());
            return back()->withErrors(['error' => 'Failed to delete customer.']);
        }
    }

    /**
     * Search for customers by name or phone for AJAX requests.
     */
    public function search(Request $request): JsonResponse
    {
        $customers = $this->customerService->searchCustomers($request->get('q'));

        $results = $customers->map(function ($customer) {
            $text = $customer->name . ($customer->contact_number ? ' (' . $customer->contact_number . ')' : '');
            return ['id' => $customer->id, 'text' => $text];
        });

        return response()->json($results);
    }
}
