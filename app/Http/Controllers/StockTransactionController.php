<?php

namespace App\Http\Controllers;

use App\Models\StockTransaction;
use App\Http\Requests\StoreStockTransactionRequest;
use App\Http\Requests\UpdateStockTransactionRequest;

class StockTransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view("report-stock.index");
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
    public function store(StoreStockTransactionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(StockTransaction $stockTransaction)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(StockTransaction $stockTransaction)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStockTransactionRequest $request, StockTransaction $stockTransaction)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(StockTransaction $stockTransaction)
    {
        //
    }
}
