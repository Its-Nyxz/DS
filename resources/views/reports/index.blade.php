<x-layouts.app>
    <h1 class="text-lg font-bold mb-4">
        Laporan {{ strtoupper($type) }}
    </h1>

    <table>
        @foreach ($transactions as $trx)
            <tr>
                <td>{{ $trx->code }}</td>
                <td>{{ $trx->date }}</td>
                <td>{{ $trx->total }}</td>
            </tr>
        @endforeach
    </table>
</x-layouts.app>
