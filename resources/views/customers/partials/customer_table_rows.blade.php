{{--
This partial view displays the rows for the customer table.
It is used by both the main index page and the AJAX search/pagination responses.
--}}
@if($customers->count() > 0)
    @foreach ($customers as $customer)
        <tr>
            <td>{{ $customer->id }}</td>
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->contact_number }}</td>
            <td>{{ $customer->email }}</td>
            <td>{{ $customer->address }}</td>
            <td>
                {{-- This is where the error was likely happening.
                     We ensure we always pass a valid $customer object to the route(). --}}
                <a href="{{ route('customers.show', $customer) }}" class="btn btn-info btn-sm">View</a>
                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary btn-sm">Edit</a>
                <form action="{{ route('customers.destroy', $customer) }}" method="POST" style="display:inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
    @endforeach
@else
    <tr>
        <td colspan="6" class="text-center">No customers found.</td>
    </tr>
@endif
