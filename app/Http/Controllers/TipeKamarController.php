<?php

namespace App\Http\Controllers;

use App\Models\TipeKamar;
use Illuminate\Http\Request;

class TipeKamarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TipeKamar::query();

        if ($request->cari) {
            $query->where('nama_tipe', 'like', '%' . $request->cari . '%');
        }

        $tipe = $query->get();

        return response()->json([
            'data' => $tipe
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "nama_tipe" => 'required|unique:tipe_kamars,nama_tipe',
            "deskripsi_tipe" => 'nullable',
            "harga_per_malam" => 'required|numeric|min_digits:4|max_digits:8',
            "kapasitas_orang" => 'required|numeric|min:1|max:4',
            "fasilitas_tipe" => 'required',
            "foto_tipe" => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);
        if ($request->hasFile('foto_tipe')) {
            $file = $request->file('foto_tipe');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/tipe_kamar/'), $filename);
            $validated['foto_tipe'] = 'uploads/tipe_kamar/' . $filename;
        }
        $tipeKamar = TipeKamar::create($validated);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, Request $request)
    {
        $query = TipeKamar::query()->with('kamars')->where('id', $id);

        if ($request->cari) {
            $query->whereHas('kamars', function ($q) use ($request) {
                $q->where('nomor_kamar', 'like', '%' . $request->cari . '%');
            });
        }
        if ($request->harga_min) {
            $query->whereHas('kamars', function ($q) use ($request) {
                $q->where('harga_kamar', '>=', $request->harga_min);
            });
        }
        if ($request->harga_max) {
            $query->whereHas('kamars', function ($q) use ($request) {
                $q->where('harga_kamar', '<=', $request->harga_max);
            });
        }

        $tipeKamar = $query->first();
        return response()->json([
            'data' => $tipeKamar
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tipeKamar = TipeKamar::findOrFail($id);
        $validated = $request->validate([
            "nama_tipe" => 'required|unique:tipe_kamars,nama_tipe,' . $id,
            "deskripsi_tipe" => 'nullable',
            "harga_per_malam" => 'required|numeric|min_digits:4|max_digits:8',
            "kapasitas_orang" => 'required|numeric|min:1|max:4',
            "fasilitas_tipe" => 'required',
            "foto_tipe" => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);
        $validated['foto_tipe'] = $tipeKamar->foto_tipe;
        if ($request->hasFile('foto_tipe')) {
            $file = $request->file('foto_tipe');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/tipe_kamar/'), $filename);
            $validated['foto_tipe'] = 'uploads/tipe_kamar/' . $filename;
        }
        $tipeKamar->update($validated);
        return response()->json([
            'message' => 'Tipe Kamar updated successfully',
            'data' => $tipeKamar
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tipeKamar = TipeKamar::findOrFail($id);
        $tipeKamar->delete();
        return response()->json([
            'message' => 'Tipe Kamar deleted successfully'
        ]);
    }
}
