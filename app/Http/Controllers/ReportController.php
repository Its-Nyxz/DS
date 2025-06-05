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


        return view('reports.index', [
            'type' => $type,
        ]);
    }
}
