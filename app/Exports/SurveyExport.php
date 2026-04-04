<?php

namespace App\Exports;

use App\Models\Survey;
use App\Models\SurveyType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class SurveyExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(private SurveyType $type) {}

    public function title(): string
    {
        return $this->type->nombre;
    }

    public function headings(): array
    {
        return array_merge(
            ['ID', 'Fecha', 'WhatsApp', 'Nombre'],
            $this->type->campos,
            ['Recibido']
        );
    }

    public function collection()
    {
        return Survey::where('survey_type_id', $this->type->id)
            ->latest()
            ->get()
            ->map(function ($survey) {
                $row = [
                    $survey->id,
                    $survey->fecha,
                    $survey->numero_whatsapp,
                    $survey->nombre,
                ];

                foreach ($this->type->campos as $campo) {
                    $row[] = $survey->data[$campo] ?? '';
                }

                $row[] = $survey->created_at->format('Y-m-d H:i:s');

                return $row;
            });
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = $sheet->getHighestColumn();

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

        $sheet->getRowDimension(1)->setRowHeight(22);

        return [];
    }
}