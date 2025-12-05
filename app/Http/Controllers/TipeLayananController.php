<?php

namespace App\Http\Controllers;

use App\Models\TipeLayanan;
use Illuminate\Http\Request;

class TipeLayananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TipeLayanan::query();
        if ($request->cari) {
            $query->where('nama_layanan', 'like', '%' . $request->cari . '%');
        }
        $page = $request->page ?? 15;
        return response()->json([
            'data' => $query->get()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "tipe_layanan" => 'required|unique:tipe_layanans,tipe_layanan',
            "deskripsi_layanan" => 'required',
        ]);
        $tipeLayanan = TipeLayanan::create($validated);
        return response()->json([
            'message' => 'Tipe Layanan created successfully',
            'data' => $tipeLayanan
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $query = TipeLayanan::query()->with('layanans')->where('id', $id);
        if ($request->cari) {
            $query->whereHas('layanan', function ($q) use ($request) {
                $q->where('nama_layanan', 'like', '%' . $request->cari . '%');
            });
        }
        $requestedLayanan = $query->first();
        return response()->json([
            'data' => $requestedLayanan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tipeLayanan = TipeLayanan::findOrFail($id);
        $validated = $request->validate([
            "tipe_layanan" => 'required|unique:tipe_layanans,tipe_layanan,' . $id,
            "deskripsi_layanan" => 'required',
        ]);
        $tipeLayanan->update($validated);
        return response()->json([
            'message' => 'Tipe Layanan updated successfully',
            'data' => $tipeLayanan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tipeLayanan = TipeLayanan::findOrFail($id);
        $tipeLayanan->delete();
        return response()->json([
            'message' => 'Tipe Layanan deleted successfully'
        ]);
    }
}
