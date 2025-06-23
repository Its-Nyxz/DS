<?php

namespace App\Livewire;

use TCPDF;
use Carbon\Carbon;
use App\Models\Item;
use Livewire\Component;
use App\Models\StockOpname;
use Termwind\Components\Dd;
use App\Models\ItemSupplier;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\StockReportExport;
use Illuminate\Support\Facades\Log;
use App\Models\StockTransactionItem;
use Maatwebsite\Excel\Facades\Excel;

class DataReportStock extends Component
{
    public $search = '';
    public $orderBy = 'created_at';
    public $orderDirection = 'desc';
    public $startDate;
    public $endDate;
    public $showItemDetailModal = false;
    public $selectedItemDetail = [];
    public $selectedItemStockDetails = [];
    public $selectedSupplier = null;
    public $selectedConversionFactor = 1;
    public $availableSuppliers = [];
    public $availableConversions = [];
    public $selectedItemId;


    public function mount()
    {
        // Memformat startDate dan endDate jika sudah ada nilainya
        if ($this->startDate) {
            $this->startDate = Carbon::parse($this->startDate)->format('Y-m-d');
        }

        if ($this->endDate) {
            $this->endDate = Carbon::parse($this->endDate)->format('Y-m-d');
        }
    }

    public function render()
    {
        $itemsWithStock = Item::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->orderBy, $this->orderDirection)
            ->paginate(10) // Menambahkan pagination
            ->through(function ($item) {
                $currentStock = $this->calculateStock($item->id) . ' ' . $item->unit->name;
                $itemNameWithBrand = $item->name . ' ' . $item->brand->name;
                return [
                    'item_name' => $itemNameWithBrand,
                    'sku' => $item->sku,
                    'current_stock' => $currentStock,
                    'img' => $item->img,
                ];
            });

        return view('livewire.data-report-stock', compact('itemsWithStock'));
    }

    public function showItemDetail($sku)
    {
        $item = Item::with('unit', 'brand')->where('sku', $sku)->first();
        $stokAwal = $item->stock_awal;
        if (!$item) {
            $this->selectedItemDetail = [['error' => 'Item tidak ditemukan']];
            $this->showItemDetailModal = true;
            return;
        }

        $suppliers = ItemSupplier::with(['supplier', 'unitConversions.fromUnit', 'unitConversions.toUnit'])
            ->where('item_id', $item->id)
            ->get();

        $this->selectedItemDetail = [[
            'Nama Barang' => $item->name . ' ' . $item->brand->name,
            'Unit Dasar' => $item->unit->name,
        ]];

        // Tanggal range
        [$start, $end] = $this->getDateRange();

        $jumlahMasuk = StockTransactionItem::where('item_id', $item->id)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'in')
                    ->where('is_approved', true)
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereNull('deleted_at')
            )->sum('quantity');

        $jumlahKeluar = StockTransactionItem::where('item_id', $item->id)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'out')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereNull('deleted_at')
            )->sum('quantity');

        $jumlahReturIn = StockTransactionItem::where('item_id', $item->id)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'retur_in')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereNull('deleted_at')
            )->sum('quantity');

        $jumlahReturOut = StockTransactionItem::where('item_id', $item->id)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'retur_out')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereNull('deleted_at')
            )->sum('quantity');

        // Penyesuaian (adjustment) dihitung per status
        $penyesuaian = StockOpname::where('item_id', $item->id)
            ->with('stockTransaction')
            ->whereHas(
                'stockTransaction',
                fn($q) =>
                $q->where('type', 'adjustment')
                    ->whereBetween('transaction_date', [$start, $end])
                    ->whereNull('deleted_at')
            )->get();

        $totalPenyesuaian = $penyesuaian->sum(function ($op) {
            return (float) $op->difference;
        });

        // dd($item->id, $stokAwal, $jumlahMasuk, $jumlahReturIn, $jumlahKeluar, $jumlahReturOut, $totalPenyesuaian);
        $total = round(max(0,  $stokAwal + $jumlahMasuk + $jumlahReturIn - $jumlahKeluar - $jumlahReturOut + $totalPenyesuaian), 2);

        $this->selectedItemStockDetails = [[
            'stok_awal' => $stokAwal, // bisa dihitung jika ingin, misalnya dari data sebelum $start
            'masuk' => $jumlahMasuk,
            'keluar' => $jumlahKeluar,
            'retur_in' => $jumlahReturIn,
            'retur_out' => $jumlahReturOut,
            'penyesuaian' => $totalPenyesuaian,
            'total' => $total,
        ]];

        // Supplier dan konversi
        $this->availableSuppliers = $suppliers->map(fn($supplier) => [
            'id' => $supplier->supplier->id,
            'name' => $supplier->supplier->name,
        ])->toArray();

        $this->selectedSupplier = $this->availableSuppliers[0]['id'] ?? null;
        $this->selectedItemId = $item->id;
        $this->updateAvailableConversions();
        $this->showItemDetailModal = true;
    }

    public function updateAvailableConversions()
    {
        if (!$this->selectedSupplier || !$this->selectedItemId) {
            $this->availableConversions = [];
            $this->selectedConversionFactor = 1;
            return;
        }

        $itemSupplier = ItemSupplier::with(['unitConversions.fromUnit', 'unitConversions.toUnit', 'supplier'])
            ->where('item_id', $this->selectedItemId)
            ->where('supplier_id', $this->selectedSupplier)
            ->first();

        if (!$itemSupplier) {
            $this->availableConversions = [];
            $this->selectedConversionFactor = 1;
            return;
        }

        if ($itemSupplier->unitConversions->isEmpty()) {
            $this->availableConversions = [];
            $this->selectedConversionFactor = 1;
            return;
        }

        $this->availableConversions = $itemSupplier->unitConversions->map(function ($conv) {
            return [
                'label' => $conv->fromUnit->name . ' ke ' . $conv->toUnit->name . ' (' . $conv->factor . ' ' . $conv->toUnit->name . ' / ' . $conv->fromUnit->name . ')',
                'factor' => $conv->factor,
            ];
        })->toArray();

        // $this->selectedConversionFactor = $this->availableConversions[0]['factor'] ?? 1;
    }

    // Menghitung stok saat ini dengan mempertimbangkan tanggal filter
    public function calculateStock($itemId)
    {
        $item = Item::find($itemId);
        if (!$item) return 0;

        $stokMasuk = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'in')->where('is_approved', true)->whereNull('deleted_at');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $stokReturIn = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'retur_in')->whereNull('deleted_at');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $stokKeluar = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'out')->whereNull('deleted_at');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $stokReturOut = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'retur_out')->whereNull('deleted_at');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        // Ambil semua penyesuaian untuk item ini
        $penyesuaian = StockOpname::where('item_id', $itemId)
            ->with('stockTransaction')
            ->whereHas('stockTransaction', function ($q) {
                $q->where('type', 'adjustment')->whereNull('deleted_at');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->get();

        $jumlahPenyesuaian = $penyesuaian->sum(function ($op) {
            return (float) $op->difference;
        });
        // dd($itemId, $item->stock_awal, $stokMasuk, $stokReturIn, $stokKeluar, $stokReturOut, $jumlahPenyesuaian);

        $stokSekarang = $item->stock_awal + $stokMasuk + $stokReturIn - $stokKeluar - $stokReturOut + $jumlahPenyesuaian;

        return round(max(0, $stokSekarang), 2);
    }

    // Export ke Excel
    public function exportExcel()
    {
        // Format the start and end date for the filename
        $startDateFormatted = Carbon::parse($this->startDate)->format('Y-m-d');
        $endDateFormatted = Carbon::parse($this->endDate)->format('Y-m-d');

        // Use the formatted dates in the filename
        return Excel::download(new StockReportExport($this->startDate, $this->endDate), 'Laporan Stok ' . $startDateFormatted . '-' . $endDateFormatted . '.xlsx');
    }

    // Export ke PDF
    public function exportPdf()
    {
        $itemsWithStock = Item::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->orderBy, $this->orderDirection)
            ->get()
            ->map(function ($item) {
                $currentStock = $this->calculateStock($item->id) . ' ' . $item->unit->name;
                $itemNameWithBrand = $item->name . ' ' . $item->brand->name;
                return [
                    'item_name' => $itemNameWithBrand,
                    'sku' => $item->sku,
                    'current_stock' => $currentStock,
                    'img' => $item->img,
                ];
            });

        // Format tanggal
        $start = Carbon::parse($this->startDate)->format('d-m-Y');
        $end = Carbon::parse($this->endDate)->format('d-m-Y');
        $filename = "data_stock_{$start}_{$end}.pdf";
        $startFormatted = Carbon::parse($this->startDate)->translatedFormat('d F Y');
        $endFormatted = Carbon::parse($this->endDate)->translatedFormat('d F Y');

        // Menyiapkan HTML untuk PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
        <title>Data Stock</title>
        <style>
            table {
                width: 100%;
                border-collapse: collapse;
            }

            table, th, td {
                border: 1px solid black;
            }

            th, td {
                padding: 8px;
                text-align: left;
            }
        </style>
        </head>
        <body>
        <h1>Data Stock  (' . $startFormatted . ' s/d ' . $endFormatted . ')</h1>
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>';

        // Menambahkan data item ke dalam HTML
        foreach ($itemsWithStock as $item) {
            $html .= '<tr>';
            $html .= '<td>' . $item['sku'] . '</td>';
            $html .= '<td>' . $item['item_name'] . '</td>';
            $html .= '<td>' . $item['current_stock'] . '</td>';
            $html .= '</tr>';
        }

        $html .= '
            </tbody>
        </table>
        </body>
        </html>';

        // Membuat instance TCPDF
        $pdf = new TCPDF();

        // Menambahkan halaman
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);

        // Menulis HTML ke PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        // Mengirim PDF ke browser
        return response()->stream(
            fn() => $pdf->Output($filename, 'I'),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "inline; filename=\"$filename\""
            ]
        );
    }

    protected function getDateRange()
    {
        return [
            Carbon::parse($this->startDate)->startOfDay(),
            Carbon::parse($this->endDate)->endOfDay(),
        ];
    }
}
