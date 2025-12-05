<?php

namespace App\Http\Controllers;

use App\Models\Layanan;
use Illuminate\Http\Request;

class LayananController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page ?? 15;
        $query = Layanan::query()->with('tipeLayanan');
        if ($request->cari) {
            $query->where('nama_layanan', 'like', '%' . $request->cari . '%');
        }
        if ($request->tipe_layanan_id) {
            $query->where('tipe_layanan_id', $request->tipe_layanan_id);
        }
        return response()->json([
            'data' => $query->paginate(15)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            "tipe_layanan_id" => 'required|numeric|exists:tipe_layanans,id',
            "nama_layanan" => 'required|min:3|max:50|unique:layanans,nama_layanan',
            "deskripsi_layanan" => 'nullable|min:3',
            "harga_layanan" => 'required|numeric|min_digits:3|max_digits:7',
            "foto_layanan" => 'required|image|mimes:jpg,png,jpeg|max:2048',
        ]);
        if ($request->hasFile('foto_layanan')) {
            $file = $request->file('foto_layanan');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/layanan/'), $filename);
            $validated['foto_layanan'] = 'uploads/layanan/' . $filename;
        }
        $layanan = Layanan::create($validated);
        return response()->json([
            'message' => 'Layanan created successfully',
            'data' => $layanan
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $requestedLayanan = Layanan::with('tipeLayanan')->findOrFail($id);
        return response()->json([
            'data' => $requestedLayanan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $layanan = Layanan::findOrFail($id);
        $validated = $request->validate([
            "tipe_layanan_id" => 'required|numeric|exists:tipe_layanans,id',
            "nama_layanan" => 'required|min:3|max:50|unique:layanans,nama_layanan,' . $id,
            "deskripsi_layanan" => 'nullable|min:3',
            "harga_layanan" => 'required|numeric',
            "foto_layanan" => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
        ]);
        $validated['foto_layanan'] = $layanan->foto_layanan;
        if ($request->hasFile('foto_layanan')) {
            $file = $request->file('foto_layanan');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/layanan/'), $filename);
            $validated['foto_layanan'] = 'uploads/layanan/' . $filename;
        }
        $layanan->update($validated);
        return response()->json([
            'message' => 'Layanan updated successfully',
            'data' => $layanan
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $layanan = Layanan::findOrFail($id);
        $layanan->delete();
        return response()->json([
            'message' => 'Layanan deleted successfully'
        ]);
    }
}
