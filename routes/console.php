<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\BookingKamar;
use App\Models\Tamu;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('report:backfill-tamu {--dry-run : Simulasi tanpa simpan data} {--limit=0 : Batasi jumlah booking yang diproses (0 = semua)}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $limit = (int) $this->option('limit');

    $query = BookingKamar::query()
        ->with(['pemesanan.user', 'tamu'])
        ->where(function ($q) {
            $q->whereIn('status_booking', ['checked_in', 'checked_out', 'check_in', 'check_out'])
                ->orWhereNotNull('waktu_checkin')
                ->orWhereNotNull('waktu_checkout');
        })
        ->orderBy('id');

    if ($limit > 0) {
        $query->limit($limit);
    }

    $bookings = $query->get();
    if ($bookings->isEmpty()) {
        $this->info('Tidak ada booking untuk diproses.');
        return self::SUCCESS;
    }

    $created = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($bookings as $booking) {
        $fallbackName = trim((string) ($booking->pemesanan->user->name ?? ''));
        if ($fallbackName === '') {
            $fallbackName = trim((string) ($booking->pemesanan->user->email ?? ''));
        }

        if ($fallbackName === '') {
            $skipped++;
            $this->warn("Skip booking #{$booking->id} ({$booking->kode_booking}) karena nama fallback tidak tersedia.");
            continue;
        }

        $guestTarget = max(1, (int) $booking->jumlah_tamu);
        $tamus = $booking->tamu ?? collect();
        $tamusWithName = $tamus->filter(fn ($t) => trim((string) $t->nama) !== '');
        $updatedInBooking = 0;

        // Create guest rows if empty.
        if ($tamus->count() === 0) {
            for ($i = 1; $i <= $guestTarget; $i++) {
                $name = $i === 1 ? $fallbackName : "{$fallbackName} ({$i})";
                if (!$dryRun) {
                    Tamu::create([
                        'nama' => $name,
                        'nik' => null,
                        'jenis_kelamin' => null,
                        'jenis_identitas' => null,
                        'kamar_id' => $booking->kamar_id,
                        'booking_kamar_id' => $booking->id,
                    ]);
                }
                $created++;
            }
            $this->line("Create {$guestTarget} tamu untuk booking #{$booking->id} ({$booking->kode_booking}).");
            continue;
        }

        // Update only rows with empty names.
        foreach ($tamus as $idx => $tamu) {
            if (trim((string) $tamu->nama) !== '') {
                continue;
            }
            $name = $idx === 0 ? $fallbackName : "{$fallbackName} (" . ($idx + 1) . ")";
            if (!$dryRun) {
                $tamu->nama = $name;
                $tamu->save();
            }
            $updated++;
            $updatedInBooking++;
        }

        // Ensure at least one non-empty name exists.
        if ($tamusWithName->count() === 0 && $updatedInBooking === 0) {
            if (!$dryRun) {
                $first = $tamus->first();
                if ($first) {
                    $first->nama = $fallbackName;
                    $first->save();
                }
            }
            $updated++;
            $updatedInBooking++;
        }
    }

    $mode = $dryRun ? 'DRY RUN' : 'EXECUTE';
    $this->info("[{$mode}] Selesai. Created: {$created}, Updated: {$updated}, Skipped: {$skipped}");

    return self::SUCCESS;
})->purpose('Backfill nama tamu untuk laporan booking dari data user pemesanan');
