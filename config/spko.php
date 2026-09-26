<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Batas Waktu Cut-Off Pelaporan Kinerja Bulanan
    |--------------------------------------------------------------------------
    |
    | Setiap tanggal 10 pukul 23:59 WIB pada bulan berikutnya, sistem otomatis
    | mengunci form pelaporan unit (status is_late = true) kecuali terdapat
    | dispensasi keterlambatan yang disetujui oleh Sekmat/Admin Kecamatan.
    |
    */
    'cutoff_day' => (int) env('SPKO_CUTOFF_DAY', 10),

    /*
    |--------------------------------------------------------------------------
    | Identitas Instansi & Pejabat Penanggung Jawab
    |--------------------------------------------------------------------------
    */
    'instansi' => [
        'pemerintah' => 'Pemerintah Kabupaten Garut',
        'kecamatan' => 'Kecamatan Malangbong',
        'alamat' => 'Jl. Raya Malangbong - Wado No. 16, Malangbong, Garut, Jawa Barat 44188',
        'email' => 'malangbong320514@gmail.com',
        'camat_nama' => 'H. Robiul Awaludin, S.Sos., A.Kp., MM',
        'camat_jabatan' => 'Plt. Camat Malangbong',
        'camat_nip' => '19700523199303 1 005',
    ],
];
