<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StockTransaction;

class ReportController extends Controller
{
    public function index(string $type)
    {
        // Validasi tipe
        if (!in_array($type, ['in', 'out', 'retur'])) {
            abort(404);
        }

        // Ambil data sesuai tipe
        $data = match ($type) {
            'in' => StockTransaction::where('type', 'in')->get(),
            'out' => StockTransaction::where('type', 'out')->get(),
            'retur' => StockTransaction::where('type', 'retur')->get(),
        };

        return view('reports.index', [
            'type' => $type,
            'transactions' => $data,
        ]);
    }
}
