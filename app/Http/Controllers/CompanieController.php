<?php

namespace App\Http\Controllers;

use App\Models\Companie;
use App\Http\Requests\StoreCompanieRequest;
use App\Http\Requests\UpdateCompanieRequest;

class CompanieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil data perusahaan (hanya 1)
        $company = Companie::firstOrCreate(['id' => 1]);

        return view('companie.index', compact('company'));
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
    public function store(StoreCompanieRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Companie $companie)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Companie $companie)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompanieRequest $request, Companie $companie)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Companie $companie)
    {
        //
    }
}
