@extends('layouts.app')

@section('title', 'Medicines')

@section('content')
<div class="card-box">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">💊 All Medicines</h3>
        <a href="{{ route('medicines.create') }}" class="btn btn-primary">
            <i class="fa fa-plus me-1"></i> Create New Medicine
        </a>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success">
            <i class="fa fa-check-circle me-1"></i> {{ $message }}
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover table-bordered mb-0">
                <thead class="table-light">
                    <tr>
                        <th>💊 Name</th>
                        <th>🏭 Company</th>
                        <th>📦 Pack</th>
                        <th>💰 GST</th>
                        <th>🧾 HSN</th>
                        <th style="width: 180px;">⚙️ Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($medicines as $medicine)
                        <tr>
                            <td>{{ $medicine->name }}</td>
                            <td>{{ $medicine->company_name ?? '-' }}</td>
                            <td>{{ $medicine->pack ?? '-' }}</td>
                            <td>
                                @if ($medicine->gst_rate)
                                    <span class="badge bg-success">{{ $medicine->gst_rate }}%</span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $medicine->hsn_code ?? '-' }}</td>
                            <td>
                                <a href="{{ route('medicines.show', $medicine->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
                                    <i class="fa fa-eye"></i>
                                </a>
                                <a href="{{ route('medicines.edit', $medicine->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                <form action="{{ route('medicines.destroy', $medicine->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this medicine?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">No medicines found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection