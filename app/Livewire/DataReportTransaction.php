<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\StockTransaction;

class DataReportTransaction extends Component
{
    use WithPagination;

    public string $type;
    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $transactions = StockTransaction::with('item')
            ->where('type', $this->type)
            ->when(
                $this->search,
                fn($query) =>
                $query->whereHas(
                    'item',
                    fn($q) =>
                    $q->where('name', 'like', '%' . $this->search . '%')
                )
            )
            ->orderByDesc('created_at')
            ->paginate(10);

        return view('livewire.data-report-transaction', [
            'transactions' => $transactions,
            'type' => $this->type,
        ]);
    }
}
