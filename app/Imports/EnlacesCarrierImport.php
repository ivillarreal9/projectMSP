<?php

namespace App\Imports;

use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Importa circuitos de enlace carrier desde el Excel de "Referencias Técnicas Carrier".
 *
 * El libro trae una hoja por país (Guatemala, El Salvador, Colombia, ...) con los datos
 * LITERALES y una hoja "Consolidado" que NO se usa: es solo una vista generada con una
 * fórmula dinámica (LET/FILTER/VSTACK) que PhpSpreadsheet no puede calcular — al leerla
 * devuelve el texto de la fórmula, no los valores.
 *
 * Por eso se procesan las hojas de país y se ignora "Consolidado". El país se deriva del
 * NOMBRE de la hoja (las hojas de país no traen columna País).
 *
 * Las cabeceras reales están en la FILA 4 (filas 1-3 son banners de título); el mapeo
 * vive en {@see EnlacesCarrierSheetImport} con headingRow() = 4.
 */
class EnlacesCarrierImport implements WithMultipleSheets
{
    /** Hojas que NO contienen datos literales y deben ignorarse. */
    private const HOJAS_IGNORADAS = ['consolidado'];

    /**
     * @param int      $batchId
     * @param string[] $sheetNames Nombres de las hojas del libro (detectados en el controlador).
     */
    public function __construct(private int $batchId, private array $sheetNames = []) {}

    public function sheets(): array
    {
        $map = [];

        foreach ($this->sheetNames as $name) {
            if (in_array(Str::lower(trim($name)), self::HOJAS_IGNORADAS, true)) {
                continue;
            }
            // país = nombre de la hoja (Guatemala, El Salvador, ...)
            $map[$name] = new EnlacesCarrierSheetImport($this->batchId, trim($name));
        }

        return $map;
    }
}
