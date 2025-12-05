<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use Illuminate\Http\Request;

class KamarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $page = $request->page ?? 15;
        $query = Kamar::query()->with('tipeKamar');
        if ($request->cari) {
            $query->where('nomor_kamar', 'like', '%' . $request->cari . '%');
        }
        if ($request->tipe_kamar_id) {
            $query->where('tipe_kamar_id', $request->tipe_kamar_id);
        }
        if ($request->harga_min) {
            $query->where('harga_kamar', '>=', $request->harga_min);
        }
        if ($request->harga_max) {
            $query->where('harga_kamar', '<=', $request->harga_max);
        }
        $kamar = $query->get();
        return response()->json([
            'data' => $kamar
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            "tipe_kamar_id" => 'required|integer|exists:tipe_kamars,id',
            "nomor_kamar" => 'required|unique:kamars,nomor_kamar',
            "status_kamar" => 'required|in:Tersedia,Tidak Tersedia,Dibooking,Dipakai,Cleaning,Maintenance',
            "lantai_kamar" => 'required|integer|min:1|max:10',
            "foto_kamar" => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            "catatan_kamar" => 'nullable',
            "harga_kamar" => 'required|numeric|min_digits:4|max_digits:8',
            "kapasitas_kamar" => 'required|numeric|min:1|max:10',
            "fasilitas_kamar" => 'nullable|array',
            "kebijakan_kamar" => 'nullable',
            'foto_lainnya' => 'nullable|array',
            'foto_lainnya.*' => 'image|mimes:jpg,png,jpeg|max:2048',
        ]);

        // Start with validated data, then add file paths to $data
        $data = $validated;

        if ($request->hasFile('foto_kamar')) {
            // Optional: hapus file lama jika ada
            $file = $request->file('foto_kamar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $uploadDir = public_path('uploads/kamar/');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $file->move($uploadDir, $filename);
            $data['foto_kamar'] = 'uploads/kamar/' . $filename;
        }

        if ($request->hasFile('foto_lainnya')) {
            // Optional: hapus file lama jika ada
            $data['foto_lainnya'] = [];
            $uploadDir = public_path('uploads/kamar/');
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach ($request->file('foto_lainnya') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move($uploadDir, $filename);
                $data['foto_lainnya'][] = 'uploads/kamar/' . $filename;
            }
        }

        $kamar = Kamar::create($data);

        return response()->json([
            'message' => 'Kamar berhasil ditambahkan',
            'data' => $kamar,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kamar = Kamar::with('tipeKamar', 'bookingKamars')->where('id', $id)->first();
        return response()->json([
            'data' => $kamar
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kamar = Kamar::findOrFail($id);

        $validated = $request->validate([
            "tipe_kamar_id" => 'required|integer|exists:tipe_kamars,id',
            "nomor_kamar" => 'required|unique:kamars,nomor_kamar,' . $kamar->id,
            "status_kamar" => 'required|in:Tersedia,Tidak Tersedia,Dibooking,Dipakai,Cleaning,Maintenance',
            "lantai_kamar" => 'required|integer|min:1|max:10',
            "foto_kamar" => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            "catatan_kamar" => 'nullable',
            "harga_kamar" => 'required|numeric',
            "kapasitas_kamar" => 'required|numeric|min:1|max:10',
            "fasilitas_kamar" => 'nullable|array',
            "kebijakan_kamar" => 'nullable',
            'foto_lainnya' => 'nullable|array',
            'foto_lainnya.*' => 'image|mimes:jpg,png,jpeg|max:2048',
        ]);


        if ($request->hasFile('foto_kamar')) {
            // Optional: hapus file lama jika ada
            if ($kamar->foto_kamar && file_exists(public_path($kamar->foto_kamar))) {
                unlink(public_path($kamar->foto_kamar));
            }

            $file = $request->file('foto_kamar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('uploads/kamar/'), $filename);
            $validated['foto_kamar'] = 'uploads/kamar/' . $filename;
        }

        if ($request->hasFile('foto_lainnya')) {
            // Optional: hapus file lama jika ada
            if ($kamar->foto_lainnya && is_array($kamar->foto_lainnya)) {
                foreach ($kamar->foto_lainnya as $oldFile) {
                    if (file_exists(public_path($oldFile))) {
                        unlink(public_path($oldFile));
                    }
                }
            }

            $validated['foto_lainnya'] = [];
            foreach ($request->file('foto_lainnya') as $file) {
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/kamar/'), $filename);
                $validated['foto_lainnya'][] = 'uploads/kamar/' . $filename;
            }
        }

        $kamar->update($validated);

        return response()->json([
            'message' => 'Data kamar berhasil diperbarui',
            'data' => $kamar,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kamar = Kamar::findOrFail($id);
        $kamar->delete();
        return response()->json([
            'message' => 'Kamar deleted successfully'
        ]);
    }
}
