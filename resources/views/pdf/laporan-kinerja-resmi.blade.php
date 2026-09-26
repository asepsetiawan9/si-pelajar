<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judulDokumen }}</title>
    <style>
        @page {
            margin: 2cm 2cm 2cm 2cm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.45;
            color: #1a202c;
            margin: 0;
            padding: 0;
        }
        .page-break {
            page-break-after: always;
        }
        .cover-page {
            text-align: center;
            height: 100%;
            padding-top: 50px;
        }
        .cover-title {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 40px;
            margin-bottom: 10px;
        }
        .cover-subtitle {
            font-size: 14pt;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 25px;
        }
        .cover-instansi {
            font-size: 13pt;
            font-weight: 600;
            margin-top: 60px;
            text-transform: uppercase;
        }
        .cover-year {
            font-size: 15pt;
            font-weight: bold;
            margin-top: 15px;
        }

        /* Kop Surat Resmi */
        .kop-table {
            width: 100%;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 18px;
        }
        .kop-table td {
            vertical-align: middle;
        }
        .kop-text {
            text-align: center;
        }
        .kop-title-1 {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .kop-title-2 {
            font-size: 15pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 2px 0;
        }
        .kop-address {
            font-size: 8.5pt;
            margin: 0;
            color: #333;
        }

        /* Heading sections */
        h2.section-header {
            font-size: 11pt;
            font-weight: bold;
            background-color: #f1f5f9;
            border-left: 4px solid #1e3a8a;
            padding: 5px 10px;
            margin-top: 16px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }
        h3.subsection-header {
            font-size: 10.5pt;
            font-weight: bold;
            margin-top: 12px;
            margin-bottom: 6px;
            color: #1e293b;
        }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            margin-bottom: 14px;
            font-size: 9.5pt;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            vertical-align: top;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: 700;
            text-align: center;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8pt;
            font-weight: 600;
            border-radius: 3px;
        }
        .badge-sangat-efektif { background-color: #dcfce7; color: #15803d; }
        .badge-efektif { background-color: #dbeafe; color: #1d4ed8; }
        .badge-cukup-efektif { background-color: #fef9c3; color: #a16207; }
        .badge-tidak-efektif { background-color: #fee2e2; color: #b91c1c; }

        .badge-sangat-efisien { background-color: #dcfce7; color: #15803d; }
        .badge-efisien { background-color: #dbeafe; color: #1d4ed8; }
        .badge-cukup-efisien { background-color: #fef9c3; color: #a16207; }
        .badge-tidak-efisien { background-color: #fee2e2; color: #b91c1c; }

        /* Pengesahan */
        .pengesahan-table {
            width: 100%;
            margin-top: 35px;
            page-break-inside: avoid;
        }
        .pengesahan-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
        }
        .qr-box {
            margin: 8px auto;
            display: inline-block;
            border: 1px dashed #94a3b8;
            padding: 4px;
            background: #fafafa;
        }
        .qr-caption {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 3px;
        }
        .legal-notice {
            margin-top: 25px;
            padding: 8px;
            font-size: 7.5pt;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            text-align: justify;
        }
    </style>
</head>
<body>

    {{-- COVER PAGE --}}
    @if ($showCover ?? true)
    <div class="cover-page page-break">
        <div style="font-size: 13pt; letter-spacing: 2px; color: #475569; font-weight: bold; margin-bottom: 20px;">
            DOKUMEN AKUNTABILITAS KINERJA INSTANSI PEMERINTAH
        </div>

        <div style="margin: 25px auto;">
            {{-- Lambang Resmi Pemkab Garut --}}
            @if (!empty($logoBase64))
                <img src="{{ $logoBase64 }}" style="width: 85px; height: auto;" alt="Lambang Daerah Garut">
            @else
                <div style="width: 80px; height: 80px; margin: 0 auto; border: 2px solid #1e3a8a; border-radius: 50%; line-height: 80px; font-weight: bold; color: #1e3a8a; font-size: 12pt;">
                    GARUT
                </div>
            @endif
        </div>

        <div class="cover-title">
            LAPORAN CAPAIAN KINERJA BULANAN
        </div>
        <div class="cover-subtitle">
            PERIODE: {{ strtoupper($periodeBulan) }}
        </div>

        <div style="width: 160px; height: 3px; background-color: #1e3a8a; margin: 20px auto;"></div>

        <div style="font-size: 11pt; color: #334155; margin-top: 25px;">
            Berdasarkan Peraturan Menteri PAN-RB No. 53 Tahun 2014 & No. 22 Tahun 2024
        </div>

        <div class="cover-instansi">
            {{ $namaUnitKerja }}<br>
            {{ config('spko.instansi.kecamatan', 'Kecamatan Malangbong') }}<br>
            {{ config('spko.instansi.pemerintah', 'Pemerintah Kabupaten Garut') }}
        </div>

        <div class="cover-year">
            TAHUN {{ $tahun }}
        </div>
    </div>
    @endif

    {{-- KOP SURAT RESMI PEMKAB GARUT --}}
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                @if (!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" style="width: 58px; height: auto;" alt="Logo Garut">
                @else
                    <div style="width: 60px; height: 60px; border: 2px solid #1e293b; border-radius: 50%; line-height: 60px; font-weight: bold; font-size: 9pt; margin: 0 auto;">
                        GARUT
                    </div>
                @endif
            </td>
            <td style="width: 85%;" class="kop-text">
                <div class="kop-title-1">{{ strtoupper(config('spko.instansi.pemerintah', 'PEMERINTAH KABUPATEN GARUT')) }}</div>
                <div class="kop-title-2">{{ strtoupper(config('spko.instansi.kecamatan', 'KECAMATAN MALANGBONG')) }}</div>
                <div class="kop-address">{{ config('spko.instansi.alamat') }}</div>
                <div class="kop-address">Pos-el: {{ config('spko.instansi.email') }} | Laman: garutkab.go.id</div>
            </td>
        </tr>
    </table>

    <div style="text-align: center; margin-bottom: 20px;">
        <span style="font-size: 12pt; font-weight: bold; text-decoration: underline;">
            LAPORAN CAPAIAN KINERJA BULAN {{ strtoupper($periodeBulan) }}
        </span><br>
        <span style="font-size: 10pt; color: #475569;">
            Unit Kerja: {{ $namaUnitKerja }}
        </span>
    </div>

    {{-- A. LATAR BELAKANG --}}
    <h2 class="section-header">A. Latar Belakang & Dasar Hukum</h2>
    <div style="text-align: justify; text-indent: 30px; margin-bottom: 12px;">
        {{ $latarBelakang }}
    </div>

    {{-- B. SASARAN STRATEGIS & RENCANA AKSI --}}
    <h2 class="section-header">B. Sasaran Strategis & Rencana Aksi</h2>
    <div style="margin-bottom: 8px;">
        Pelaksanaan tugas pokok dan fungsi pada periode bulan {{ $periodeBulan }} mengacu pada Perjanjian Kinerja Camat Malangbong dan target rencana aksi unit kerja sebagai berikut:
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Sasaran Strategis Camat</th>
                <th style="width: 50%;">Rencana Aksi Unit Kerja</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($daftarRencanaAksi as $index => $item)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $item['sasaran'] }}</strong><br>
                    <small style="color: #64748b;">Indikator: {{ $item['sasaran_indikator'] }}</small>
                </td>
                <td>
                    <strong>{{ $item['rencana_aksi'] }}</strong><br>
                    <small style="color: #64748b;">Indikator Aksi: {{ $item['indikator_kinerja'] }}</small>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="text-center">Tidak ada data sasaran strategis.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- C. REALISASI CAPAIAN KINERJA FISIK --}}
    <h2 class="section-header">C. Realisasi Capaian Kinerja Fisik</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 45%;">Rencana Aksi & Indikator</th>
                <th style="width: 15%;">Target</th>
                <th style="width: 15%;">Realisasi</th>
                <th style="width: 20%;">Capaian (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($indikatorList as $index => $ind)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    <strong>{{ $ind['rencana_aksi'] }}</strong><br>
                    <small style="color: #64748b;">{{ $ind['indikator_kinerja'] }}</small>
                </td>
                <td class="text-center">{{ number_format($ind['target_kinerja'], 0, ',', '.') }} {{ $ind['satuan'] }}</td>
                <td class="text-center font-bold">{{ number_format($ind['realisasi_kinerja'], 0, ',', '.') }} {{ $ind['satuan'] }}</td>
                <td class="text-center font-bold">{{ number_format($ind['persentase_kinerja'], 2, ',', '.') }}%</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada indikator kinerja fisik.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- SUB-TABEL RINCIAN LAYANAN DINAMIS (JIKA ADA, CONTOH PATEN) --}}
    @if (!empty($layananList) && count($layananList) > 0)
    <h3 class="subsection-header">Rincian Volume Layanan / Pemohon Kegiatan:</h3>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 6%;">No</th>
                <th style="width: 54%;">Jenis Layanan / Kegiatan</th>
                <th style="width: 20%;">Jumlah Pemohon</th>
                <th style="width: 20%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @php $totalPemohon = 0; @endphp
            @foreach ($layananList as $idx => $layanan)
            @php $totalPemohon += $layanan['jumlah']; @endphp
            <tr>
                <td class="text-center">{{ $idx + 1 }}</td>
                <td>{{ $layanan['nama_layanan'] }}</td>
                <td class="text-center font-bold">{{ number_format($layanan['jumlah'], 0, ',', '.') }} {{ $layanan['satuan'] }}</td>
                <td>{{ $layanan['keterangan'] ?? '-' }}</td>
            </tr>
            @endforeach
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAL PEMOHON:</td>
                <td class="text-center">{{ number_format($totalPemohon, 0, ',', '.') }} Pemohon</td>
                <td>-</td>
            </tr>
        </tbody>
    </table>
    @endif

    {{-- D. ANALISIS EFEKTIVITAS KINERJA --}}
    <h2 class="section-header">D. Analisis Efektivitas Kinerja (Permenpan-RB 53/2014)</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 50%;">Indikator Kinerja</th>
                <th style="width: 20%;">Persentase</th>
                <th style="width: 25%;">Predikat Efektivitas</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($indikatorList as $index => $ind)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $ind['indikator_kinerja'] }}</td>
                <td class="text-center font-bold">{{ number_format($ind['persentase_kinerja'], 2, ',', '.') }}%</td>
                <td class="text-center">
                    <span class="badge badge-{{ str_replace('_', '-', $ind['predikat_efektivitas']) }}">
                        {{ ucwords(str_replace('_', ' ', $ind['predikat_efektivitas'])) }}
                    </span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div style="font-size: 8pt; color: #64748b; margin-top: -8px; margin-bottom: 12px;">
        * Kriteria Efektivitas: >100% (Sangat Efektif), 90-100% (Efektif), 60-89% (Cukup Efektif), <60% (Tidak Efektif).
    </div>

    {{-- E. ANALISIS EFISIENSI ANGGARAN --}}
    <h2 class="section-header">E. Analisis Efisiensi Realisasi Anggaran</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 35%;">Uraian Belanja / Kegiatan</th>
                <th style="width: 18%;">Pagu Anggaran</th>
                <th style="width: 18%;">Realisasi Belanja</th>
                <th style="width: 12%;">Serapan</th>
                <th style="width: 12%;">Predikat</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalPagu = 0;
                $totalRealisasi = 0;
            @endphp
            @foreach ($indikatorList as $index => $ind)
            @php
                $totalPagu += $ind['anggaran_pagu'];
                $totalRealisasi += $ind['realisasi_anggaran'];
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $ind['rencana_aksi'] }}</td>
                <td class="text-right">Rp {{ number_format($ind['anggaran_pagu'], 0, ',', '.') }}</td>
                <td class="text-right font-bold">Rp {{ number_format($ind['realisasi_anggaran'], 0, ',', '.') }}</td>
                <td class="text-center">{{ number_format($ind['persentase_anggaran'], 2, ',', '.') }}%</td>
                <td class="text-center">
                    <span class="badge badge-{{ str_replace('_', '-', $ind['predikat_efisiensi']) }}">
                        {{ ucwords(str_replace('_', ' ', $ind['predikat_efisiensi'])) }}
                    </span>
                </td>
            </tr>
            @endforeach
            <tr style="background-color: #f1f5f9; font-weight: bold;">
                <td colspan="2" class="text-right">TOTAL:</td>
                <td class="text-right">Rp {{ number_format($totalPagu, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($totalRealisasi, 0, ',', '.') }}</td>
                <td class="text-center">
                    {{ $totalPagu > 0 ? number_format(($totalRealisasi / $totalPagu) * 100, 2, ',', '.') : '0,00' }}%
                </td>
                <td class="text-center">-</td>
            </tr>
        </tbody>
    </table>
    <div style="font-size: 8pt; color: #64748b; margin-top: -8px; margin-bottom: 12px;">
        * Kriteria Efisiensi Serapan: <60% (Sangat Efisien), 60-90% (Efisien), 91-100% (Cukup Efisien), >100% (Tidak Efisien).
    </div>

    {{-- F. EVALUASI, KENDALA & SIMPULAN --}}
    <h2 class="section-header">F. Evaluasi Hambatan & Simpulan</h2>
    <div style="margin-bottom: 8px;">
        <strong>1. Keluhan & Aduan Masyarakat:</strong><br>
        <span style="color: #334155;">{{ $keluhanMasyarakat ?: 'Tidak terdapat aduan masyarakat yang signifikan pada periode ini.' }}</span>
    </div>
    <div style="margin-bottom: 8px;">
        <strong>2. Hambatan / Kendala Operasional:</strong><br>
        <span style="color: #334155;">{{ $hambatan ?: 'Secara umum seluruh kegiatan terlaksana sesuai rencana kerja operasional.' }}</span>
    </div>
    <div style="margin-bottom: 12px;">
        <strong>3. Simpulan Evaluasi Kinerja:</strong><br>
        <span style="color: #334155;">{{ $simpulan ?: 'Target kinerja periode ini tercapai dengan baik dan seluruh program kerja terlaksana secara akuntabel.' }}</span>
    </div>

    {{-- LEMBAR PENGESAHAN DENGAN QR CODE VERIFIKASI DIGITAL --}}
    <table class="pengesahan-table">
        <tr>
            <td>
                Mengetahui / Memverifikasi:<br>
                <strong>SEKRETARIS CAMAT MALANGBONG</strong>
                <br><br>
                <div class="qr-box">
                    <img src="data:image/svg+xml;base64,{{ $qrCodeSekmat }}" width="85" height="85" alt="QR Verifikasi Sekmat">
                    <div class="qr-caption">TERVERIFIKASI SISTEM SI-PELAJAR</div>
                </div>
                <br>
                <strong><u>{{ $namaSekmat }}</u></strong><br>
                NIP. {{ $nipSekmat }}
            </td>
            <td>
                Malangbong, {{ $tanggalPengesahan }}<br>
                Mengesahkan:<br>
                <strong>{{ strtoupper(config('spko.instansi.camat_jabatan', 'PLT. CAMAT MALANGBONG')) }}</strong>
                <br><br>
                <div class="qr-box">
                    <img src="data:image/svg+xml;base64,{{ $qrCodeCamat }}" width="85" height="85" alt="QR Pengesahan Camat">
                    <div class="qr-caption">DISAHKAN SECARA ELEKTRONIK</div>
                </div>
                <br>
                <strong><u>{{ config('spko.instansi.camat_nama', 'H. Robiul Awaludin, S.Sos., A.Kp., MM') }}</u></strong><br>
                NIP. {{ config('spko.instansi.camat_nip', '19700523199303 1 005') }}
            </td>
        </tr>
    </table>

    <div class="legal-notice">
        <strong>Catatan Keabsahan Dokumen Elektronik:</strong> Dokumen ini merupakan dokumen resmi akuntabilitas kinerja yang sah dan dicetak secara otomatis melalui Sistem Informasi Pelaporan Kinerja (SI-PELAJAR) Kecamatan Malangbong. Tanda tangan elektronik dibuktikan dengan kode QR terenkripsi yang dapat dipindai untuk memverifikasi keaslian pengesahan pejabat yang berwenang.
    </div>

</body>
</html>
