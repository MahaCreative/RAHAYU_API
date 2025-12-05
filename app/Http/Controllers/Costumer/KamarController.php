<?php

namespace App\Http\Controllers\Costumer;

use App\Http\Controllers\Controller;
use App\Models\Kamar;
use Illuminate\Http\Request;

class KamarController extends Controller
{
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
    public function show(string $id)
    {
        $kamar = Kamar::with('tipeKamar')->where('id', $id)->first();
        return response()->json([
            'data' => $kamar
        ]);
    }
}
