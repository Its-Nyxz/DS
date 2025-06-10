<?php

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use App\Models\ItemSupplier;
use App\Models\StockTransactionItem;

class StockNotification extends Component
{
    public $lowStockItems = [];
    public $unreadCount = 0;

    protected $listeners = ['loadNotifications' => 'loadLowStockItems'];

    public function mount()
    {
        $this->loadLowStockItems();
    }

    public function loadLowStockItems(): void
    {
        // Ambil semua item
        $this->lowStockItems = Item::all()->filter(function (Item $item) {
            // Ambil stok yang dikonversi dan hitung stoknya
            $stokSekarang = $this->calculateStock($item->id); // Pastikan $item adalah objek Item
            // Tambahkan log untuk memeriksa stok dan min_stock
            logger()->info("Item ID: {$item->id}, Stok Sekarang: {$stokSekarang}, Min Stock: {$item->min_stock}");
            // Bandingkan dengan min_stock untuk memberi notifikasi jika stok lebih rendah atau sama dengan min_stock
            return $stokSekarang <= $item->min_stock;
        });
        // Hitung jumlah notifikasi yang belum dibaca
        $this->unreadCount = $this->lowStockItems->count();
    }

    // Menghitung stok saat ini dengan konversi unit
    public function calculateStock($itemId)
    {
        // Ambil relasi ItemSupplier berdasarkan item_id
        $itemSupplier = ItemSupplier::where('item_id', $itemId)->first();

        // Jika tidak ada relasi ItemSupplier, hentikan perhitungan dan beri log
        if (!$itemSupplier) {
            logger()->warning('ItemSupplier tidak ditemukan untuk item ID: ' . $itemId);
            return 0;  // Kembalikan stok 0 jika ItemSupplier tidak ditemukan
        }


        $stokMasuk = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->where('type', 'in')->where('is_approved', true)
                    ->orWhere('type', 'retur_in');
            })
            ->sum('quantity');

        $stokKeluar = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', function ($q) {
                $q->whereIn('type', ['out', 'retur_out']);
            })
            ->sum('quantity');

        $stokOpname = StockTransactionItem::where('item_id', $itemId)
            ->whereHas('transaction', fn($q) => $q->where('type', 'adjustment'))
            ->orderByDesc('transaction_date')
            ->value('quantity');

        $item = $itemSupplier->item; // Ambil item yang terkait

        // Periksa jika item ada
        if (!$item) {
            logger()->warning('Item tidak ditemukan untuk ItemSupplier ID: ' . $itemSupplier->id);
            return 0; // Kembalikan 0 jika item tidak ditemukan
        }

        // Menghitung faktor konversi dari unit
        $conversionFactor = $this->getConversionFactor($itemSupplier->item_id, $item->unit_id);

        $stokSekarang = isset($stokOpname)
            ? $stokOpname * $conversionFactor
            : ($stokMasuk - $stokKeluar) * $conversionFactor;

        return $stokSekarang < 0 ? 0 : $stokSekarang; // Menghindari stok negatif
    }


    // Mendapatkan faktor konversi dari unit
    protected function getConversionFactor($itemId, $unitId): float
    {
        $itemSupplier = ItemSupplier::with('unitConversions')->find($itemId);
        if (!$itemSupplier || !$unitId) {
            return 1;
        }

        $conversion = $itemSupplier->unitConversions->firstWhere('to_unit_id', $unitId);
        return $conversion ? $conversion->factor : 1;
    }

    public function markAsRead()
    {
        // Setiap kali tombol "Tandai dibaca" diklik, notifikasi dianggap dibaca
        $this->unreadCount = 0;
    }

    public function render()
    {
        logger()->info('Low Stock Items: ', $this->lowStockItems->toArray());
        return view('livewire.stock-notification');
    }
}
