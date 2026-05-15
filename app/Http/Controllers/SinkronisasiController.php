<?php

namespace App\Http\Controllers;

use App\Models\DataKunjungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SinkronisasiController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CHECK KONEKSI
    |--------------------------------------------------------------------------
    */
    public function check()
    {
        try {
            DB::connection('sipirman')->getPdo();

            $totalPenitip = DB::connection('sipirman')
                ->table('penitip')
                ->count();

            return response()->json([
                'success'       => true,
                'online'        => true,
                'total_penitip' => $totalPenitip,
                'message'       => 'Database SIPIRMAN terhubung',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'online'  => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW SINKRONISASI
    |--------------------------------------------------------------------------
    */
    public function preview()
    {
        try {
            $totalKunjungan = DataKunjungan::count();

            $totalPenitip = DB::connection('sipirman')
                ->table('penitip')
                ->count();

            $belumSync = 0;

            DataKunjungan::select('no_identitas')
                ->whereNotNull('no_identitas')
                ->chunk(500, function ($rows) use (&$belumSync) {
                    $niks = $rows->pluck('no_identitas')->toArray();

                    $exists = DB::connection('sipirman')
                        ->table('penitip')
                        ->whereIn('nik', $niks)
                        ->pluck('nik')
                        ->toArray();

                    foreach ($niks as $nik) {
                        if (!in_array($nik, $exists)) {
                            $belumSync++;
                        }
                    }
                });

            $fotoKtpKosong = DataKunjungan::whereNull('foto_ktp')
                ->orWhere('foto_ktp', '')
                ->count();

            $fotoDiriKosong = DataKunjungan::whereNull('foto_diri')
                ->orWhere('foto_diri', '')
                ->count();

            return response()->json([
                'success'                 => true,
                'total_kunjungan'         => $totalKunjungan,
                'total_penitip'           => $totalPenitip,
                'perlu_sync'              => $belumSync,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | RUN SINKRONISASI DUA ARAH
    |--------------------------------------------------------------------------
    */
    public function run()
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '1024M');
            $previewFoto = [];
            $start = microtime(true);
            $logs  = [];
            $stats = [
                'insert_sipirman'  => 0,
                'insert_kunjungan' => 0,
                'update_sipirman'  => 0,
                'update_kunjungan' => 0,
                'skip'             => 0,
                'error'            => 0,
                'update_foto_ktp' => 0,
                'update_foto_diri' => 0,
                'detail_foto_ktp'  => [],
                'detail_foto_diri' => [],

            ];


            $this->addLog($logs, 'info', 'Memulai sinkronisasi database...');

            DB::connection('sipirman')->getPdo();
            $this->addLog($logs, 'success', 'Koneksi SIPIRMAN berhasil.');

            $this->addLog($logs, 'info', 'Memuat data SIPIRMAN ke memori...');

            $penitipRows = DB::connection('sipirman')
                ->table('penitip')
                ->select('id', 'nik', 'nama', 'hp', 'nama_wbp', 'jadwal_kunjungan', 'foto', 'foto_ktp')
                ->whereNotNull('nik')
                ->get();

            $penitipByNik = [];
            foreach ($penitipRows as $p) {
                $nik = trim($p->nik ?? '');
                if ($nik !== '') {
                    $penitipByNik[$nik] = $p;
                }
            }

            $this->addLog($logs, 'info', count($penitipByNik) . ' data penitip SIPIRMAN dimuat.');
            $this->addLog($logs, 'info', 'Memuat index DataKunjungan ke memori...');

            $kunjunganRows = DataKunjungan::select(
                'id',
                'no_identitas',
                'wbp',
                'pengunjung',
                'catatan',
                'waktu_kunjungan',
                'foto_ktp',
                'foto_diri'
            )
                ->whereNotNull('no_identitas')
                ->get();

            $kunjunganByNik = [];
            foreach ($kunjunganRows as $k) {
                $nik = trim($k->no_identitas ?? '');
                if ($nik !== '') {
                    $kunjunganByNik[$nik] = $k;
                }
            }

            $this->addLog($logs, 'info', count($kunjunganByNik) . ' data kunjungan dimuat.');
            $this->addLog($logs, 'info', 'Sinkronisasi SIPIRMAN → DATA KUNJUNGAN...');

            $insertKunjunganBatch = [];

            foreach ($penitipRows as $p) {
                try {
                    $nik = trim($p->nik ?? '');
                    if ($nik === '') continue;

                    $tanggal = $this->formatTanggal($p->jadwal_kunjungan ?? null);
                    $namaWbp = $this->cleanNamaWbp($p->nama_wbp ?? null);

                    $kunjungan = $kunjunganByNik[$nik] ?? null;

                    if (!$kunjungan) {

                        $insertKunjunganBatch[] = [
                            'no'              => mt_rand(100000, 999999),
                            'wbp'             => $namaWbp,
                            'pengunjung'      => $p->nama   ?? null,
                            'no_identitas'    => $nik,
                            'catatan'         => $p->hp      ?? null,
                            'waktu_kunjungan' => $tanggal,
                            'foto_ktp'        => $p->foto_ktp ?? null,
                            'foto_diri'       => $p->foto     ?? null,
                        ];

                        $kunjunganByNik[$nik] = (object) end($insertKunjunganBatch);
                        $stats['insert_kunjungan']++;

                        $this->addLog(
                            $logs,
                            'info',
                            "INSERT DataKunjungan | NIK: {$nik} | Nama: " . ($p->nama ?? '-') . " | WBP: {$namaWbp} | Tanggal: {$tanggal}"
                        );
                    } else {

                        $update = [];

                        $rawWbp = method_exists($kunjungan, 'getRawOriginal')
                            ? $kunjungan->getRawOriginal('wbp')
                            : ($kunjungan->wbp ?? null);

                        if (empty($rawWbp)                 && !empty($namaWbp))    $update['wbp']             = $namaWbp;
                        if (empty($kunjungan->foto_ktp)    && !empty($p->foto_ktp)) {
                            $update['foto_ktp']     = $p->foto_ktp;
                            $stats['update_foto_ktp']++;
                        }
                        if (empty($kunjungan->foto_diri)   && !empty($p->foto)) {
                            $update['foto_diri']    = $p->foto;
                            $stats['update_foto_diri']++;
                        }
                        if (empty($kunjungan->catatan)     && !empty($p->hp))       $update['catatan']         = $p->hp;
                        if (empty($kunjungan->pengunjung)  && !empty($p->nama))     $update['pengunjung']      = $p->nama;

                        $rawTgl = method_exists($kunjungan, 'getRawOriginal')
                            ? $kunjungan->getRawOriginal('waktu_kunjungan')
                            : ($kunjungan->waktu_kunjungan ?? null);
                        if (empty($rawTgl) && !empty($tanggal)) $update['waktu_kunjungan'] = $tanggal;

                        if (!empty($update)) {
                            DataKunjungan::where('id', $kunjungan->id)->update($update);
                            $stats['update_kunjungan']++;

                            $updatedFields = implode(', ', array_keys($update));
                            $this->addLog(
                                $logs,
                                'info',
                                "UPDATE DataKunjungan (NIK: {$nik}, WBP: " . ($kunjungan->wbp ?? '-') . ") | Field: {$updatedFields}"
                            );
                        } else {
                            $stats['skip']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $stats['error']++;
                    $this->addLog(
                        $logs,
                        'error',
                        'SIPIRMAN→SDP [' . ($p->nik ?? '-') . ']: ' . $e->getMessage() . ' | line:' . $e->getLine()
                    );
                    Log::error('SIPIRMAN→SDP error', ['nik' => $p->nik ?? '-', 'error' => $e->getMessage()]);
                }
            }

            if (!empty($insertKunjunganBatch)) {
                foreach (array_chunk($insertKunjunganBatch, 500) as $chunk) {
                    DataKunjungan::insert($chunk);
                }
                $this->addLog(
                    $logs,
                    'success',
                    count($insertKunjunganBatch) . ' data baru ditambahkan ke DataKunjungan.'
                );
            }

            $this->addLog($logs, 'info', 'Sinkronisasi SIPIRMAN → DATA KUNJUNGAN...');

            $insertKunjunganBatch = [];

            foreach ($penitipRows as $p) {
                try {
                    $nik = trim($p->nik ?? '');

                    if ($nik === '') {
                        continue;
                    }

                    $tanggal = $this->formatTanggal($p->jadwal_kunjungan ?? null);

                    $namaWbp = $this->cleanNamaWbp($p->nama_wbp ?? null);

                    $kunjungan = $kunjunganByNik[$nik] ?? null;

                    if (!$kunjungan) {
                        $insertKunjunganBatch[] = [
                            'no'              => mt_rand(100000, 999999),
                            'wbp'             => $namaWbp,
                            'pengunjung'      => $p->nama ?? null,
                            'no_identitas'    => $nik,
                            'catatan'         => $p->hp ?? null,
                            'waktu_kunjungan' => $tanggal,
                            'foto_ktp'        => $p->foto_ktp ?? null,
                            'foto_diri'       => $p->foto ?? null,
                        ];

                        $kunjunganByNik[$nik] = (object) end($insertKunjunganBatch);

                        $stats['insert_kunjungan']++;
                        $this->addLog(
                            $logs,
                            'info',
                            "INSERT DataKunjungan | NIK: {$nik} | Nama: " . ($p->nama ?? '-') . " | WBP: {$namaWbp} | Tanggal: {$tanggal}"
                        );

                        $updatedFields = implode(', ', array_keys($update));
                        $this->addLog(
                            $logs,
                            'info',
                            "UPDATE DataKunjungan | NIK: {$nik} | Field: {$updatedFields}"
                        );
                    } else {
                        $update = [];

                        $rawWbp = $kunjungan->getRawOriginal('wbp');
                        if (empty($rawWbp) && !empty($namaWbp)) {
                            $update['wbp'] = $namaWbp;
                        }
                        if (empty($kunjungan->foto_ktp) && !empty($p->foto_ktp)) {

                            $update['foto_ktp'] = $p->foto_ktp;

                            $stats['update_foto_ktp']++;
                        }
                        if (empty($kunjungan->foto_diri) && !empty($p->foto)) {
                            $update['foto_diri'] = $p->foto;
                            $stats['update_foto_diri']++;
                        }
                        if (empty($kunjungan->catatan) && !empty($p->hp)) {
                            $update['catatan'] = $p->hp;
                        }
                        if (empty($kunjungan->pengunjung) && !empty($p->nama)) {
                            $update['pengunjung'] = $p->nama;
                        }

                        $rawTgl = $kunjungan->getRawOriginal('waktu_kunjungan');
                        if (empty($rawTgl) && !empty($tanggal)) {
                            $update['waktu_kunjungan'] = $tanggal;
                        }

                        if (!empty($update)) {
                            DataKunjungan::where('id', $kunjungan->id)->update($update);
                            $stats['update_kunjungan']++;
                            $this->addLog($logs, 'info', 'Update DataKunjungan NIK: ' . $nik);
                        }
                    }
                } catch (\Throwable $e) {
                    $stats['error']++;
                    $this->addLog($logs, 'error', 'SIPIRMAN→SDP [' . ($p->nik ?? '-') . ']: ' . $e->getMessage());
                }
            }

            if (!empty($insertKunjunganBatch)) {
                foreach (array_chunk($insertKunjunganBatch, 500) as $chunk) {
                    DataKunjungan::insert($chunk);
                }
                $this->addLog($logs, 'success', count($insertKunjunganBatch) . ' data baru ditambahkan ke DataKunjungan.');
            }

            $elapsed = round(microtime(true) - $start, 2);

            $this->addLog($logs, 'success', 'Sinkronisasi selesai dalam ' . $elapsed . ' detik.');

            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi berhasil dalam ' . $elapsed . ' detik.',
                'elapsed' => $elapsed,
                'stats'   => $stats,
                'log'     => $logs,
            ]);
        } catch (\Throwable $e) {
            Log::error($e);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'log'     => [[
                    'level'   => 'error',
                    'message' => $e->getMessage(),
                    'time'    => now()->format('H:i:s'),
                ]],
            ], 500);
        }
    }

    public function autoSyncAfterImport(array $newRows = []): array
    {
        $stats = ['insert_sipirman' => 0, 'update_sipirman' => 0, 'skip' => 0, 'error' => 0];
        $logs  = [];

        try {
            DB::connection('sipirman')->getPdo();
        } catch (\Throwable $e) {
            $this->addLog($logs, 'error', 'Koneksi SIPIRMAN gagal: ' . $e->getMessage());
            Log::error('AutoSync: koneksi SIPIRMAN gagal', ['error' => $e->getMessage()]);
            return ['stats' => $stats, 'logs' => $logs];
        }

        if (empty($newRows)) {
            $source = DataKunjungan::select(
                'id',
                'no_identitas',
                'wbp',
                'pengunjung',
                'catatan',
                'waktu_kunjungan',
                'foto_ktp',
                'foto_diri'
            )->whereNotNull('no_identitas')->get()->toArray();
        } else {
            $source = $newRows;
        }

        if (empty($source)) {
            $this->addLog($logs, 'info', 'Tidak ada data untuk disinkronkan.');
            return ['stats' => $stats, 'logs' => $logs];
        }

        $niks = array_filter(array_unique(
            array_map(
                fn($r) => trim(is_array($r) ? ($r['no_identitas'] ?? '') : ($r->no_identitas ?? '')),
                $source
            )
        ));

        $penitipByNik = [];
        if (!empty($niks)) {
            foreach (
                DB::connection('sipirman')->table('penitip')
                    ->select('id', 'nik', 'nama', 'hp', 'nama_wbp', 'jadwal_kunjungan', 'foto', 'foto_ktp')
                    ->whereIn('nik', array_values($niks))
                    ->get() as $p
            ) {
                $penitipByNik[trim($p->nik)] = $p;
            }
        }

        // Composite key NIK+tanggal dari SIPIRMAN untuk cek duplikat
        $penitipCompositeKeys = [];
        foreach ($penitipByNik as $nik => $p) {
            $tgl = $this->formatTanggal($p->jadwal_kunjungan ?? null) ?? '__nodate__';
            $penitipCompositeKeys[$nik . '|' . $tgl] = true;
        }

        $insertBatch = [];

        foreach ($source as $row) {
            try {
                $nik = trim(is_array($row) ? ($row['no_identitas'] ?? '') : ($row->no_identitas ?? ''));
                if ($nik === '') {
                    $stats['skip']++;
                    continue;
                }

                $nama     = $this->safeTrunc($this->safeStr(is_array($row) ? ($row['pengunjung'] ?? null) : ($row->pengunjung ?? null)), 100);
                $hp       = $this->safeTrunc($this->safeStr(is_array($row) ? ($row['catatan']    ?? null) : ($row->catatan    ?? null)), 13);
                $wbp      = is_array($row) ? ($row['wbp']             ?? null) : ($row->wbp             ?? null);
                $waktu    = is_array($row) ? ($row['waktu_kunjungan'] ?? null) : ($row->waktu_kunjungan ?? null);
                $fotoKtp  = is_array($row) ? ($row['foto_ktp']        ?? null) : ($row->foto_ktp        ?? null);
                $fotoDiri = is_array($row) ? ($row['foto_diri']       ?? null) : ($row->foto_diri       ?? null);

                $namaWbp      = $this->safeTrunc($this->cleanNamaWbp($wbp), 100);
                $jadwal       = $this->formatTanggal($waktu); // ✅ FIX #1: pakai $waktu, bukan $k->getRawOriginal()
                $tglKey       = $jadwal ?? '__nodate__';
                $compositeKey = $nik . '|' . $tglKey;

                if (!isset($penitipByNik[$nik])) {

                    // ✅ FIX #2: Cek composite key dulu, baru insert
                    // Mencegah insert ulang NIK+tanggal yang sama di iterasi berikutnya
                    if (!isset($penitipCompositeKeys[$compositeKey])) {

                        $newRow                              = $this->buildPenitipRow($nik, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri); // ✅ FIX #3: pakai $this bukan $self
                        $insertBatch[]                       = $newRow;
                        $penitipByNik[$nik]                  = (object) $newRow;
                        $penitipCompositeKeys[$compositeKey] = true;
                        $stats['insert_sipirman']++;

                        $this->addLog(
                            $logs,
                            'info', // ✅ FIX #3: pakai $this bukan $self
                            "INSERT SIPIRMAN | NIK: {$nik} | Nama: {$nama} | WBP: {$namaWbp} | Tanggal: {$jadwal}"
                        );
                    } else {
                        // NIK belum ada tapi tanggal sama → skip
                        $stats['skip']++;
                    }
                } else {

                    // NIK sudah ada di SIPIRMAN → UPDATE field yang masih kosong
                    $update = $this->buildUpdatePayload(
                        $penitipByNik[$nik],
                        $nama,
                        $hp,
                        $namaWbp,
                        $jadwal,
                        $fotoKtp,
                        $fotoDiri
                    );

                    if (!empty($update)) {
                        DB::connection('sipirman')->table('penitip')
                            ->where('id', $penitipByNik[$nik]->id)
                            ->update($update);

                        foreach ($update as $fld => $val) {
                            $penitipByNik[$nik]->$fld = $val;
                        }

                        $stats['update_sipirman']++;

                        $updatedFields = implode(', ', array_keys($update));
                        $this->addLog(
                            $logs,
                            'info',
                            "UPDATE SIPIRMAN | NIK: {$nik} | Nama: {$nama} | Field diupdate: {$updatedFields}"
                        );
                    } else {
                        $stats['skip']++;
                    }
                }
            } catch (\Throwable $e) {
                $stats['error']++;
                $this->addLog($logs, 'error', 'AutoSync [' . ($nik ?? '-') . ']: ' . $e->getMessage());
                Log::error('AutoSync error', ['nik' => $nik ?? '-', 'error' => $e->getMessage()]);
            }
        }

        if (!empty($insertBatch)) {
            $this->bulkInsertWithFallback($insertBatch, $stats, $logs);
            $this->addLog(
                $logs,
                'success',
                $stats['insert_sipirman'] . ' data baru dikirim ke SIPIRMAN.'
            );
        }

        $this->addLog(
            $logs,
            'success',
            "AutoSync selesai | Insert: {$stats['insert_sipirman']} | Update: {$stats['update_sipirman']} | Skip: {$stats['skip']} | Error: {$stats['error']}"
        );

        return ['stats' => $stats, 'logs' => $logs];
    }


    public function log()
    {
        return response()->json([
            'success' => true,
            'history' => [],
        ]);
    }

    public function foto(Request $request)
    {
        try {
            $path   = $request->query('path');
            $folder = $request->query('folder', '');

            if (!$path) {
                abort(404);
            }

            // ✅ FIX: Strip prefix folder dari path jika sudah ada
            // Contoh: path="diri/Tgk3c.png" → cleanPath="Tgk3c.png"
            $cleanPath = ltrim($path, '/');
            foreach (['ktp/', 'diri/', 'foto_diri/', 'foto_ktp/'] as $prefix) {
                if (str_starts_with($cleanPath, $prefix)) {
                    $cleanPath = substr($cleanPath, strlen($prefix));
                    break;
                }
            }

            $candidates = array_unique([
                public_path('storage/' . ltrim($path, '/')),          // path asli (sudah ada folder prefix)
                public_path("storage/{$folder}/{$cleanPath}"),         // folder dari param + nama file bersih
                public_path("storage/ktp/{$cleanPath}"),               // fallback ktp
                public_path("storage/diri/{$cleanPath}"),              // fallback diri
                public_path("storage/foto_diri/{$cleanPath}"),         // fallback foto_diri
                public_path("storage/{$cleanPath}"),                   // fallback root storage
            ]);

            foreach ($candidates as $fullPath) {
                if (file_exists($fullPath)) {
                    return response()->file($fullPath);
                }
            }

            Log::warning('Foto tidak ditemukan', [
                'path'       => $path,
                'folder'     => $folder,
                'candidates' => $candidates,
            ]);

            abort(404);
        } catch (\Throwable $e) {
            abort(404);
        }
    }


    private function addLog(array &$logs, string $level, string $message): void
    {
        $logs[] = [
            'level'   => $level,
            'message' => $message,
            'time'    => now()->format('H:i:s'),
        ];
    }

    private function formatTanggal(?string $tanggal): ?string
    {
        if (empty($tanggal)) {
            return null;
        }

        $tanggal = trim($tanggal);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
            return $tanggal;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})[\sT]/', $tanggal, $m)) {
            return $m[1];
        }

        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $tanggal, $m)) {
            [$day, $month, $year] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }

        try {
            return \Carbon\Carbon::parse($tanggal)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cleanNamaWbp(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $clean = implode(', ', array_filter(array_map('trim', $decoded)));
            return $clean !== '' ? $clean : null;
        }

        $clean = str_replace(['["', '"]', '[', ']', '"', "'"], '', $value);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        return $clean !== '' ? $clean : null;
    }
    /*
|--------------------------------------------------------------------------
| HELPER METHODS
|--------------------------------------------------------------------------
*/

    public function buildPenitipRow(
        string  $nik,
        ?string $nama,
        ?string $hp,
        ?string $namaWbp,
        ?string $jadwal,
        ?string $fotoKtp,
        ?string $foto
    ): array {
        return [
            'nama'             => $nama ?? '',
            'hp'               => $hp   ?? '',
            'nik'              => $nik,
            'nama_wbp'         => $namaWbp,
            'jadwal_kunjungan' => $jadwal,
            'foto'             => $this->safeTrunc($foto,    100),
            'foto_ktp'         => $this->safeTrunc($fotoKtp, 100),
            'username'         => null,
            'password'         => null,
            'pengikut'         => null,
            'kode_tahanan'     => null,
            'foto_kk'          => null,
            'foto_pernyataan'  => null,
            'keluarga_inti'    => '0',
        ];
    }

    public function buildUpdatePayload(
        object  $sip,
        ?string $nama,
        ?string $hp,
        ?string $namaWbp,
        ?string $jadwal,
        ?string $fotoKtp,
        ?string $foto
    ): array {
        $update = [];

        if (empty(trim($sip->foto_ktp  ?? '')) && !empty($fotoKtp))  $update['foto_ktp']         = $this->safeTrunc($fotoKtp, 100);
        if (empty(trim($sip->foto      ?? '')) && !empty($foto))      $update['foto']             = $this->safeTrunc($foto,    100);
        if (empty(trim($sip->hp        ?? '')) && !empty($hp))        $update['hp']               = $this->safeTrunc($hp,      13);
        if (empty(trim($sip->nama      ?? '')) && !empty($nama))      $update['nama']             = $this->safeTrunc($nama,    100);
        if (empty(trim($sip->nama_wbp  ?? '')) && !empty($namaWbp))   $update['nama_wbp']         = $this->safeTrunc($namaWbp, 100);
        if (empty(trim($sip->jadwal_kunjungan ?? '')) && !empty($jadwal)) $update['jadwal_kunjungan'] = $jadwal;

        return $update;
    }

    public function bulkInsertWithFallback(array $batch, array &$stats, array &$logs): void
    {
        foreach (array_chunk($batch, 500) as $chunk) {
            try {
                DB::connection('sipirman')->table('penitip')->insert($chunk);
            } catch (\Throwable $e) {
                $this->addLog($logs, 'error', 'Bulk insert gagal, fallback per-baris: ' . $e->getMessage());
                Log::error('Bulk insert SIPIRMAN gagal', ['error' => $e->getMessage()]);

                foreach ($chunk as $singleRow) {
                    try {
                        DB::connection('sipirman')->table('penitip')->insert($singleRow);
                    } catch (\Throwable $e2) {
                        $stats['error']++;
                        $this->addLog(
                            $logs,
                            'error',
                            'Insert gagal NIK [' . ($singleRow['nik'] ?? '-') . ']: ' . $e2->getMessage()
                        );
                        Log::error('Insert per-baris SIPIRMAN gagal', [
                            'nik'   => $singleRow['nik'] ?? '-',
                            'error' => $e2->getMessage(),
                            'row'   => $singleRow,
                        ]);
                    }
                }
            }
        }
    }

    public function safeStr(?string $value): string
    {
        return $value !== null ? trim($value) : '';
    }

    public function safeTrunc(?string $value, int $maxLen): ?string
    {
        if ($value === null) return null;
        return mb_strlen($value) > $maxLen ? mb_substr($value, 0, $maxLen) : $value;
    }
}
