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

class RekapitulasiUnitSheet implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
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
        return 'Rekap Status Unit';
    }

    public function headings(): array
    {
        return [
            'No',
            'Unit Organisasi',
            'Pejabat Pengisi',
            'NIP',
            'Status Laporan',
            'Kepatuhan Waktu (Cut-Off)',
            'Waktu Diajukan',
            'Diverifikasi Oleh',
            'Waktu Verifikasi',
        ];
    }

    public function collection()
    {
        $rows = collect();
        $no = 1;

        $details = $this->detail
            ? collect([$this->detail])
            : ($this->laporan ? $this->laporan->details()->with(['unitOrganisasi', 'user', 'verifiedBy'])->get() : collect());

        foreach ($details as $detail) {
            $rows->push([
                'no' => $no++,
                'unit' => $detail->unitOrganisasi?->nama_unit ?? '-',
                'pengisi' => $detail->user?->name ?? '-',
                'nip' => $detail->user?->nip ?? '-',
                'status' => strtoupper($detail->status),
                'kepatuhan' => $detail->is_late ? 'Terlambat (> Tgl 10)' : ($detail->is_dispensasi ? 'Dispensasi Sekmat' : 'Tepat Waktu'),
                'submitted_at' => $detail->submitted_at ? $detail->submitted_at->format('d/m/Y H:i') : '-',
                'verifier' => $detail->verifiedBy?->name ?? ($detail->verified_at ? 'Sekretaris Camat' : '-'),
                'verified_at' => $detail->verified_at ? $detail->verified_at->format('d/m/Y H:i') : '-',
            ]);
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
                'startColor' => ['rgb' => '4F46E5'], // Indigo
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
        }

        return [];
    }
}
