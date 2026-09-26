<?php

namespace App\Exports\Sheets;

use App\Models\Laporan;
use App\Models\LaporanDetail;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IndikatorKinerjaSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    protected ?Laporan $laporan;

    protected ?LaporanDetail $detail;

    public function __construct(?Laporan $laporan = null, ?LaporanDetail $detail = null)
    {
        $this->laporan = $laporan;
        $this->detail = $detail;
    }

    public function title(): string
    {
        return 'Indikator & Anggaran';
    }

    public function headings(): array
    {
        return [
            'No',
            'Unit Organisasi',
            'Sasaran Strategis',
            'Rencana Aksi',
            'Target Fisik',
            'Realisasi Fisik',
            'Satuan',
            'Capaian Fisik (%)',
            'Predikat Efektivitas',
            'Pagu Anggaran (Rp)',
            'Realisasi Belanja (Rp)',
            'Serapan Belanja (%)',
            'Predikat Efisiensi',
        ];
    }

    public function collection()
    {
        $rows = collect();
        $no = 1;

        $details = $this->detail
            ? collect([$this->detail])
            : ($this->laporan ? $this->laporan->details()->with(['unitOrganisasi', 'indikators.rencanaAksi.sasaranStrategis'])->get() : collect());

        foreach ($details as $detail) {
            $unitName = $detail->unitOrganisasi?->nama_unit ?? '-';

            foreach ($detail->indikators as $ind) {
                $ra = $ind->rencanaAksi;
                $ss = $ra?->sasaranStrategis;

                $rows->push([
                    'no' => $no++,
                    'unit' => $unitName,
                    'sasaran' => $ss?->uraian_sasaran ?? '-',
                    'rencana_aksi' => $ra?->uraian_rencana_aksi ?? '-',
                    'target_kinerja' => (float) $ind->target_kinerja,
                    'realisasi_kinerja' => (float) $ind->realisasi_kinerja,
                    'satuan' => $ra?->satuan_target ?? 'Kegiatan',
                    'persentase_kinerja' => (float) $ind->persentase_kinerja,
                    'predikat_efektivitas' => str_replace('_', ' ', ucwords($ind->predikat_efektivitas, '_')),
                    'anggaran_pagu' => (float) $ind->anggaran_pagu,
                    'realisasi_anggaran' => (float) $ind->realisasi_anggaran,
                    'persentase_anggaran' => (float) $ind->persentase_anggaran,
                    'predikat_efisiensi' => str_replace('_', ' ', ucwords($ind->predikat_efisiensi, '_')),
                ]);
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669'], // Emerald primary
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($highestRow > 1) {
            $sheet->getStyle("A1:{$highestCol}{$highestRow}")->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D1D5DB'],
                    ],
                ],
            ]);

            // Number formats
            $sheet->getStyle("J2:K{$highestRow}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("H2:H{$highestRow}")->getNumberFormat()->setFormatCode('0.00"%"');
            $sheet->getStyle("L2:L{$highestRow}")->getNumberFormat()->setFormatCode('0.00"%"');
        }

        return [];
    }
}
