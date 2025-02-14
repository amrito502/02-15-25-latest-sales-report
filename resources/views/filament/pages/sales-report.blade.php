<x-filament::page>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
       <!-- DataTables CSS -->
       <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <style>
        .fi-header-heading{
            display: none;
        }
        label{
            margin-bottom: 30px!important;
            display: inline-block!important;
        }
        .paginate_button{
            font-size: 15px!important;
        }
        .paginate_button.current {
            background: #7a00ff;
            color: #fff !important;
            width: 37px!important;
            height: 37px!important;
            border-radius: 50%!important!important;
            text-align: center;
        }


    </style>
    <div class="flex items-center justify-between">
        <h1 style="font-size: 41px;text-transform: uppercase;letter-spacing: 0.5px;word-spacing: 6.6px;color: #36d305;font-family: math;" class="">Sales Report</h1>
        <div>
            <button style="color: #884dc7;border: 1px solid;margin-right: 20px;" wire:click="applyFilter('today')" class="px-4 py-2 bg-blue-500  rounded {{ $filter === 'today' ? 'bg-blue-700' : '' }}">Today</button> |
            <button style="color: #58b7dd;border: 1px solid;margin-right: 20px;margin-left: 20px;" wire:click="applyFilter('this_week')" class="px-4 py-2 bg-blue-500  rounded {{ $filter === 'this_week' ? 'bg-blue-700' : '' }}">This Week</button> |
            <button style="color: #d36f05;border: 1px solid;margin-left: 20px;" wire:click="applyFilter('this_month')" class="px-4 py-2 bg-blue-500  rounded {{ $filter === 'this_month' ? 'bg-blue-700' : '' }}">This Month</button>
        </div>
    </div>

    <table id="sales_report" class="w-full mt-3 border border-collapse border-gray-300 table-auto" style="margin-bottom: 20px;">
        <thead style="background: rebeccapurple;color: #fff;">
            <tr>
                <th class="px-4 py-2 border border-gray-300">Date</th>
                <th class="px-4 py-2 border border-gray-300">Name</th>
                <th class="px-4 py-2 border border-gray-300">Email</th>
                <th class="px-4 py-2 border border-gray-300">Role</th>
                <th class="px-4 py-2 border border-gray-300">Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td class="px-4 py-2 border border-gray-300">{{ $user->created_at->format('Y-m-d') }}</td>
                    <td class="px-4 py-2 border border-gray-300">{{ $user->name }}</td>
                    <td class="px-4 py-2 border border-gray-300">{{ $user->email }}</td>
                    <td class="px-4 py-2 border border-gray-300">{{ $user->role }}</td>
                    <td class="px-4 py-2 border border-gray-300">{{ $user->balance }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="px-4 py-2 text-center border border-gray-300">No sales data available for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

 <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

 <!-- DataTables JS -->
 <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

 <script>
     $(document).ready(function() {
         $('#sales_report').DataTable();
     });
 </script>
</x-filament::page>
