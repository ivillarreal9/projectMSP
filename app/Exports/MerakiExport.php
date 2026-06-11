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

/**
 * Export genérico para informes del módulo Meraki.
 *
 * Parametriza encabezados, filas y título de la hoja para reutilizarse en
 * los distintos informes (dispositivos, licencias, alertas) manteniendo un
 * estilo visual consistente: cabecera teal, filas alternadas y autoajuste de
 * columnas. Se exporta como .xlsx nativo mediante maatwebsite/excel.
 *
 * @see \App\Http\Controllers\Admin\MerakiController::exportDevices()
 * @see \App\Http\Controllers\Admin\MerakiController::exportLicenses()
 * @see \App\Http\Controllers\Admin\MerakiController::exportAlerts()
 */
class MerakiExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    /**
     * @param  array   $headers  Encabezados de columna
     * @param  array   $rows     Filas de datos (array de arrays indexados)
     * @param  string  $title    Título de la hoja de cálculo
     */
    public function __construct(
        protected array $headers,
        protected array $rows,
        protected string $title = 'Meraki',
    ) {}

    public function title(): string
    {
        // Excel limita los nombres de hoja a 31 caracteres
        return mb_substr($this->title, 0, 31);
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Cabecera: fondo teal, texto blanco, centrado
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF0D9488'], // teal-600
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        if ($lastRow > 1) {
            $sheet->getStyle("A2:{$lastCol}{$lastRow}")->applyFromArray([
                'font'      => ['size' => 9],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => [
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FFE5E7EB'],
                    ],
                ],
            ]);

            // Filas alternadas (zebra)
            for ($row = 2; $row <= $lastRow; $row++) {
                if ($row % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
                        'fill' => [
                            'fillType'   => Fill::FILL_SOLID,
                            'startColor' => ['argb' => 'FFF0FDFA'], // teal-50
                        ],
                    ]);
                }
            }
        }

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2'); // mantener cabecera visible al hacer scroll

        return [];
    }
}
