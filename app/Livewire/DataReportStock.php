<?php

namespace App\Livewire;

use TCPDF;
use Carbon\Carbon;
use App\Models\Item;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ItemSupplier;
use App\Exports\StockReportExport;
use App\Models\StockTransactionItem;
use Maatwebsite\Excel\Facades\Excel;

class DataReportStock extends Component
{
    public $search = '';
    public $orderBy = 'created_at';
    public $orderDirection = 'desc';
    public $startDate;
    public $endDate;

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


    // Menghitung stok saat ini dengan mempertimbangkan tanggal filter
    public function calculateStock($itemId)
    {
        // Ambil relasi ItemSupplier berdasarkan item_id
        $itemSupplier = ItemSupplier::where('item_id', $itemId)->first();

        // Jika tidak ada relasi item_supplier, kembalikan stok 0
        if (!$itemSupplier) {
            return 0;
        }

        // Siapkan query untuk transaksi stok masuk
        $stokMasukQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('type', ['in', 'retur_in'])
                    ->where('is_approved', true);

                // Filter berdasarkan tanggal jika ada
                if ($this->startDate && $this->endDate) {
                    $q->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
                }
            });

        // Hitung stok masuk
        $stokMasuk = $stokMasukQuery->sum('quantity');

        // Siapkan query untuk transaksi stok keluar
        $stokKeluarQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('type', ['out', 'retur_out']);

                // Filter berdasarkan tanggal jika ada
                if ($this->startDate && $this->endDate) {
                    $q->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
                }
            });

        // Hitung stok keluar
        $stokKeluar = $stokKeluarQuery->sum('quantity');

        // Hitung stok opname (penyesuaian) dengan filter tanggal jika ada
        $stokOpnameQuery = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'adjustment');

                // Filter berdasarkan tanggal jika ada
                if ($this->startDate && $this->endDate) {
                    $q->whereBetween('transaction_date', [$this->startDate, $this->endDate]);
                }
            });


        // Ambil stok opname terakhir (penyesuaian)
        $stokOpname = $stokOpnameQuery->orderByDesc('transaction_date')->value('quantity');
        // Hitung stok sekarang
        $stokSekarang = isset($stokOpname) ? $stokOpname : ($stokMasuk - $stokKeluar);

        // Pastikan stok tidak negatif
        return max(0, $stokSekarang);
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
}
