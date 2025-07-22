@forelse ($suppliers as $supplier)
    <tr>
        <td>{{ $supplier->name }}</td>
        <td>{{ $supplier->phone_number ?? '-' }}</td>
        <td>{{ $supplier->email ?? '-' }}</td>
        <td>{{ $supplier->address ?? '-' }}</td>
        <td class="text-center">
            <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-sm btn-outline-info me-1" title="View">
                <i class="fa fa-eye"></i>
            </a>
            <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                <i class="fa fa-pen-to-square"></i>
            </a>
            <form action="{{ route('suppliers.destroy', $supplier->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this supplier?');">
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
        <td colspan="5" class="text-center text-muted">No suppliers found.</td>
    </tr>
@endforelse
