<?php

namespace App\Livewire;

use App\Models\Item;
use Livewire\Component;
use App\Models\StockOpname;
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
            // logger()->info("Item ID: {$item->id}, Stok Sekarang: {$stokSekarang}, Min Stock: {$item->min_stock}");
            // Bandingkan dengan min_stock untuk memberi notifikasi jika stok lebih rendah atau sama dengan min_stock
            return $stokSekarang <= $item->min_stock;
        });
        // Hitung jumlah notifikasi yang belum dibaca
        $this->unreadCount = $this->lowStockItems->count();
    }

    // Menghitung stok saat ini dengan konversi unit
    public function calculateStock($itemId)
    {
        $item = Item::with('unit')->find($itemId);
        if (!$item) {
            return 0;
        }

        // Ambil salah satu relasi ItemSupplier (karena untuk notifikasi stok, satu saja cukup)
        $itemSupplier = ItemSupplier::where('item_id', $itemId)->first();
        if (!$itemSupplier) {
            return 0;
        }

        // Hitung stok masuk
        $stokMasuk = StockTransactionItem::where('item_id', $itemId)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'in')->where('is_approved', true)
            )->sum('quantity');

        // Retur in
        $stokReturIn = StockTransactionItem::where('item_id', $itemId)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'retur_in')
            )->sum('quantity');

        // Stok keluar
        $stokKeluar = StockTransactionItem::where('item_id', $itemId)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'out')
            )->sum('quantity');

        // Retur out
        $stokReturOut = StockTransactionItem::where('item_id', $itemId)
            ->whereHas(
                'transaction',
                fn($q) =>
                $q->where('type', 'retur_out')
            )->sum('quantity');

        // Ambil semua penyesuaian dari StockOpname
        $penyesuaian = StockOpname::where('item_id', $itemId)
            ->with('stockTransaction')
            ->whereHas(
                'stockTransaction',
                fn($q) =>
                $q->where('type', 'adjustment')
            )->get();

        $jumlahPenyesuaian = $penyesuaian->sum(function ($op) {
            $diff = $op->difference ?? $op->quantity; // fallback jika difference null
            return match ($op->status) {
                'tambah' => $diff,
                'penyusutan' => $diff, // assumed negative if properly stored
                default => 0,
            };
        });

        // Faktor konversi jika diperlukan
        $conversionFactor = $this->getConversionFactor($itemSupplier->item_id, $item->unit_id);

        $stokSekarang = ($stokMasuk + $stokReturIn - $stokKeluar - $stokReturOut + $jumlahPenyesuaian) * $conversionFactor;

        return round(max(0, $stokSekarang), 2);
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
        // logger()->info('Low Stock Items: ', $this->lowStockItems->toArray());
        return view('livewire.stock-notification');
    }
}
