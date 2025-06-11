<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>{{ $type_label }}</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 11px;
            margin: 10px;
        }

        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .transaction-block {
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #ccc;
        }

        .meta-info {
            margin-bottom: 5px;
        }

        .meta-info strong {
            display: inline-block;
            width: 90px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        table.items th,
        table.items td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }

        table.items th {
            background-color: #f0f0f0;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .total {
            text-align: right;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>

<body>

    <div class="title">{{ $type_label }}</div>

    @foreach ($transactions as $tx)
        <div class="transaction-block">
            <div class="meta-info"><strong>Kode:</strong> {{ $tx->transaction_code }}</div>
            <div class="meta-info"><strong>Tanggal:</strong>
                {{ optional($tx->transaction_date)->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }}
                <div class="meta-info">
                    <strong>{{ $tx->customer ? 'Customer' : 'Supplier' }}:</strong>
                    {{ $tx->customer->name ?? ($tx->supplier->name ?? '-') }}
                </div>
                <div class="meta-info"><strong>Catatan:</strong> {{ $tx->description ?? '-' }}</div>

                <table class="items">
                    <thead>
                        <tr>
                            <th>Nama Barang</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tx->items as $item)
                            @php
                                $unitSymbol = $item->selectedUnit->symbol ?? ($item->item->unit->symbol ?? '-');
                                $convertedQty = $item->selected_unit_id
                                    ? optional(
                                            $item->itemSupplier->unitConversions->firstWhere(
                                                'to_unit_id',
                                                $item->selected_unit_id,
                                            ),
                                        )->factor * $item->quantity
                                    : $item->quantity;
                            @endphp
                            <tr>
                                <td>{{ $item->item->name }}</td>
                                <td class="text-right">{{ number_format($convertedQty, 2, ',', '.') }} /
                                    {{ $unitSymbol }}
                                </td>
                                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="total">Total: Rp {{ number_format($tx->items->sum('subtotal'), 0, ',', '.') }}</div>
            </div>
    @endforeach

</body>

</html>
