@php
    $typeLabels = [
        'in' => 'Transaksi Masuk',
        'out' => 'Transaksi Keluar',
        'retur_in' => 'Retur dari Customer',
        'retur_out' => 'Retur ke Supplier',
        'opname' => 'Stock Opname',
    ];
    $labelType = $typeLabels[$tx->type] ?? strtoupper($tx->type);
@endphp
<h2 style="text-align:center; font-family: sans-serif;">{{ $labelType }}</h2>
<p style="margin: 4px 0;"><strong>Kode:</strong> {{ $tx->transaction_code }}</p>
<p style="margin: 4px 0;"><strong>Tanggal:</strong>
    {{ optional($tx->transaction_date)->format('d/m/Y H:i') ?? $tx->created_at->format('d/m/Y H:i') }}</p>
<p style="margin: 4px 0;"><strong>{{ $tx->customer ? 'Customer' : 'Supplier' }}:</strong>
    {{ $tx->customer->name ?? ($tx->supplier->name ?? '-') }}</p>
<p style="margin: 4px 0;"><strong>Catatan:</strong> {{ $tx->description ?? '-' }}</p>

<hr style="margin: 10px 0;">

<table width="100%" cellpadding="4" cellspacing="0" style="font-family: sans-serif; font-size: 13px;">
    <thead>
        <tr style="border-bottom: 1px solid #000;">
            <th align="left">Barang</th>
            <th align="right">Qty</th>
            <th align="right">Harga</th>
            <th align="right">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tx->items as $item)
            <tr>
                <td>{{ $item->item->name }} {{ $item->item->brand->name }}</td>
                <td align="right">
                    {{ number_format($item->converted_qty, 2, ',', '.') }}/{{ $item->converted_unit_symbol }}</td>

                <td align="right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                <td align="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<hr style="margin: 10px 0;">

<h3 style="text-align:right; font-family: sans-serif;">
    Total: Rp {{ number_format($tx->items->sum('subtotal'), 0, ',', '.') }}
</h3>
