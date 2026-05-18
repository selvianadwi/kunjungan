<?php

namespace App\Http\Controllers;

use App\Models\DataKunjungan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SinkronisasiController extends Controller
{
    // =========================================================================
    // CHECK KONEKSI
    // =========================================================================

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

    // =========================================================================
    // PREVIEW
    // =========================================================================

    public function preview()
    {
        try {
            $totalKunjungan = DataKunjungan::count();
            $totalPenitip   = DB::connection('sipirman')->table('penitip')->count();
            $belumSync      = 0;

            DataKunjungan::select('no_identitas')
                ->whereNotNull('no_identitas')
                ->chunk(500, function ($rows) use (&$belumSync) {
                    $niks   = $rows->pluck('no_identitas')->toArray();
                    $exists = DB::connection('sipirman')
                        ->table('penitip')
                        ->whereIn('nik', $niks)
                        ->pluck('nik')
                        ->toArray();
                    foreach ($niks as $nik) {
                        if (!in_array($nik, $exists)) $belumSync++;
                    }
                });

            $fotoKtpKosong  = DataKunjungan::whereNull('foto_ktp')->orWhere('foto_ktp', '')->count();
            $fotoDiriKosong = DataKunjungan::whereNull('foto_diri')->orWhere('foto_diri', '')->count();

            return response()->json([
                'success'              => true,
                'total_kunjungan'      => $totalKunjungan,
                'total_penitip'        => $totalPenitip,
                'perlu_sync'           => $belumSync,
                'akan_dicocokkan'      => $totalKunjungan,
                'foto_ktp_akan_diisi'  => $fotoKtpKosong,
                'foto_diri_akan_diisi' => $fotoDiriKosong,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

        if (empty($niks)) {
            $this->addLog($logs, 'info', 'Tidak ada NIK valid untuk disinkronkan.');
            return ['stats' => $stats, 'logs' => $logs];
        }

      
        $penitipByNik         = [];   
        $penitipCompositeKeys = [];  

        foreach (
            DB::connection('sipirman')->table('penitip')
                ->select('id', 'nik', 'nama', 'hp', 'nama_wbp', 'jadwal_kunjungan', 'foto', 'foto_ktp')
                ->whereIn('nik', array_values($niks))
                ->get() as $p
        ) {
            $nik = trim($p->nik);
            $tgl = $this->formatTanggal($p->jadwal_kunjungan ?? null) ?? '__nodate__';
            $penitipCompositeKeys[$nik . '|' . $tgl] = $p;

            if (!isset($penitipByNik[$nik]) || (empty($penitipByNik[$nik]->foto_ktp) && !empty($p->foto_ktp))) {
                $penitipByNik[$nik] = $p;
            }
            if (isset($penitipByNik[$nik]) && empty($penitipByNik[$nik]->foto) && !empty($p->foto)) {
                $penitipByNik[$nik]->foto = $p->foto;
            }
        }

        $kunjunganByNik = [];
        foreach (
            DataKunjungan::select('id', 'no_identitas', 'foto_ktp', 'foto_diri')
                ->whereIn('no_identitas', array_values($niks))
                ->get() as $k
        ) {
            $nik = trim($k->no_identitas ?? '');
            if (!isset($kunjunganByNik[$nik]) || (empty($kunjunganByNik[$nik]->foto_ktp) && !empty($k->foto_ktp))) {
                $kunjunganByNik[$nik] = $k;
            }
            if (isset($kunjunganByNik[$nik]) && empty($kunjunganByNik[$nik]->foto_diri) && !empty($k->foto_diri)) {
                $kunjunganByNik[$nik]->foto_diri = $k->foto_diri;
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

                $rowId    = is_array($row) ? ($row['id']          ?? null) : ($row->id          ?? null);
                $nama     = $this->safeTrunc($this->safeStr(is_array($row) ? ($row['pengunjung'] ?? null) : ($row->pengunjung ?? null)), 100);
                $hp       = $this->safeTrunc($this->safeStr(is_array($row) ? ($row['catatan']    ?? null) : ($row->catatan    ?? null)), 13);
                $wbpRaw   = is_array($row) ? ($row['wbp']             ?? null) : ($row->wbp             ?? null);
                $waktu    = is_array($row) ? ($row['waktu_kunjungan'] ?? null) : ($row->waktu_kunjungan ?? null);
                $fotoKtp  = is_array($row) ? ($row['foto_ktp']        ?? null) : ($row->foto_ktp        ?? null);
                $fotoDiri = is_array($row) ? ($row['foto_diri']       ?? null) : ($row->foto_diri       ?? null);

                $namaWbp      = $this->safeTrunc($this->cleanNamaWbp($wbpRaw), 100);
                $jadwal       = $this->formatTanggal($waktu);
                $tglKey       = $jadwal ?? '__nodate__';
                $compositeKey = $nik . '|' . $tglKey;

                $sipFoto = $penitipByNik[$nik] ?? null;
                if ($sipFoto) {
                    if (empty($fotoKtp)  && !empty($sipFoto->foto_ktp)) {
                        $fotoKtp = $sipFoto->foto_ktp;
                    }
                    if (empty($fotoDiri) && !empty($sipFoto->foto)) {
                        $fotoDiri = $sipFoto->foto;
                    }
                }

                if ($rowId) {
                    $sdpUpdate = [];
                    $sdpRow    = $kunjunganByNik[$nik] ?? null;

                    $sdpFotoKtp  = $sdpRow ? ($sdpRow->foto_ktp  ?? null) : null;
                    $sdpFotoDiri = $sdpRow ? ($sdpRow->foto_diri ?? null) : null;

                    if (empty($sdpFotoKtp)  && !empty($fotoKtp))  $sdpUpdate['foto_ktp']  = $fotoKtp;
                    if (empty($sdpFotoDiri) && !empty($fotoDiri)) $sdpUpdate['foto_diri'] = $fotoDiri;

                    if (!empty($sdpUpdate)) {
                        DataKunjungan::where('no_identitas', $nik)
                            ->where(function ($q) use ($sdpUpdate) {
                                if (isset($sdpUpdate['foto_ktp'])) {
                                    $q->orWhereNull('foto_ktp')->orWhere('foto_ktp', '');
                                }
                                if (isset($sdpUpdate['foto_diri'])) {
                                    $q->orWhereNull('foto_diri')->orWhere('foto_diri', '');
                                }
                            })
                            ->update($sdpUpdate);

                        $fields = implode(', ', array_keys($sdpUpdate));
                        $this->addLog($logs, 'info', "UPDATE SDP foto | NIK: {$nik} | Field: {$fields} (dari SIPIRMAN)");
                    }
                }

                
                if (isset($penitipCompositeKeys[$compositeKey])) {

                    $sip    = $penitipCompositeKeys[$compositeKey];
                    $update = $this->buildUpdatePayload($sip, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);

                    if (!empty($update)) {
                        DB::connection('sipirman')->table('penitip')
                            ->where('id', $sip->id)
                            ->update($update);

                        foreach ($update as $fld => $val) {
                            $penitipCompositeKeys[$compositeKey]->$fld = $val;
                            if (isset($penitipByNik[$nik])) {
                                $penitipByNik[$nik]->$fld = $val;
                            }
                        }

                        $stats['update_sipirman']++;
                        $this->addLog(
                            $logs,
                            'info',
                            "UPDATE SIPIRMAN | NIK: {$nik} | Nama: {$nama} | Field: " . implode(', ', array_keys($update))
                        );
                    } else {
                        $stats['skip']++;
                    }
                } else {

                    $newRow                              = $this->buildPenitipRow($nik, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);
                    $insertBatch[]                       = $newRow;
                    $penitipCompositeKeys[$compositeKey] = (object) $newRow;

                    if (!isset($penitipByNik[$nik])) {
                        $penitipByNik[$nik] = (object) $newRow;
                    }

                    $stats['insert_sipirman']++;
                    $this->addLog(
                        $logs,
                        'info',
                        "INSERT SIPIRMAN | NIK: {$nik} | Nama: {$nama} | WBP: {$namaWbp} | Tanggal: {$jadwal}"
                    );
                }
            } catch (\Throwable $e) {
                $stats['error']++;
                $nikLog = isset($nik) ? $nik : '-';
                $this->addLog($logs, 'error', 'AutoSync [' . $nikLog . ']: ' . $e->getMessage());
                Log::error('AutoSync error', ['nik' => $nikLog, 'error' => $e->getMessage()]);
            }
        }

        if (!empty($insertBatch)) {
            $this->bulkInsertWithFallback($insertBatch, $stats, $logs);
            $this->addLog($logs, 'success', $stats['insert_sipirman'] . ' data baru dikirim ke SIPIRMAN.');
        }

        $this->syncFotoSdpDariSipirman($niks, $logs, $stats);

        $this->addLog(
            $logs,
            'success',
            "AutoSync selesai | Insert: {$stats['insert_sipirman']} | Update: {$stats['update_sipirman']} | Skip: {$stats['skip']} | Error: {$stats['error']}"
        );

        return ['stats' => $stats, 'logs' => $logs];
    }

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

            $this->addLog($logs, 'info', 'Memulai sinkronisasi database...');

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

            $this->addLog($logs, 'info', 'Memuat data SIPIRMAN ke memori...');

            $penitipRows = DB::connection('sipirman')
                ->table('penitip')
                ->select('id', 'nik', 'nama', 'hp', 'nama_wbp', 'jadwal_kunjungan', 'foto', 'foto_ktp')
                ->whereNotNull('nik')
                ->get();

            $penitipByNik         = [];
            $penitipCompositeKeys = [];

            foreach ($penitipRows as $p) {
                $nik = trim($p->nik ?? '');
                if ($nik === '') continue;

                $tgl = $this->formatTanggal($p->jadwal_kunjungan ?? null) ?? '__nodate__';
                $penitipCompositeKeys[$nik . '|' . $tgl] = $p;

                if (!isset($penitipByNik[$nik]) || (empty($penitipByNik[$nik]->foto_ktp) && !empty($p->foto_ktp))) {
                    $penitipByNik[$nik] = $p;
                }
                if (isset($penitipByNik[$nik]) && empty($penitipByNik[$nik]->foto) && !empty($p->foto)) {
                    $penitipByNik[$nik]->foto = $p->foto;
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
            )->whereNotNull('no_identitas')->get();

            $kunjunganCompositeKeys = [];
            $kunjunganByNik         = [];

            foreach ($kunjunganRows as $k) {
                $nik = trim($k->no_identitas ?? '');
                if ($nik === '') continue;
                $tgl = $this->formatTanggal($k->getRawOriginal('waktu_kunjungan') ?? null) ?? '__nodate__';
                $kunjunganCompositeKeys[$nik . '|' . $tgl] = $k;

                if (!isset($kunjunganByNik[$nik]) || (empty($kunjunganByNik[$nik]->foto_ktp) && !empty($k->foto_ktp))) {
                    $kunjunganByNik[$nik] = $k;
                }
                if (isset($kunjunganByNik[$nik]) && empty($kunjunganByNik[$nik]->foto_diri) && !empty($k->foto_diri)) {
                    $kunjunganByNik[$nik]->foto_diri = $k->foto_diri;
                }
            }

            $this->addLog($logs, 'info', count($kunjunganByNik) . ' data kunjungan dimuat.');

            $this->addLog($logs, 'info', 'Sinkronisasi DATA KUNJUNGAN → SIPIRMAN...');

            $self = $this;

            DataKunjungan::whereNotNull('no_identitas')
                ->chunk(300, function ($rows) use (&$logs, &$stats, &$penitipByNik, &$penitipCompositeKeys, &$kunjunganByNik, $self) {

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
                            $tglKey   = $jadwal ?? '__nodate__';
                            $compositeKey = $nik . '|' . $tglKey;

                            $sipFoto = $penitipByNik[$nik] ?? null;
                            if ($sipFoto) {
                                if (empty($fotoKtp)  && !empty($sipFoto->foto_ktp)) {
                                    $fotoKtp = $sipFoto->foto_ktp;
                                    DataKunjungan::where('id', $k->id)->update(['foto_ktp' => $fotoKtp]);
                                    $stats['update_foto_ktp']++;
                                    $stats['detail_foto_ktp'][] = ['nik' => $nik, 'nama' => $k->pengunjung ?? '-', 'arah' => 'SIPIRMAN→SDP'];
                                    $self->addLog($logs, 'info', "UPDATE SDP foto_ktp | NIK: {$nik} | Nama: " . ($k->pengunjung ?? '-') . " (dari SIPIRMAN)");
                                }
                                if (empty($fotoDiri) && !empty($sipFoto->foto)) {
                                    $fotoDiri = $sipFoto->foto;
                                    DataKunjungan::where('id', $k->id)->update(['foto_diri' => $fotoDiri]);
                                    $stats['update_foto_diri']++;
                                    $stats['detail_foto_diri'][] = ['nik' => $nik, 'nama' => $k->pengunjung ?? '-', 'arah' => 'SIPIRMAN→SDP'];
                                    $self->addLog($logs, 'info', "UPDATE SDP foto_diri | NIK: {$nik} | Nama: " . ($k->pengunjung ?? '-') . " (dari SIPIRMAN)");
                                }
                            }

                            if (isset($penitipCompositeKeys[$compositeKey])) {

                                $sip    = $penitipCompositeKeys[$compositeKey];
                                $update = $self->buildUpdatePayload($sip, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);

                                if (!empty($update)) {
                                    if (isset($update['foto_ktp'])) {
                                        $stats['update_foto_ktp']++;
                                        $stats['detail_foto_ktp'][] = ['nik' => $nik, 'nama' => $sip->nama ?? '-', 'arah' => 'SDP→SIPIRMAN'];
                                    }
                                    if (isset($update['foto'])) {
                                        $stats['update_foto_diri']++;
                                        $stats['detail_foto_diri'][] = ['nik' => $nik, 'nama' => $sip->nama ?? '-', 'arah' => 'SDP→SIPIRMAN'];
                                    }

                                    DB::connection('sipirman')->table('penitip')
                                        ->where('id', $sip->id)
                                        ->update($update);

                                    foreach ($update as $fld => $val) {
                                        $penitipCompositeKeys[$compositeKey]->$fld = $val;
                                        if (isset($penitipByNik[$nik])) $penitipByNik[$nik]->$fld = $val;
                                    }

                                    $stats['update_sipirman']++;
                                    $self->addLog(
                                        $logs,
                                        'info',
                                        "UPDATE SIPIRMAN | NIK: {$nik} | Nama: " . ($sip->nama ?? '-') . " | Field: " . implode(', ', array_keys($update))
                                    );
                                } else {
                                    $stats['skip']++;
                                }
                            } else {

                                $newRow = $self->buildPenitipRow($nik, $nama, $hp, $namaWbp, $jadwal, $fotoKtp, $fotoDiri);
                                $insertBatch[]                       = $newRow;
                                $penitipCompositeKeys[$compositeKey] = (object) $newRow;
                                if (!isset($penitipByNik[$nik])) $penitipByNik[$nik] = (object) $newRow;
                                $stats['insert_sipirman']++;

                                $self->addLog(
                                    $logs,
                                    'info',
                                    "INSERT SIPIRMAN | NIK: {$nik} | Nama: {$nama} | WBP: {$namaWbp} | Tanggal: {$jadwal}"
                                );
                            }
                        } catch (\Throwable $e) {
                            $stats['error']++;
                            $self->addLog(
                                $logs,
                                'error',
                                'SDP→SIPIRMAN [' . ($k->no_identitas ?? '-') . ']: ' . $e->getMessage() . ' | line:' . $e->getLine()
                            );
                            Log::error('SDP→SIPIRMAN error', ['nik' => $k->no_identitas ?? '-', 'error' => $e->getMessage()]);
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
                "Selesai SDP→SIPIRMAN | Insert: {$stats['insert_sipirman']} | Update: {$stats['update_sipirman']} | Skip: {$stats['skip']} | Error: {$stats['error']}"
            );

            $this->addLog($logs, 'info', 'Sinkronisasi SIPIRMAN → DATA KUNJUNGAN...');

            $penitipRowsFresh = DB::connection('sipirman')
                ->table('penitip')
                ->select('id', 'nik', 'nama', 'hp', 'nama_wbp', 'jadwal_kunjungan', 'foto', 'foto_ktp')
                ->whereNotNull('nik')
                ->get();

            $penitipByNikFresh = [];
            foreach ($penitipRowsFresh as $p) {
                $nik = trim($p->nik ?? '');
                if ($nik === '') continue;
                if (!isset($penitipByNikFresh[$nik]) || (empty($penitipByNikFresh[$nik]->foto_ktp) && !empty($p->foto_ktp))) {
                    $penitipByNikFresh[$nik] = $p;
                }
                if (isset($penitipByNikFresh[$nik]) && empty($penitipByNikFresh[$nik]->foto) && !empty($p->foto)) {
                    $penitipByNikFresh[$nik]->foto = $p->foto;
                }
            }

            $insertKunjunganBatch = [];

            foreach ($penitipRowsFresh as $p) {
                try {
                    $nik = trim($p->nik ?? '');
                    if ($nik === '') continue;

                    $tanggal = $this->formatTanggal($p->jadwal_kunjungan ?? null);
                    $namaWbp = $this->cleanNamaWbp($p->nama_wbp ?? null);
                    $tglKey  = $tanggal ?? '__nodate__';
                    $compositeKey = $nik . '|' . $tglKey;

                    $kunjungan = $kunjunganCompositeKeys[$compositeKey] ?? null;

                    if (!$kunjungan) {

                        $insertKunjunganBatch[]                       = [
                            'no'              => mt_rand(100000, 999999),
                            'wbp'             => $namaWbp,
                            'pengunjung'      => $p->nama     ?? null,
                            'no_identitas'    => $nik,
                            'catatan'         => $p->hp       ?? null,
                            'waktu_kunjungan' => $tanggal,
                            'foto_ktp'        => $p->foto_ktp ?? null,
                            'foto_diri'       => $p->foto     ?? null,
                        ];
                        $kunjunganCompositeKeys[$compositeKey] = (object) end($insertKunjunganBatch);
                        if (!isset($kunjunganByNik[$nik])) {
                            $kunjunganByNik[$nik] = (object) end($insertKunjunganBatch);
                        }
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
                        $rawTgl = method_exists($kunjungan, 'getRawOriginal')
                            ? $kunjungan->getRawOriginal('waktu_kunjungan')
                            : ($kunjungan->waktu_kunjungan ?? null);

                        if (empty($rawWbp)                && !empty($namaWbp))     $update['wbp']             = $namaWbp;
                        if (empty($kunjungan->catatan)    && !empty($p->hp))       $update['catatan']         = $p->hp;
                        if (empty($kunjungan->pengunjung) && !empty($p->nama))     $update['pengunjung']      = $p->nama;
                        if (empty($rawTgl)                && !empty($tanggal))     $update['waktu_kunjungan'] = $tanggal;

                        if (empty($kunjungan->foto_ktp)  && !empty($p->foto_ktp)) {
                            $update['foto_ktp']  = $p->foto_ktp;
                            $stats['update_foto_ktp']++;
                            $stats['detail_foto_ktp'][] = ['nik' => $nik, 'nama' => $p->nama ?? '-', 'arah' => 'SIPIRMAN→SDP'];
                        }
                        if (empty($kunjungan->foto_diri) && !empty($p->foto)) {
                            $update['foto_diri'] = $p->foto;
                            $stats['update_foto_diri']++;
                            $stats['detail_foto_diri'][] = ['nik' => $nik, 'nama' => $p->nama ?? '-', 'arah' => 'SIPIRMAN→SDP'];
                        }

                        $sdpFotoKtp  = $kunjunganByNik[$nik]->foto_ktp  ?? null;
                        $sdpFotoDiri = $kunjunganByNik[$nik]->foto_diri ?? null;
                        $sipUpdate   = [];

                        if (empty($p->foto_ktp) && !empty($sdpFotoKtp)) {
                            $sipUpdate['foto_ktp'] = $this->safeTrunc($sdpFotoKtp, 100);
                            $stats['update_foto_ktp']++;
                            $stats['detail_foto_ktp'][] = ['nik' => $nik, 'nama' => $p->nama ?? '-', 'arah' => 'SDP→SIPIRMAN'];
                        }
                        if (empty($p->foto) && !empty($sdpFotoDiri)) {
                            $sipUpdate['foto'] = $this->safeTrunc($sdpFotoDiri, 100);
                            $stats['update_foto_diri']++;
                            $stats['detail_foto_diri'][] = ['nik' => $nik, 'nama' => $p->nama ?? '-', 'arah' => 'SDP→SIPIRMAN'];
                        }

                        if (!empty($sipUpdate)) {
                            DB::connection('sipirman')->table('penitip')
                                ->where('id', $p->id)
                                ->update($sipUpdate);
                            $this->addLog(
                                $logs,
                                'info',
                                "UPDATE SIPIRMAN foto | NIK: {$nik} | Field: " . implode(', ', array_keys($sipUpdate)) . " (dari SDP)"
                            );
                        }

                        if (!empty($update)) {
                            if (method_exists($kunjungan, 'getKey') && $kunjungan->getKey()) {
                                DataKunjungan::where('id', $kunjungan->getKey())->update($update);
                            } else {
                                DataKunjungan::where('no_identitas', $nik)
                                    ->when($tanggal, fn($q) => $q->whereDate('waktu_kunjungan', $tanggal))
                                    ->update($update);
                            }
                            $stats['update_kunjungan']++;
                            $this->addLog(
                                $logs,
                                'info',
                                "UPDATE DataKunjungan | NIK: {$nik} | Nama: " . ($p->nama ?? '-') . " | Field: " . implode(', ', array_keys($update))
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

    public function syncFotoSdpDariSipirman(array $niks, array &$logs, array &$stats): void
    {
        if (empty($niks)) return;

        try {
            $sipFotos = DB::connection('sipirman')
                ->table('penitip')
                ->select('nik', 'foto_ktp', 'foto')
                ->whereIn('nik', array_values($niks))
                ->whereRaw("(foto_ktp IS NOT NULL AND foto_ktp != '') OR (foto IS NOT NULL AND foto != '')")
                ->get()
                ->groupBy('nik')
                ->map(function ($rows) {
                    $fotoKtp  = null;
                    $fotoDiri = null;
                    foreach ($rows as $r) {
                        if (empty($fotoKtp)  && !empty($r->foto_ktp)) $fotoKtp  = $r->foto_ktp;
                        if (empty($fotoDiri) && !empty($r->foto))     $fotoDiri = $r->foto;
                        if ($fotoKtp && $fotoDiri) break;
                    }
                    return ['foto_ktp' => $fotoKtp, 'foto_diri' => $fotoDiri];
                });

            if ($sipFotos->isEmpty()) return;

            foreach ($sipFotos as $nik => $fotos) {
                if (!empty($fotos['foto_ktp'])) {
                    $updated = DataKunjungan::where('no_identitas', $nik)
                        ->where(fn($q) => $q->whereNull('foto_ktp')->orWhere('foto_ktp', ''))
                        ->update(['foto_ktp' => $fotos['foto_ktp']]);

                    if ($updated > 0) {
                        $stats['update_foto_ktp'] = ($stats['update_foto_ktp'] ?? 0) + $updated;
                        $this->addLog($logs, 'info', "FILL SDP foto_ktp | NIK: {$nik} | {$updated} baris (dari SIPIRMAN)");
                    }
                }

                if (!empty($fotos['foto_diri'])) {
                    $updated = DataKunjungan::where('no_identitas', $nik)
                        ->where(fn($q) => $q->whereNull('foto_diri')->orWhere('foto_diri', ''))
                        ->update(['foto_diri' => $fotos['foto_diri']]);

                    if ($updated > 0) {
                        $stats['update_foto_diri'] = ($stats['update_foto_diri'] ?? 0) + $updated;
                        $this->addLog($logs, 'info', "FILL SDP foto_diri | NIK: {$nik} | {$updated} baris (dari SIPIRMAN)");
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->addLog($logs, 'error', 'syncFotoSdpDariSipirman gagal: ' . $e->getMessage());
            Log::error('syncFotoSdpDariSipirman error', ['error' => $e->getMessage()]);
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

            $cleanPath = ltrim($path, '/');
            foreach (['ktp/', 'diri/', 'foto_diri/', 'foto_ktp/'] as $prefix) {
                if (str_starts_with($cleanPath, $prefix)) {
                    $cleanPath = substr($cleanPath, strlen($prefix));
                    break;
                }
            }

            $candidates = array_unique([
                public_path('storage/' . ltrim($path, '/')),
                public_path("storage/{$folder}/{$cleanPath}"),
                public_path("storage/ktp/{$cleanPath}"),
                public_path("storage/diri/{$cleanPath}"),
                public_path("storage/foto_diri/{$cleanPath}"),
                public_path("storage/{$cleanPath}"),
            ]);

            foreach ($candidates as $fullPath) {
                if (file_exists($fullPath)) {
                    return response()->file($fullPath);
                }
            }

            Log::warning('Foto tidak ditemukan', ['path' => $path, 'folder' => $folder, 'candidates' => $candidates]);
            abort(404);
        } catch (\Throwable $e) {
            abort(404);
        }
    }

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

        if (empty(trim($sip->foto_ktp         ?? '')) && !empty($fotoKtp))  $update['foto_ktp']         = $this->safeTrunc($fotoKtp, 100);
        if (empty(trim($sip->foto             ?? '')) && !empty($foto))      $update['foto']             = $this->safeTrunc($foto,    100);
        if (empty(trim($sip->hp               ?? '')) && !empty($hp))        $update['hp']               = $this->safeTrunc($hp,      13);
        if (empty(trim($sip->nama             ?? '')) && !empty($nama))      $update['nama']             = $this->safeTrunc($nama,    100);
        if (empty(trim($sip->nama_wbp         ?? '')) && !empty($namaWbp))   $update['nama_wbp']         = $this->safeTrunc($namaWbp, 100);
        if (empty(trim($sip->jadwal_kunjungan ?? '')) && !empty($jadwal))    $update['jadwal_kunjungan'] = $jadwal;

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

    public function addLog(array &$logs, string $level, string $message): void
    {
        $logs[] = [
            'level'   => $level,
            'message' => $message,
            'time'    => now()->format('H:i:s'),
        ];
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
