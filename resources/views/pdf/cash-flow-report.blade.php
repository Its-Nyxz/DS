<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 6px;
            border: 1px solid #ccc;
            text-align: right;
        }

        th {
            background-color: #f0f0f0;
            text-align: center;
        }

        td.left {
            text-align: left;
        }
    </style>
</head>

<body>
    <h2>Laporan Arus Kas</h2>
    <p>Periode: {{ $start }} s.d. {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th class="left">Referensi</th>
                <th class="left">Keterangan</th>
                <th>Debit</th>
                <th>Kredit</th>
                <th>Saldo</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($transactions as $tx)
                <tr>
                    <td>{{ $tx['tanggal'] }}</td>
                    <td class="left">{{ $tx['reference'] ?? '-' }}</td>
                    <td class="left">{{ $tx['keterangan'] }}</td>
                    <td>{{ number_format($tx['debit'], 0, ',', '.') }}</td>
                    <td>{{ number_format($tx['kredit'], 0, ',', '.') }}</td>
                    <td>{{ number_format($tx['saldo'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
