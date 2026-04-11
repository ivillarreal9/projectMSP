<?php

namespace App\Exports;

use App\Models\Survey;
use App\Models\SurveyType;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SurveyExport implements FromCollection, WithHeadings, WithStyles
{
    public function __construct(
        protected ?SurveyType $type = null
    ) {}

    public function collection()
    {
        $query = Survey::query();

        if ($this->type) {
            $query->where('survey_type_id', $this->type->id);
        }

        return $query->latest()->get()->map(function ($s) {
            $row = [
                'fecha'           => $s->fecha,
                'numero_whatsapp' => $s->numero_whatsapp,
                'nombre'          => $s->nombre,
            ];

            // Campos dinámicos según el tipo
            if ($this->type) {
                foreach ($this->type->campos as $campo) {
                    $row[$campo] = $s->$campo ?? '';
                }
            } else {
                $row['satisfaccion']  = $s->satisfaccion ?? '';
                $row['recomendacion'] = $s->recomendacion ?? '';
            }

            return $row;
        });
    }

    public function headings(): array
    {
        $base = ['Fecha', 'Número WhatsApp', 'Nombre'];

        if ($this->type) {
            return array_merge($base, array_map('ucfirst', $this->type->campos));
        }

        return array_merge($base, ['Satisfacción', 'Recomendación']);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}