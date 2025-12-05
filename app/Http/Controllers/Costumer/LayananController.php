<?php

namespace App\Http\Controllers\Costumer;

use App\Http\Controllers\Controller;
use App\Models\Layanan;
use Illuminate\Http\Request;

class LayananController extends Controller
{
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
    public function show(string $id)
    {
        $requestedLayanan = Layanan::with('tipeLayanan')->findOrFail($id);
        return response()->json([
            'data' => $requestedLayanan
        ]);
    }
}
