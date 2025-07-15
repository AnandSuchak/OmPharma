<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>💊 Medicine Name</th>
                    <th>📦 Pack</th> {{-- NEW COLUMN --}}
                    <th>📦 Total Quantity</th>
                    <th class="text-center">⚙️ Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inventories as $inventory)
                    <tr>
                        <td>{{ $inventory->medicine->name ?? $inventory->name }}</td> {{-- Added $inventory->name fallback if medicine relation isn't loaded --}}
                        <td>{{ $inventory->medicine->pack ?? $inventory->pack }}</td> {{-- NEW CELL --}}
                        <td>{{ $inventory->total_quantity }}</td>
                        <td class="text-center">
                            <a href="{{ route('inventories.show', $inventory->medicine_id) }}"
                               class="btn btn-sm btn-outline-info" title="View Details">
                                <i class="fa fa-eye me-1"></i> View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted">No inventory records found.</td> {{-- Adjusted colspan --}}
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-3">
            {{ $inventories->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>