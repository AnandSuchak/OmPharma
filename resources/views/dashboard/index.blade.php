@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1>Welcome to your Wholesale Business Dashboard</h1>
    <p>This is the default page you will see when you log in.</p>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Suppliers</h5>
                    <p class="card-text">Manage your suppliers.</p>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-primary">Go to Suppliers</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Medicines</h5>
                    <p class="card-text">Manage your medicines.</p>
                    <a href="{{ route('medicines.index') }}" class="btn btn-primary">Go to Medicines</a>
                </div>
            </div>
        </div>
        </div>
@endsection