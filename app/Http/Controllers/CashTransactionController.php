<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Http\Requests\StoreCashTransactionRequest;
use App\Http\Requests\UpdateCashTransactionRequest;

class CashTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($type)
    {
        if (!in_array($type, ['utang', 'piutang', 'arus'])) {
            abort(404);
        }

        return view('cashtransactions.index', compact('type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCashTransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CashTransaction $cashTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CashTransaction $cashTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCashTransactionRequest $request, CashTransaction $cashTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CashTransaction $cashTransaction)
    {
        //
    }
}
