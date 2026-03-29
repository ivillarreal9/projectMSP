<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ApiMspExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;
    protected array $headers;

    public function __construct(array $data)
    {
        $this->data    = $data;
        $this->headers = !empty($data) ? array_keys($data[0]) : [];
    }

    public function title(): string
    {
        return 'Tickets MSP';
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function array(): array
    {
        return array_map(fn($row) => array_values($row), $this->data);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF7C3AED'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                'font' => ['size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFE5E7EB'],
                    ],
                ],
            ]);

            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF9FAFB'],
                        ],
                    ]);
                }
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(22);

        return [];
    }
}