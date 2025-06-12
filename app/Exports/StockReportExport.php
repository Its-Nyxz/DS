<?php

namespace App\Exports;

use App\Models\Item;
use App\Models\ItemSupplier;
use App\Models\StockTransactionItem;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use App\Models\StockOpname;

class StockReportExport implements FromCollection, WithHeadings, WithTitle
{
    protected $startDate;
    protected $endDate;

    // Menerima parameter tanggal
    public function __construct($startDate, $endDate)
    {
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * Get the collection of items with stock information.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Item::get()
            ->map(function ($item) {
                // Hitung stok saat ini dengan filter tanggal yang diberikan
                $currentStock = $this->calculateStock($item->id);

                return [
                    'sku'         => $item->sku,
                    'item_name'   => $item->name . ' ' . $item->brand->name,
                    'current_stock' => $currentStock . ' ' . $item->unit->name,
                ];
            });
    }

    /**
     * Calculate the current stock of an item with date filters applied.
     *
     * @param int $itemId
     * @return int
     */
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

    /**
     * Define the headings for the Excel export.
     *
     * @return array
     */
    public function headings(): array
    {
        // Title and Date Filter Range
        $title = 'Laporan Stok Barang';
        $dateRange = 'Tanggal: ' . $this->startDate . ' s/d ' . $this->endDate;

        return [
            [$title], // Title row
            [$dateRange], // Date range row
            ['Kode Barang', 'Nama Barang',  'Stock'] // Column headers
        ];
    }

    /**
     * Define the style for the Excel export.
     *
     * @param Worksheet $sheet
     */
    public function style(Worksheet $sheet)
    {
        // Set header style (bold, centered)
        $sheet->getStyle('A1:C1')->getFont()->setBold(true);
        $sheet->getStyle('A1:C1')->getAlignment()->setHorizontal('center');

        // Set date range style (italic, centered)
        $sheet->getStyle('A2:C2')->getFont()->setItalic(true);
        $sheet->getStyle('A2:C2')->getAlignment()->setHorizontal('center');

        // Set column headers bold and centered
        $sheet->getStyle('A3:C3')->getFont()->setBold(true);
        $sheet->getStyle('A3:C3')->getAlignment()->setHorizontal('center');

        // Set column widths for readability
        $sheet->getColumnDimension('A')->setWidth(20); // Kode Barang
        $sheet->getColumnDimension('B')->setWidth(30); // Nama Barang
        $sheet->getColumnDimension('C')->setWidth(20); // Stock

        // Add borders around the table
        $sheet->getStyle('A4:C' . (count($this->collection()) + 3))
            ->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // Apply background color to the header row
        $sheet->getStyle('A3:C3')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $sheet->getStyle('A3:C3')->getFill()->getStartColor()->setARGB('FFFF99'); // Light Yellow
    }

    /**
     * Set the title for the sheet
     *
     * @return string
     */
    public function title(): string
    {
        return 'Laporan Stok'; // Sheet title
    }
}
