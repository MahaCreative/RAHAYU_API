<?php

namespace App\Http\Controllers\Costumer;

use App\Http\Controllers\Controller;
use App\Models\TipeKamar;
use Illuminate\Http\Request;

class TipeKamarController extends Controller
{
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
}
