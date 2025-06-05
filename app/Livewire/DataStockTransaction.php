<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\StockTransaction;

class DataStockTransaction extends Component
{
    use WithPagination;

    public string $type;

    public $search = '';

    public function mount($type)
    {
        if (!in_array($type, ['in', 'out', 'retur', 'opname'])) {
            abort(404);
        }

        $this->type = $type;
    }

    public function render()
    {
        $transactions = StockTransaction::with('items')
            ->where('type', $this->type)
            ->whereHas('items', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.data-stock-transaction', [
            'transactions' => $transactions,
        ]);
    }

    public function updatingSearch()
    {
        $this->resetPage(); // jika pakai pagination
    }
}
