<?php

// File: tests/Feature/Http/CustomerControllerTest.php

namespace Tests\Feature\Http;

use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $customerServiceMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customerServiceMock = Mockery::mock(CustomerService::class);
        $this->app->instance(CustomerService::class, $this->customerServiceMock);
    }

    #[Test]
    public function it_can_display_a_list_of_customers(): void
    {
        // Arrange: Create customers in the database so they have IDs
        $customers = Customer::factory()->count(5)->create();
        $paginator = new LengthAwarePaginator($customers, 5, 15);

        $this->customerServiceMock
            ->shouldReceive('getAllCustomers')
            ->once()
            ->andReturn($paginator);

        // Act
        $response = $this->get(route('customers.index'));

        // Assert: Check that the page loads successfully and uses the correct view.
        $response->assertStatus(200);
        $response->assertViewIs('customers.index');
    }

    #[Test]
    public function it_can_store_a_new_customer(): void
    {
        $customerData = ['name' => 'John Doe', 'contact_number' => '1234567890', 'email' => 'john.doe@example.com', 'address' => '123 Main St', 'dln' => 'AB123456'];
        
        $this->customerServiceMock->shouldReceive('createCustomer')->once();
        
        $response = $this->post(route('customers.store'), $customerData);
        
        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer created successfully.');
    }

    #[Test]
    public function it_can_update_a_customer(): void
    {
        $customer = Customer::factory()->create();
        $updateData = ['name' => 'Jane Doe', 'contact_number' => '0987654321'];
        
        $this->customerServiceMock->shouldReceive('updateCustomer')->once();

        $response = $this->put(route('customers.update', $customer), array_merge($customer->toArray(), $updateData));
        
        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer updated successfully.');
    }

    #[Test]
    public function it_can_delete_a_customer(): void
    {
        $customer = Customer::factory()->create();
        $this->customerServiceMock->shouldReceive('deleteCustomer')->once()->with($customer->id)->andReturn(true);
        $response = $this->delete(route('customers.destroy', $customer));
        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHas('success', 'Customer deleted successfully.');
    }

    #[Test]
    public function it_handles_errors_during_deletion(): void
    {
        $customer = Customer::factory()->create();
        $this->customerServiceMock->shouldReceive('deleteCustomer')->once()->with($customer->id)->andThrow(new Exception('Error'));
        $response = $this->delete(route('customers.destroy', $customer));
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }
}
