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
    | DEBUG
    | Akses: GET /sinkronisasi/debug
    |--------------------------------------------------------------------------
    */
    public function debug()
    {
        $result = [];

        try {
            DB::connection('sipirman')->getPdo();
            $result['koneksi'] = 'OK';
        } catch (\Throwable $e) {
            return response()->json(['koneksi_error' => $e->getMessage()]);
        }

        try {
            $columns = DB::connection('sipirman')->select('SHOW FULL COLUMNS FROM penitip');
            $result['kolom_penitip'] = collect($columns)->map(fn($c) => [
                'field'   => $c->Field,
                'type'    => $c->Type,
                'null'    => $c->Null,
                'default' => $c->Default,
                'extra'   => $c->Extra,
            ])->toArray();
        } catch (\Throwable $e) {
            $result['kolom_error'] = $e->getMessage();
        }

        $testRow = $this->buildPenitipRow(
            'DEBUG_' . time(),
            null,
            null,
            null,
            null,
            null,
            null
        );

        try {
            DB::connection('sipirman')->beginTransaction();
            DB::connection('sipirman')->table('penitip')->insert($testRow);
            DB::connection('sipirman')->rollBack();
            $result['test_insert'] = 'BERHASIL (di-rollback, tidak ada data tersimpan)';
        } catch (\Throwable $e) {
            DB::connection('sipirman')->rollBack();
            $result['test_insert_error'] = $e->getMessage();
        }

        try {
            $sample = DataKunjungan::select(
                'id',
                'no_identitas',
                'pengunjung',
                'catatan',
                'waktu_kunjungan',
                'foto_ktp',
                'foto_diri'
            )->whereNotNull('no_identitas')->limit(5)->get();
            $result['sample_kunjungan'] = $sample->toArray();
        } catch (\Throwable $e) {
            $result['sample_kunjungan_error'] = $e->getMessage();
        }

        try {
            $mode = DB::connection('sipirman')->selectOne('SELECT @@sql_mode as mode');
            $result['sql_mode_sipirman'] = $mode->mode;
        } catch (\Throwable $e) {
            $result['sql_mode_error'] = $e->getMessage();
        }

        return response()->json($result, 200, [], JSON_PRETTY_PRINT);
    }

    /*
    |--------------------------------------------------------------------------
    | PREVIEW
    |--------------------------------------------------------------------------
    */
    public function preview()
    {
        try {
            $totalKunjungan = DataKunjungan::count();
            $totalPenitip   = DB::connection('sipirman')->table('penitip')->count();
            $belumSync      = 0;

            DataKunjungan::select('no_identitas', 'waktu_kunjungan')
                ->whereNotNull('no_identitas')
                ->chunk(500, function ($rows) use (&$belumSync) {
                    $niks = $rows->pluck('no_identitas')->unique()->toArray();
                    $ada  = DB::connection('sipirman')
                        ->table('penitip')
                        ->whereIn('nik', $niks)
                        ->pluck('nik')
                        ->toArray();
                    foreach ($rows as $row) {
                        $nik = trim($row->no_identitas ?? '');
                        if ($nik !== '' && !in_array($nik, $ada)) {
                            $belumSync++;
                        }
                    }
                });

            return response()->json([
                'success'              => true,
                'total_kunjungan'      => $totalKunjungan,
                'total_penitip'        => $totalPenitip,
                'perlu_sync'           => $belumSync,
                'akan_dicocokkan'      => $totalKunjungan,
                'foto_ktp_akan_diisi'  => DataKunjungan::whereNull('foto_ktp')->orWhere('foto_ktp', '')->count(),
                'foto_diri_akan_diisi' => DataKunjungan::whereNull('foto_diri')->orWhere('foto_diri', '')->count(),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | AUTO SYNC
    | Dipanggil dari DataKunjunganController setelah import Excel selesai.
    |
    | Contoh pemanggilan:
    |   $syncer = new SinkronisasiController();
    |   $result = $syncer->autoSyncAfterImport($newRows);
    |--------------------------------------------------------------------------
    */
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

                $namaWbp = $this->safeTrunc($this->cleanNamaWbp($wbp), 100);
                $jadwal  = $this->formatTanggal($waktu);

                if (!isset($penitipByNik[$nik])) {
                    $newRow             = $this->buildPenitipRow($nik, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);
                    $insertBatch[]      = $newRow;
                    $penitipByNik[$nik] = (object) $newRow;
                    $stats['insert_sipirman']++;
                } else {
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
            $this->addLog($logs, 'success', $stats['insert_sipirman'] . ' data baru dikirim ke SIPIRMAN.');
        }

        return ['stats' => $stats, 'logs' => $logs];
    }

    /*
    |--------------------------------------------------------------------------
    | RUN — Sinkronisasi dua arah manual (tombol di UI)
    |--------------------------------------------------------------------------
    */
    public function run()
    {
        try {
            set_time_limit(0);
            ini_set('memory_limit', '1024M');

            $start = microtime(true);
            $logs  = [];
            $stats = [
                'insert_sipirman'  => 0,
                'insert_kunjungan' => 0,
                'update_sipirman'  => 0,
                'update_kunjungan' => 0,
                'skip'             => 0,
                'error'            => 0,
                'update_foto_ktp'  => 0,
                'update_foto_diri' => 0,
                'detail_foto_ktp'  => [],
                'detail_foto_diri' => [],
            ];

            $this->addLog($logs, 'info', 'Memulai sinkronisasi dua arah...');

            // ----------------------------------------------------------------
            // STEP 1 — Verifikasi koneksi
            // ----------------------------------------------------------------
            try {
                DB::connection('sipirman')->getPdo();
                $this->addLog($logs, 'success', 'Koneksi SIPIRMAN berhasil.');
            } catch (\Throwable $e) {
                $this->addLog($logs, 'error', 'Koneksi SIPIRMAN gagal: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Koneksi SIPIRMAN gagal: ' . $e->getMessage(),
                    'log'     => $logs,
                ], 500);
            }

            // ----------------------------------------------------------------
            // STEP 2 — Load seluruh data SIPIRMAN ke memori (index by NIK)
            // ----------------------------------------------------------------
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

            // ----------------------------------------------------------------
            // STEP 3 — Load seluruh DataKunjungan ke memori
            //          Index: [NIK][Tanggal] untuk deteksi duplikat arah 2
            // ----------------------------------------------------------------
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
            )->whereNotNull('no_identitas')->get();

            $kunjunganByNikTgl   = [];
            $kunjunganFirstByNik = [];

            foreach ($kunjunganRows as $k) {
                $nik = trim($k->no_identitas ?? '');
                if ($nik === '') continue;
                $tgl = $this->formatTanggal($k->getRawOriginal('waktu_kunjungan')) ?? '__nodate__';
                if (!isset($kunjunganByNikTgl[$nik])) {
                    $kunjunganByNikTgl[$nik] = [];
                }
                $kunjunganByNikTgl[$nik][$tgl] = $k;
                if (!isset($kunjunganFirstByNik[$nik])) {
                    $kunjunganFirstByNik[$nik] = $k;
                }
            }

            $this->addLog($logs, 'info', count($kunjunganFirstByNik) . ' NIK unik di DataKunjungan dimuat.');

            // ----------------------------------------------------------------
            // STEP 4 — Arah 1: SDP ke SIPIRMAN
            //
            // Iterasi semua DataKunjungan dengan chunk(300).
            // NIK yang belum ada di SIPIRMAN akan diinsert.
            // NIK yang sudah ada akan diupdate field yang masih kosong.
            //
            // $self = $this wajib karena closure PHP tidak punya akses $this.
            // ----------------------------------------------------------------
            $this->addLog($logs, 'info', 'Sinkronisasi SDP ke SIPIRMAN...');

            $self = $this;

            DataKunjungan::whereNotNull('no_identitas')
                ->chunk(300, function ($rows) use (&$logs, &$stats, &$penitipByNik, $self) {

                    $insertBatch = [];

                    foreach ($rows as $k) {
                        try {
                            $nik = trim($k->no_identitas ?? '');
                            if ($nik === '') {
                                $stats['skip']++;
                                continue;
                            }

                            $nama     = $self->safeTrunc($self->safeStr($k->pengunjung ?? null), 100);
                            $hp       = $self->safeTrunc($self->safeStr($k->catatan    ?? null), 13);
                            $namaWbp  = $self->safeTrunc($self->cleanNamaWbp($k->getRawOriginal('wbp') ?? null), 100);
                            $jadwal   = $self->formatTanggal($k->getRawOriginal('waktu_kunjungan') ?? null);
                            $fotoKtp  = $k->foto_ktp  ?? null;
                            $fotoDiri = $k->foto_diri ?? null;

                            if (!isset($penitipByNik[$nik])) {
                                $newRow             = $self->buildPenitipRow($nik, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);
                                $insertBatch[]      = $newRow;
                                $penitipByNik[$nik] = (object) $newRow;
                                $stats['insert_sipirman']++;
                            } else {
                                $sip    = $penitipByNik[$nik];
                                $update = $self->buildUpdatePayload($sip, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);

                                if (!empty($update)) {
                                    if (isset($update['foto_ktp'])) {
                                        $stats['update_foto_ktp']++;
                                        $stats['detail_foto_ktp'][] = ['nik' => $nik, 'nama' => $sip->nama ?? '-'];
                                    }
                                    if (isset($update['foto'])) {
                                        $stats['update_foto_diri']++;
                                        $stats['detail_foto_diri'][] = ['nik' => $nik, 'nama' => $sip->nama ?? '-'];
                                    }

                                    DB::connection('sipirman')
                                        ->table('penitip')
                                        ->where('id', $sip->id)
                                        ->update($update);

                                    foreach ($update as $fld => $val) {
                                        $penitipByNik[$nik]->$fld = $val;
                                    }
                                    $stats['update_sipirman']++;
                                } else {
                                    $stats['skip']++;
                                }
                            }
                        } catch (\Throwable $e) {
                            $stats['error']++;
                            $self->addLog(
                                $logs,
                                'error',
                                'SDP ke SIPIRMAN [' . ($k->no_identitas ?? '-') . ']: '
                                    . $e->getMessage() . ' | line:' . $e->getLine()
                            );
                            Log::error('SDP ke SIPIRMAN error', [
                                'nik'   => $k->no_identitas ?? '-',
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString(),
                            ]);
                        }
                    }

                    if (!empty($insertBatch)) {
                        $self->bulkInsertWithFallback($insertBatch, $stats, $logs);
                        $self->addLog($logs, 'success', count($insertBatch) . ' data baru dikirim ke SIPIRMAN.');
                    }
                });

            $this->addLog(
                $logs,
                'info',
                'Total insert ke SIPIRMAN: ' . $stats['insert_sipirman'] .
                    ' | Update: '  . $stats['update_sipirman'] .
                    ' | Skip: '   . $stats['skip'] .
                    ' | Error: '  . $stats['error']
            );

            // ----------------------------------------------------------------
            // STEP 5 — Arah 2: SIPIRMAN ke SDP
            //
            // Load ulang data SIPIRMAN agar mencerminkan kondisi terkini
            // setelah insert di STEP 4.
            // ----------------------------------------------------------------
            $this->addLog($logs, 'info', 'Sinkronisasi SIPIRMAN ke SDP...');

            $penitipRowsFresh = DB::connection('sipirman')
                ->table('penitip')
                ->select('id', 'nik', 'nama', 'hp', 'nama_wbp', 'jadwal_kunjungan', 'foto', 'foto_ktp')
                ->whereNotNull('nik')
                ->get();

            $insertKunjunganBatch = [];

            foreach ($penitipRowsFresh as $p) {
                try {
                    $nik = trim($p->nik ?? '');
                    if ($nik === '') continue;

                    $tanggal = $this->formatTanggal($p->jadwal_kunjungan ?? null);
                    $tglKey  = $tanggal ?? '__nodate__';
                    $namaWbp = $this->cleanNamaWbp($p->nama_wbp ?? null);

                    $existingRow = $kunjunganByNikTgl[$nik][$tglKey] ?? null;

                    if (!$existingRow) {
                        $newRow = [
                            'no'              => mt_rand(100000, 999999),
                            'wbp'             => $namaWbp,
                            'pengunjung'      => $p->nama   ?? null,
                            'no_identitas'    => $nik,
                            'catatan'         => $p->hp     ?? null,
                            'waktu_kunjungan' => $tanggal,
                            'foto_ktp'        => $p->foto_ktp ?? null,
                            'foto_diri'       => $p->foto     ?? null,
                        ];

                        $insertKunjunganBatch[] = $newRow;

                        if (!isset($kunjunganByNikTgl[$nik])) {
                            $kunjunganByNikTgl[$nik] = [];
                        }
                        $kunjunganByNikTgl[$nik][$tglKey] = (object) $newRow;
                        $stats['insert_kunjungan']++;
                    } else {
                        $update = [];

                        $rawWbp = method_exists($existingRow, 'getRawOriginal')
                            ? $existingRow->getRawOriginal('wbp')
                            : ($existingRow->wbp ?? null);

                        if (empty($rawWbp)                  && !empty($namaWbp))     $update['wbp']             = $namaWbp;
                        if (empty($existingRow->foto_ktp)   && !empty($p->foto_ktp)) {
                            $update['foto_ktp']  = $p->foto_ktp;
                            $stats['update_foto_ktp']++;
                        }
                        if (empty($existingRow->foto_diri)  && !empty($p->foto)) {
                            $update['foto_diri'] = $p->foto;
                            $stats['update_foto_diri']++;
                        }
                        if (empty($existingRow->catatan)    && !empty($p->hp))         $update['catatan']    = $p->hp;
                        if (empty($existingRow->pengunjung) && !empty($p->nama))       $update['pengunjung'] = $p->nama;

                        $rawTgl = method_exists($existingRow, 'getRawOriginal')
                            ? $existingRow->getRawOriginal('waktu_kunjungan')
                            : ($existingRow->waktu_kunjungan ?? null);
                        if (empty($rawTgl) && !empty($tanggal)) {
                            $update['waktu_kunjungan'] = $tanggal;
                        }

                        if (!empty($update)) {
                            DataKunjungan::where('id', $existingRow->id)->update($update);
                            $stats['update_kunjungan']++;
                        } else {
                            $stats['skip']++;
                        }
                    }
                } catch (\Throwable $e) {
                    $stats['error']++;
                    $this->addLog(
                        $logs,
                        'error',
                        'SIPIRMAN ke SDP [' . ($p->nik ?? '-') . ']: '
                            . $e->getMessage() . ' | line:' . $e->getLine()
                    );
                    Log::error('SIPIRMAN ke SDP error', ['nik' => $p->nik ?? '-', 'error' => $e->getMessage()]);
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
            Log::error('run() fatal', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'log'     => [['level' => 'error', 'message' => $e->getMessage(), 'time' => now()->format('H:i:s')]],
            ], 500);
        }
    }

    public function log()
    {
        return response()->json(['success' => true, 'history' => []]);
    }

    public function foto(Request $request)
    {
        try {
            $path   = $request->query('path');
            $folder = $request->query('folder', '');
            if (!$path) abort(404);
            $path = ltrim($path, '/');

            $candidates = [];
            if ($folder) {
                $candidates[] = public_path("storage/{$folder}/{$path}");
                $candidates[] = public_path("storage/{$path}");
            }
            $candidates[] = public_path("storage/{$path}");
            $candidates[] = public_path("storage/ktp/{$path}");
            $candidates[] = public_path("storage/foto_diri/{$path}");

            foreach (array_unique($candidates) as $fullPath) {
                if (file_exists($fullPath)) return response()->file($fullPath);
            }
            abort(404);
        } catch (\Throwable $e) {
            abort(404);
        }
    }

    /*
    |==========================================================================
    | HELPER
    | Semua public agar bisa dipanggil via $self di dalam closure.
    |==========================================================================
    */

    /**
     * Bangun array INSERT untuk tabel penitip SIPIRMAN.
     *
     * Kolom NOT NULL:
     *   nama          varchar(100) NOT NULL  -> fallback ''
     *   hp            varchar(13)  NOT NULL  -> fallback ''
     *   keluarga_inti ENUM('1','0')          -> wajib '0'
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

    /**
     * Bangun array UPDATE untuk SIPIRMAN.
     * Hanya mengisi field yang saat ini masih kosong.
     */
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

        if (empty($sip->foto_ktp)         && !empty($fotoKtp)) $update['foto_ktp']         = $this->safeTrunc($fotoKtp, 100);
        if (empty($sip->foto)             && !empty($foto))     $update['foto']             = $this->safeTrunc($foto,    100);
        if (empty($sip->hp)               && !empty($hp))       $update['hp']               = $this->safeTrunc($hp,      13);
        if (empty($sip->nama)             && !empty($nama))     $update['nama']             = $this->safeTrunc($nama,    100);
        if (empty($sip->nama_wbp)         && !empty($namaWbp))  $update['nama_wbp']         = $this->safeTrunc($namaWbp, 100);
        if (empty($sip->jadwal_kunjungan) && !empty($jadwal))   $update['jadwal_kunjungan'] = $jadwal;

        return $update;
    }

    /**
     * Bulk insert ke SIPIRMAN dengan fallback insert per-baris.
     * Jika batch gagal, dicoba satu per satu agar baris lain tetap masuk.
     */
    public function bulkInsertWithFallback(array $batch, array &$stats, array &$logs): void
    {
        foreach (array_chunk($batch, 500) as $chunk) {
            try {
                DB::connection('sipirman')->table('penitip')->insert($chunk);
            } catch (\Throwable $e) {
                $this->addLog(
                    $logs,
                    'error',
                    'Bulk insert gagal, fallback per-baris: ' . $e->getMessage()
                );
                Log::error('Bulk insert SIPIRMAN gagal', [
                    'error'  => $e->getMessage(),
                    'sample' => array_slice($chunk, 0, 2),
                ]);

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

    /**
     * Pastikan string tidak pernah null untuk kolom NOT NULL.
     * Mengembalikan '' jika input null.
     */
    public function safeStr(?string $value): string
    {
        return $value !== null ? trim($value) : '';
    }

    /**
     * Potong string agar tidak melebihi batas varchar MySQL.
     * Null tetap null.
     *
     * Batas kolom SIPIRMAN:
     *   hp               -> 13
     *   nama / nama_wbp  -> 100
     *   foto / foto_ktp  -> 100
     *   jadwal_kunjungan -> 100
     */
    public function safeTrunc(?string $value, int $maxLen): ?string
    {
        if ($value === null) return null;
        return mb_strlen($value) > $maxLen ? mb_substr($value, 0, $maxLen) : $value;
    }

    public function addLog(array &$logs, string $level, string $message): void
    {
        $logs[] = ['level' => $level, 'message' => $message, 'time' => now()->format('H:i:s')];
    }

    public function formatTanggal(?string $tanggal): ?string
    {
        if (empty($tanggal)) return null;
        $tanggal = trim($tanggal);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal))            return $tanggal;
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[\sT]/', $tanggal, $m)) return $m[1];
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

    public function cleanNamaWbp(?string $value): ?string
    {
        if ($value === null || trim($value) === '') return null;
        $value   = trim($value);
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $clean = implode(', ', array_filter(array_map('trim', $decoded)));
            return $clean !== '' ? $clean : null;
        }

        $clean = str_replace(['["', '"]', '[', ']', '"', "'"], '', $value);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));
        return $clean !== '' ? $clean : null;
    }
}
