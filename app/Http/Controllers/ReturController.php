<?php

namespace App\Http\Controllers;

use App\Models\Retur;
use App\Http\Requests\StoreReturRequest;
use App\Http\Requests\UpdateReturRequest;

class ReturController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         return view("stock-retur.index");
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
    public function store(StoreReturRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Retur $retur)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Retur $retur)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReturRequest $request, Retur $retur)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Retur $retur)
    {
        //
    }
}
