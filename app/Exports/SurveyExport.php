<?php

namespace App\Exports;

use App\Models\Survey;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Survey::select(
            'fecha',
            'numero_whatsapp',
            'nombre',
            'satisfaccion',
            'recomendacion'
        )->latest()->get();
    }

    public function headings(): array
    {
        return ['Fecha', 'Número WhatsApp', 'Nombre', 'Satisfacción', 'Recomendación'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}