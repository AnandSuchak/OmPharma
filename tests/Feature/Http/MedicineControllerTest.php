<?php

// File: tests/Feature/Http/controllers/MedicineControllerTest.php

namespace Tests\Feature\Http;

use App\Models\Medicine;
use App\Services\MedicineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

/**
 * Feature test for the refactored MedicineController.
 * This test uses mocking to isolate the controller for testing.
 */
class MedicineControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $medicineServiceMock;

    /**
     * Set up the test environment by mocking the MedicineService.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // We use Mockery to create a "fake" version of the MedicineService.
        // The controller will receive this mock instead of the real service.
        $this->medicineServiceMock = Mockery::mock(MedicineService::class);
        $this->app->instance(MedicineService::class, $this->medicineServiceMock);
    }

   #[Test]
    public function it_can_display_a_list_of_medicines(): void
    {
        // Arrange: We create a fake paginator result.
        // FIXED: Use create() instead of make() so models have an ID for route generation.
        $medicines = \App\Models\Medicine::factory()->count(5)->create();
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator($medicines, 5, 15);

        // We tell the mock service to expect a call to `getAllMedicines`
        // and to return our fake paginator when it happens.
        $this->medicineServiceMock
            ->shouldReceive('getAllMedicines')
            ->once()
            ->andReturn($paginator);

        // Act: Call the controller's index route.
        $response = $this->get(route('medicines.index'));

        // Assert: Check that the controller behaved correctly.
        $response->assertStatus(200);
        $response->assertViewIs('medicines.index');
        $response->assertViewHas('medicines', $paginator);
    }

    #[Test]
    public function it_can_store_a_new_medicine(): void
    {
        // Arrange: Define the data we will send.
        $medicineData = [
            'name' => 'Testocilin',
            'company_name' => 'Test Pharma',
            'pack' => '10 tab',
            'gst_rate' => 12,
            'hsn_code' => '12345',
            'description' => 'A test medicine'
        ];

        // We tell the mock service to expect a call to `createMedicine`.
        $this->medicineServiceMock
            ->shouldReceive('createMedicine')
            ->once()
            ->with($medicineData); // We can even assert it was called with the correct data.

        // Act: Post the data to the store route.
        $response = $this->post(route('medicines.store'), $medicineData);

        // Assert: Check for the correct redirect and session message.
        $response->assertRedirect(route('medicines.index'));
        $response->assertSessionHas('success', 'Medicine added successfully.');
    }

    #[Test]
    public function it_can_update_a_medicine(): void
    {
        // Arrange: Create a real medicine to get an ID.
        $medicine = Medicine::factory()->create();
        $updatedData = ['name' => 'Updated Name', 'company_name' => 'Updated Co'];

        // We tell the mock to expect a call to `updateMedicine`.
        $this->medicineServiceMock
            ->shouldReceive('updateMedicine')
            ->once()
            ->with($medicine->id, Mockery::on(function ($data) use ($updatedData) {
                // Assert that the data passed to the service is correct.
                return $data['name'] === $updatedData['name'] && $data['company_name'] === $updatedData['company_name'];
            }));

        // Act: Send the update request.
        $response = $this->put(route('medicines.update', $medicine), array_merge($medicine->toArray(), $updatedData));

        // Assert: Check for the correct redirect and session message.
        $response->assertRedirect(route('medicines.index'));
        $response->assertSessionHas('success', 'Medicine updated successfully.');
    }

    #[Test]
    public function it_can_soft_delete_a_medicine(): void
    {
        // Arrange
        $medicine = Medicine::factory()->create();

        // We tell the mock to expect a call to `deleteMedicine`.
        $this->medicineServiceMock
            ->shouldReceive('deleteMedicine')
            ->once()
            ->with($medicine->id);

        // Act
        $response = $this->delete(route('medicines.destroy', $medicine));

        // Assert
        $response->assertRedirect(route('medicines.index'));
        $response->assertSessionHas('success', 'Medicine deleted successfully.');
    }

    #[Test]
    public function it_prevents_deleting_medicine_with_related_transactions(): void
    {
        // Arrange
        $medicine = Medicine::factory()->create();

        // We tell the mock to expect a call to `deleteMedicine` and to
        // throw the specific Exception our service would throw.
        $this->medicineServiceMock
            ->shouldReceive('deleteMedicine')
            ->once()
            ->with($medicine->id)
            ->andThrow(new Exception('Cannot delete medicine that has related transactions.'));

        // Act
        $response = $this->delete(route('medicines.destroy', $medicine));

        // Assert: Check that the controller caught the exception and returned an error.
        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    }
}
