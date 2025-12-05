<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_number ?? $pemesanan->kode_pemesanan ?? 'Invoice' }}</title>
    <style>
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #333;
        }

        .container {
            width: 100%;
            padding: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hotel {
            font-weight: 700;
            font-size: 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        th,
        td {
            padding: 8px 6px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .total-row {
            font-weight: 700;
        }

        .watermark {
            position: fixed;
            left: 0;
            right: 0;
            top: 40%;
            text-align: center;
            opacity: 0.12;
            font-size: 72px;
            transform: rotate(-30deg);
            color: #000;
            z-index: 0;
        }

        .meta {
            margin-top: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        @if(isset($pemesanan) && ($pemesanan->status_pembayaran === 'lunas' || ($invoice && $invoice->jumlah_bayar >= $invoice->total_amount)))
        <div class="watermark">LUNAS</div>
        @endif

        <div class="header">
            <div>
                <div class="hotel">{{ config('app.name', 'Hotel') }}</div>
                <div>{{ $hotel_address ?? '' }}</div>
            </div>
            <div class="meta">
                <div>Invoice: <strong>{{ $invoice->invoice_number ?? '-' }}</strong></div>
                <div>Order: <strong>{{ $pemesanan->kode_pemesanan ?? '-' }}</strong></div>
                <div>Date: <strong>{{ \\Carbon\\Carbon::parse($invoice->created_at ?? $pemesanan->created_at)->format('Y-m-d H:i') }}</strong></div>
            </div>
        </div>

        <h3>Items</h3>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Qty</th>
                    <th class="right">Unit</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                @php $i = 1; $grand = 0; @endphp
                @foreach($pemesanan->bookingKamars ?? [] as $bk)
                @php
                $line = $bk->harga_total ?? ($bk->harga * ($bk->durasi ?? 1));
                $grand += $line;
                @endphp
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>Booking Kamar: {{ $bk->kamar->nama ?? ($bk->kamar->nomor_kamar ?? 'Kamar') }}<br>
                        Check-in: {{ $bk->checkin ?? '-' }} — Check-out: {{ $bk->checkout ?? '-' }}</td>
                    <td>{{ $bk->jumlah ?? 1 }}</td>
                    <td class="right">{{ number_format($bk->harga ?? 0, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($line, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                @foreach($pemesanan->pesananLayanans ?? [] as $pl)
                @php $line = ($pl->harga ?? 0) * ($pl->jumlah ?? 1); $grand += $line; @endphp
                <tr>
                    <td>{{ $i++ }}</td>
                    <td>Layanan: {{ $pl->layanan->nama ?? $pl->nama }}</td>
                    <td>{{ $pl->jumlah ?? 1 }}</td>
                    <td class="right">{{ number_format($pl->harga ?? 0, 0, ',', '.') }}</td>
                    <td class="right">{{ number_format($line, 0, ',', '.') }}</td>
                </tr>
                @endforeach

            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="right">Subtotal</td>
                    <td class="right">{{ number_format($grand, 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" class="right">Jumlah Bayar</td>
                    <td class="right">{{ number_format($invoice->jumlah_bayar ?? ($pemesanan->jumlah_bayar ?? 0), 0, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td colspan="4" class="right">Sisa Bayar</td>
                    <td class="right">{{ number_format(($invoice->total_amount ?? $grand) - ($invoice->jumlah_bayar ?? ($pemesanan->jumlah_bayar ?? 0)), 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <div style="margin-top:18px;">
            <div>Catatan: {{ $pemesanan->catatan_pemesanan ?? '-' }}</div>
        </div>
    </div>
</body>

</html>