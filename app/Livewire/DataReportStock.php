<?php

namespace App\Livewire;

use TCPDF;
use Carbon\Carbon;
use App\Models\Item;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ItemSupplier;
use App\Exports\StockReportExport;
use App\Models\StockOpname;
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

        $jumlahMasuk = StockTransactionItem::where('item_id', $item->id)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'in')->where('is_approved', true);
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $jumlahKeluar = StockTransactionItem::where('item_id', $item->id)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'out');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $jumlahReturIn = StockTransactionItem::where('item_id', $item->id)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'retur_in');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $jumlahReturOut = StockTransactionItem::where('item_id', $item->id)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'retur_out');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $penyesuaian = StockOpname::where('item_id', $item->id)
            ->whereHas('stockTransaction', function ($q) {
                $q->where('type', 'adjustment');
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            })->sum('quantity');

        $total = round(max(0, $jumlahMasuk + $jumlahReturIn - $jumlahKeluar - $jumlahReturOut + $penyesuaian), 2);

        $this->selectedItemStockDetails = [[
            'stok_awal' => 0,
            'masuk' => $jumlahMasuk,
            'keluar' => $jumlahKeluar,
            'retur_in' => $jumlahReturIn,
            'retur_out' => $jumlahReturOut,
            'penyesuaian' => $penyesuaian,
            'total' => $total,
        ]];

        // Untuk keperluan dropdown konversi
        $this->availableSuppliers = $suppliers->map(function ($supplier) {
            return [
                'id' => $supplier->supplier->id,
                'name' => $supplier->supplier->name,
            ];
        })->toArray();

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
        // Ambil relasi ItemSupplier berdasarkan item_id
        $itemSupplier = ItemSupplier::where('item_id', $itemId)->first();

        // Jika tidak ada relasi item_supplier, kembalikan stok 0
        if (!$itemSupplier) {
            return 0;
        }

        // Hitung stok masuk (in)
        $stokMasukQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'in') // Hanya untuk transaksi masuk (in)
                    ->where('is_approved', true); // Pastikan sudah disetujui
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            });

        // Hitung stok masuk
        $stokMasuk = $stokMasukQuery->sum('quantity');

        // Hitung retur masuk (retur_in)
        $stokReturInQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'retur_in'); // Hanya untuk retur masuk (retur_in)
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            });

        // Hitung stok retur masuk
        $stokReturIn = $stokReturInQuery->sum('quantity');

        // Hitung stok keluar (out)
        $stokOutQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'out'); // Hanya untuk transaksi keluar (out)
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            });

        // Hitung stok keluar untuk transaksi `out`
        $stokOut = $stokOutQuery->sum('quantity');

        // Hitung retur keluar (retur_out)
        $stokReturOutQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'retur_out'); // Hanya untuk retur keluar (retur_out)
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            });

        // Hitung stok retur keluar
        $stokReturOut = $stokReturOutQuery->sum('quantity');

        // Hitung penyesuaian stok (adjustment)
        $stokOpnameQuery = StockOpname::where('item_id', $itemId)
            ->whereHas('stockTransaction', function ($q) {
                $q->where('type', 'adjustment'); // Penyesuaian stok
                if ($this->startDate && $this->endDate) {
                    [$start, $end] = $this->getDateRange();
                    $q->whereBetween('transaction_date', [$start, $end]);
                }
            });

        // Ambil stok opname terakhir (penyesuaian)
        $stokOpname = $stokOpnameQuery->orderByDesc('transaction_date')->value('quantity');

        // Hitung stok sekarang berdasarkan stok opname terakhir, atau dari transaksi masuk dan keluar
        $stokSekarang = isset($stokOpname) ? $stokOpname : ($stokMasuk + $stokReturIn - $stokOut - $stokReturOut);

        // Pastikan stok tidak negatif dan batasi 2 desimal
        return round(max(0, $stokSekarang), 2); // Membatasi angka desimal menjadi 2
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
        <h1>Data Stock</h1>
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
            function () use ($pdf) {
                $pdf->Output('data_stock.pdf', 'I');
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="data_stock.pdf"'
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
