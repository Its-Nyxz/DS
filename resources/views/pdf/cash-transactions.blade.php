<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }
    </style>
</head>

<body>
    <h2>{{ $title }}</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Referensi</th>
                <th>Catatan</th>
                <th>Tagihan</th>
                <th>Dibayar</th>
                <th>Sisa</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $tx)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($tx->transaction_date)->format('d/m/Y') }}</td>
                    <td>{{ $tx->reference_number }}</td>
                    <td>{{ $tx->note ?? '-' }}</td>
                    <td>Rp {{ number_format($tx->total, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format($tx->paid, 0, ',', '.') }}</td>
                    <td>
                        @if ($tx->remaining <= 0)
                            Lunas
                        @else
                            Rp {{ number_format($tx->remaining, 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
