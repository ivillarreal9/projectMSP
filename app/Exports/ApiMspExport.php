<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ApiMspExport implements FromArray, WithHeadings, WithStyles
{
    protected array $data;
    protected array $headers;

    public function __construct(array $data)
    {
        $this->data    = $data;
        $this->headers = !empty($data) ? array_keys($data[0]) : [];
    }

    public function array(): array
    {
        return array_map(fn($row) => array_values($row), $this->data);
    }

    public function headings(): array
    {
        return $this->headers;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}