<?php

namespace Tests\Feature\Http; // Use the correct namespace for your test file

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;
   protected function setUp(): void
    {
        parent::setUp();

        // Authenticate a test user
        /** @var \App\Models\User $user */
        $user = User::factory()->createOne();
        $this->actingAs($user);
    }

    #[Test]
    public function it_can_display_a_list_of_suppliers(): void
    {
        Supplier::factory()->count(3)->create();
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee(Supplier::first()->name);
    }

    #[Test]
    public function it_can_display_the_create_supplier_page(): void
    {
        $response = $this->get(route('suppliers.create'));
        $response->assertStatus(200);
        $response->assertSee('Create New Supplier');
    }

    #[Test]
    public function it_can_store_a_new_supplier(): void
    {
        $supplierData = Supplier::factory()->make()->toArray();
        $response = $this->post(route('suppliers.store'), $supplierData);
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['name' => $supplierData['name']]);
    }

    #[Test]
    public function it_fails_to_store_a_supplier_if_validation_fails(): void
    {
        $response = $this->post(route('suppliers.store'), ['name' => '']); // Invalid data
        $response->assertSessionHasErrors('name');
    }

    #[Test]
    public function it_can_display_the_edit_supplier_page(): void
    {
        $supplier = Supplier::factory()->create();
        // UPDATED: Pass the supplier's ID to the route helper
        $response = $this->get(route('suppliers.edit', $supplier->id));
        $response->assertStatus(200);
        $response->assertSee($supplier->name);
    }

    #[Test]
    public function it_can_update_a_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        $updatedData = Supplier::factory()->make()->toArray();

        // UPDATED: Pass the supplier's ID to the route helper
        $response = $this->put(route('suppliers.update', $supplier->id), $updatedData);

        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => $updatedData['name']
        ]);
    }

    #[Test]
    public function it_can_delete_a_supplier(): void
    {
        $supplier = Supplier::factory()->create();
        // UPDATED: Pass the supplier's ID to the route helper
        $response = $this->delete(route('suppliers.destroy', $supplier->id));
        $response->assertRedirect(route('suppliers.index'));
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
}
