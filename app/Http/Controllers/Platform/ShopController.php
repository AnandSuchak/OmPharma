<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class ShopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shops = Shop::latest()->paginate(10);
        return view('platform.shops.index', compact('shops'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('platform.shops.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_name' => ['required', 'string', 'max:255'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:'.User::class.',email'],
            'admin_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($request) {
            // Create the Shop
            $shop = Shop::create([
                'name' => $request->shop_name,
            ]);

            // Create the Super Admin for that shop
            $shop->users()->create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'role' => 'super-admin', // Assign the super-admin role
            ]);
        });

        return redirect()->route('platform.shops.index')->with('success', 'Shop and Super Admin created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Shop $shop)
    {
        // Eager load the users relationship
        $shop->load('users');
        return view('platform.shops.show', compact('shop'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Shop $shop)
    {
        return view('platform.shops.edit', compact('shop'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Shop $shop)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:active,suspended,trial'],
        ]);

        $shop->update($request->only('name', 'status'));

        return redirect()->route('platform.shops.index')->with('success', 'Shop updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Shop $shop)
    {
        // Deleting a shop will also delete its users due to the onDelete('cascade') in the migration.
        $shop->delete();
        return redirect()->route('platform.shops.index')->with('success', 'Shop deleted successfully.');
    }
}
