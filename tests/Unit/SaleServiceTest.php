<?php

namespace Tests\Unit;

use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\SaleRepositoryInterface;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Exception;

class SaleServiceTest extends TestCase
{
    use RefreshDatabase;

    private SaleService $saleService;

    /** @var SaleRepositoryInterface&MockInterface */
    private $saleRepositoryMock;

    /** @var InventoryRepositoryInterface&MockInterface */
    private $inventoryRepositoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->saleRepositoryMock = $this->mock(SaleRepositoryInterface::class);
        $this->inventoryRepositoryMock = $this->mock(InventoryRepositoryInterface::class);

        // Pass both mocked repositories to the service constructor
        $this->saleService = new SaleService($this->saleRepositoryMock, $this->inventoryRepositoryMock);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_get_all_sales(): void
    {
        $paginator = $this->mock(LengthAwarePaginator::class);
        $this->saleRepositoryMock->shouldReceive('getAllPaginated')->once()->andReturn($paginator);

        $result = $this->saleService->getAllSales();
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_get_sale_by_id(): void
    {
        $sale = new Sale(['id' => 1]);
        $this->saleRepositoryMock->shouldReceive('findById')->with(1)->once()->andReturn($sale);

        $result = $this->saleService->getSaleById(1);
        $this->assertSame($sale, $result);
    }



    #[Test]
    public function it_throws_exception_if_sale_not_found_on_update()
    {
        $this->saleRepositoryMock->shouldReceive('findById')->with(999)->andReturn(null);
        $this->expectException(Exception::class);
        $this->saleService->updateSale(999, ['customer_id' => 1]);
    }

    #[Test]
    public function it_throws_exception_if_sale_not_found_on_delete()
    {
        $this->saleRepositoryMock->shouldReceive('findById')->with(999)->andReturn(null);
        $this->expectException(Exception::class);
        $this->saleService->deleteSale(999);
    }



}
