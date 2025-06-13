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
            margin: 0;
            line-height: 1.4;
        }

        .meta-info.note {
            margin-bottom: 10px;
        }

        .meta-info strong {
            display: inline-block;
            width: 100px;
        }

        table.items {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        table.items th,
        table.items td {
            padding: 6px;
            border-bottom: 1px solid #ddd;
        }

        table.items th {
            background-color: #f0f0f0;
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
    @php
        $differenceReasons = [
            'damaged' => 'Rusak',
            'stolen' => 'Dicuri',
            'clerical_error' => 'Kesalahan Administrasi',
            'other' => 'Lainnya',
        ];

        $opnameTypes = [
            'regular' => 'Reguler',
            'audit' => 'Audit',
            'ad_hoc' => 'Ad-hoc',
        ];
    @endphp

    <div class="title">{{ $type_label }}</div>

    @foreach ($transactions as $tx)
        <div class="transaction-block">
            <div class="meta-info"><strong>Kode:</strong> {{ $tx->transaction_code }}</div>
            <div class="meta-info"><strong>Tanggal:</strong>
                {{ optional($tx->transaction_date)->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }}</div>

            @if ($tx->type === 'adjustment')
                <div class="meta-info"><strong>Jenis Opname:</strong> {{ $opnameTypes[$tx->opname_type] ?? '-' }}</div>
                <div class="meta-info"><strong>Alasan:</strong> {{ $differenceReasons[$tx->difference_reason] ?? '-' }}
                </div>
            @else
                <div class="meta-info">
                    <strong>{{ $tx->customer ? 'Customer' : 'Supplier' }}:</strong>
                    {{ $tx->customer->name ?? ($tx->supplier->name ?? '-') }}
                </div>
            @endif

            <div class="meta-info note"><strong>Catatan:</strong> {{ $tx->description ?? '-' }}</div>

            <table class="items">
                <thead>
                    <tr>
                        <th>Nama Barang</th>

                        @if ($tx->type === 'adjustment')
                            <th class="text-right">Stok Sistem</th>
                            <th class="text-right">Stok Aktual</th>
                            <th class="text-right">Selisih</th>
                            <th>Status</th>
                        @else
                            <th class="text-right">Qty</th>
                            <th class="text-right">Harga</th>
                            <th class="text-right">Subtotal</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tx->items as $item)
                        <tr>
                            <td>{{ $item->item->name }} {{ $item->item->brand->name ?? '' }}</td>

                            @if ($tx->type === 'adjustment')
                                <td class="text-right">
                                    {{ number_format($item->system_stock ?? 0, 2, ',', '.') }}/{{ $item->converted_unit_symbol }}
                                </td>
                                <td class="text-right">
                                    {{ number_format($item->converted_qty ?? 0, 2, ',', '.') }}/{{ $item->converted_unit_symbol }}
                                </td>
                                <td class="text-right">
                                    {{ number_format($item->difference ?? 0, 2, ',', '.') }}/{{ $item->converted_unit_symbol }}
                                </td>
                                <td>{{ ucfirst($item->status ?? '-') }}</td>
                            @else
                                <td class="text-right">
                                    {{ number_format($item->converted_qty ?? $item->quantity, 2, ',', '.') }}
                                    / {{ $item->converted_unit_symbol ?? ($item->selectedUnit->symbol ?? '-') }}
                                </td>
                                <td class="text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($tx->type !== 'adjustment')
                <div class="total">Total: Rp {{ number_format($tx->items->sum('subtotal'), 0, ',', '.') }}</div>
            @endif
        </div>
    @endforeach
</body>

</html>
