<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $judulDokumen }}</title>
    <style>
        @page {
            margin: 1.8cm 2cm 2cm 2cm;
            size: a4 portrait;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.45;
            color: #1a202c;
            margin: 0;
            padding: 0;
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
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .kop-title-2 {
            font-size: 14pt;
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

        /* Title */
        .doc-title {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 18px;
        }
        .doc-title h1 {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0 0 4px 0;
            text-decoration: underline;
        }
        .doc-title p {
            font-size: 9pt;
            color: #4b5563;
            margin: 0;
        }

        /* Sections */
        h2.section-header {
            font-size: 10.5pt;
            font-weight: bold;
            background-color: #f1f5f9;
            border-left: 4px solid #0f766e;
            padding: 4px 8px;
            margin-top: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .meta-table td {
            padding: 4px 6px;
            vertical-align: top;
            font-size: 9.5pt;
        }
        .meta-label {
            width: 25%;
            font-weight: 600;
            color: #374151;
        }
        .meta-separator {
            width: 3%;
            text-align: center;
        }
        .meta-value {
            width: 72%;
            color: #111827;
        }

        .content-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 9.5pt;
            line-height: 1.5;
            color: #1f2937;
            text-align: justify;
            margin-bottom: 12px;
        }

        /* Data table */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 14px;
        }
        .data-table th, .data-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 8px;
            font-size: 9pt;
        }
        .data-table th {
            background-color: #f1f5f9;
            font-weight: bold;
            text-align: center;
            color: #1e293b;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Badge status */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 8.5pt;
            font-weight: bold;
            border-radius: 3px;
        }
        .status-disetujui {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .status-diajukan {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }
        .status-draft {
            background-color: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        .status-ditolak {
            background-color: #ffe4e6;
            color: #9f1239;
            border: 1px solid #fca5a5;
        }

        /* Pengesahan */
        .pengesahan-table {
            width: 100%;
            margin-top: 25px;
            border-collapse: collapse;
        }
        .pengesahan-table td {
            width: 50%;
            vertical-align: top;
            text-align: center;
            font-size: 9pt;
            line-height: 1.35;
        }
        .qr-box {
            display: inline-block;
            padding: 4px;
            border: 1px solid #cbd5e1;
            background: #fff;
            margin: 6px 0;
        }
        .qr-caption {
            font-size: 6.5pt;
            font-weight: bold;
            color: #0f766e;
            letter-spacing: 0.5px;
            margin-top: 2px;
        }
        .legal-notice {
            margin-top: 20px;
            padding: 8px 10px;
            border-top: 1px dashed #cbd5e1;
            font-size: 7.5pt;
            color: #64748b;
            text-align: justify;
            line-height: 1.3;
        }
    </style>
</head>
<body>

    {{-- KOP SURAT RESMI PEMKAB GARUT --}}
    <table class="kop-table">
        <tr>
            <td style="width: 15%; text-align: center;">
                @if (!empty($logoBase64))
                    <img src="{{ $logoBase64 }}" width="72" height="72" alt="Logo Pemkab Garut">
                @else
                    <div style="width: 70px; height: 70px; border: 1px dashed #999; margin: 0 auto; line-height: 70px; font-size: 8pt;">GARUT</div>
                @endif
            </td>
            <td style="width: 85%;" class="kop-text">
                <div class="kop-title-1">Pemerintah Kabupaten Garut</div>
                <div class="kop-title-2">Kecamatan Malangbong</div>
                <div class="kop-address">Jl. Raya Malangbong No. 123, Malangbong, Kabupaten Garut, Jawa Barat 44188</div>
                <div class="kop-address">Laman Resmi: kec-malangbong.garutkab.go.id | Email: kec.malangbong@garutkab.go.id</div>
            </td>
        </tr>
    </table>

    {{-- JUDUL DOKUMEN --}}
    <div class="doc-title">
        <h1>LAPORAN KINERJA UNIT KERJA</h1>
        <p>Nomor Registrasi: SI-PELAJAR/V2/{{ $record->periode_tahun }}/{{ str_pad($record->periode_bulan, 2, '0', STR_PAD_LEFT) }}/{{ str_pad($record->id, 4, '0', STR_PAD_LEFT) }}</p>
    </div>

    {{-- I. INFORMASI PELAPORAN --}}
    <h2 class="section-header">I. Informasi & Periode Pelaporan</h2>
    <table class="meta-table">
        <tr>
            <td class="meta-label">Judul / Agenda Pelaporan</td>
            <td class="meta-separator">:</td>
            <td class="meta-value font-bold">{{ $record->judul_pelaporan }}</td>
        </tr>
        <tr>
            <td class="meta-label">Unit Kerja / Organisasi</td>
            <td class="meta-separator">:</td>
            <td class="meta-value font-bold" style="color: #0f766e;">{{ $record->unitOrganisasi?->nama_unit ?? '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Periode Pelaporan</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $record->nama_bulan }} {{ $record->periode_tahun }}</td>
        </tr>
        <tr>
            <td class="meta-label">Tanggal Pelaporan</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $record->tanggal_pelaporan ? $record->tanggal_pelaporan->translatedFormat('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td class="meta-label">Status Verifikasi</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">
                <span class="status-badge status-{{ $record->status }}">
                    {{ $record->status_label }}
                </span>
            </td>
        </tr>
    </table>

    {{-- II. PEJABAT PENANGGUNG JAWAB --}}
    <h2 class="section-header">II. Pejabat Penanggung Jawab Pelapor</h2>
    <table class="meta-table">
        <tr>
            <td class="meta-label">Nama Lengkap Pejabat</td>
            <td class="meta-separator">:</td>
            <td class="meta-value font-bold">{{ $record->nama_pejabat }}</td>
        </tr>
        <tr>
            <td class="meta-label">Nomor Induk Pegawai (NIP)</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $record->nip_pejabat }}</td>
        </tr>
        <tr>
            <td class="meta-label">Jabatan Kedinasan</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ $record->jabatan_pejabat }}</td>
        </tr>
    </table>

    {{-- III. RINGKASAN PELAKSANAAN KEGIATAN & KINERJA --}}
    <h2 class="section-header">III. Uraian Pelaksanaan Kinerja & Kegiatan</h2>
    <div class="content-box">
        @if (!empty($record->ringkasan_kegiatan))
            {!! nl2br(e($record->ringkasan_kegiatan)) !!}
        @else
            Unit kerja telah melaksanakan seluruh program tugas pokok dan fungsi operasional, pelayanan kepada masyarakat, serta penyusunan administrasi kedinasan sesuai dengan target kerja periode {{ $record->nama_bulan }} {{ $record->periode_tahun }}. Rincian pelaksanaan kegiatan dan output kerja terlampir secara lengkap dalam dokumen bukti dukung fisik terlampir.
        @endif
    </div>

    {{-- IV. DAFTAR DOKUMEN BUKTI DUKUNG TERLAMPIR --}}
    <h2 class="section-header">IV. Berkas Bukti Dukung yang Dilampirkan</h2>
    @php
        $files = $record->bukti_dukung_details;
    @endphp
    @if (!empty($files) && count($files) > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%;">No</th>
                    <th style="width: 54%;">Nama Berkas / Dokumen</th>
                    <th style="width: 20%;">Format Berkas</th>
                    <th style="width: 20%;">Keterangan Arsip</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($files as $idx => $file)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="font-bold">{{ $file['name'] }}</td>
                        <td class="text-center">{{ $file['ext'] ?: 'DOKUMEN' }}</td>
                        <td class="text-center" style="color: #059669; font-weight: 600;">Terverifikasi Digital</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <div class="content-box" style="color: #64748b; font-style: italic;">
            Tidak ada dokumen lampiran bukti dukung tambahan yang diunggah untuk pelaporan ini.
        </div>
    @endif

    {{-- V. CATATAN TELAAH & VERIFIKASI RESMI --}}
    <h2 class="section-header">V. Catatan Telaah & Verifikasi Sekretariat</h2>
    <div class="content-box">
        <strong>Status Hasil Telaah:</strong> {{ $record->status_label }}<br>
        <strong>Diverifikasi Oleh:</strong> {{ $record->verifier?->name ?? 'Sekretaris Camat Malangbong' }}<br>
        <strong>Waktu Verifikasi:</strong> {{ $record->verified_at ? $record->verified_at->translatedFormat('d F Y, H:i') . ' WIB' : 'Menunggu Verifikasi' }}<br>
        <div style="margin-top: 6px;">
            <strong>Catatan Verifikator:</strong><br>
            <span style="color: #374151;">
                {{ $record->catatan_verifikasi ?: 'Laporan kinerja dan berkas bukti dukung telah diteliti secara administratif serta memenuhi kelayakan akuntabilitas kinerja Kecamatan Malangbong.' }}
            </span>
        </div>
    </div>

    {{-- LEMBAR PENGESAHAN DENGAN QR CODE DIGITAL --}}
    <table class="pengesahan-table">
        <tr>
            <td>
                Mengetahui / Memverifikasi:<br>
                <strong>SEKRETARIS CAMAT MALANGBONG</strong>
                <br><br>
                <div class="qr-box">
                    <img src="data:image/svg+xml;base64,{{ $qrCodeSekmat }}" width="80" height="80" alt="QR Verifikasi Sekmat">
                    <div class="qr-caption">TERVERIFIKASI SI-PELAJAR</div>
                </div>
                <br>
                <strong><u>{{ $namaSekmat }}</u></strong><br>
                NIP. {{ $nipSekmat }}
            </td>
            <td>
                Malangbong, {{ $tanggalPengesahan }}<br>
                Pejabat Pembuat Laporan:<br>
                <strong>{{ strtoupper($record->jabatan_pejabat) }}</strong>
                <br><br>
                <div class="qr-box">
                    <img src="data:image/svg+xml;base64,{{ $qrCodePelapor }}" width="80" height="80" alt="QR Pejabat Pelapor">
                    <div class="qr-caption">TANDA TANGAN RESMI</div>
                </div>
                <br>
                <strong><u>{{ $record->nama_pejabat }}</u></strong><br>
                NIP. {{ $record->nip_pejabat }}
            </td>
        </tr>
    </table>

    <div class="legal-notice">
        <strong>Catatan Keabsahan Dokumen Elektronik:</strong> Dokumen ini merupakan berkas resmi Laporan Kinerja Unit Kerja yang diterbitkan dan diverifikasi secara elektronik melalui Sistem Informasi Pelaporan Kinerja (SI-PELAJAR) Pemerintah Kabupaten Garut - Kecamatan Malangbong. Keabsahan pengesahan dan integritas dokumen dijamin dengan kode verifikasi QR terenkripsi sesuai ketentuan peraturan perundang-undangan.
    </div>

</body>
</html>
