{{-- resources/views/medicines/partials/medicine_table_rows.blade.php --}}

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
    <tr id="no_results_row">
        <td colspan="6" class="text-center text-muted">No medicines found.</td>
    </tr>
@endforelse