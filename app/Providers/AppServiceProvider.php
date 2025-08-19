<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\CustomerRepositoryInterface;
use App\Interfaces\InventoryRepositoryInterface;
use App\Interfaces\MedicineRepositoryInterface;
use App\Interfaces\PurchaseBillRepositoryInterface;
use App\Interfaces\SaleRepositoryInterface;
use App\Interfaces\SupplierRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\InventoryRepository;
use App\Repositories\MedicineRepository;
use App\Repositories\PurchaseBillRepository;
use App\Repositories\SaleRepository;
use App\Repositories\SupplierRepository;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SupplierRepositoryInterface::class, SupplierRepository::class);
        $this->app->bind(MedicineRepositoryInterface::class, MedicineRepository::class); 
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(PurchaseBillRepositoryInterface::class, PurchaseBillRepository::class);
        $this->app->bind(InventoryRepositoryInterface::class, InventoryRepository::class);
        $this->app->bind(SaleRepositoryInterface::class, SaleRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
         Paginator::useBootstrapFive(); 
    }
}
