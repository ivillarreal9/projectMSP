<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de importación de enlaces carrier desde Excel/SharePoint.
 *
 * @property int         $id
 * @property string      $filename
 * @property string|null $sharepoint_item_id
 * @property string|null $referencia_tecnica   Formato OVN-TEC-AAAA-NNNN
 * @property string|null $source_modified_at   lastModifiedDateTime del Excel origen en SharePoint
 * @property int         $total_registros
 */
class EnlaceBatch extends Model
{
    protected $table = 'enlaces_batches';

    protected $fillable = [
        'filename',
        'sharepoint_item_id',
        'referencia_tecnica',
        'source_modified_at',
        'total_registros',
    ];

    public function enlaces(): HasMany
    {
        return $this->hasMany(EnlaceCarrier::class, 'batch_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $batch) {
            if (empty($batch->referencia_tecnica)) {
                $year = now()->year;
                $seq  = str_pad(static::whereYear('created_at', $year)->count() + 1, 4, '0', STR_PAD_LEFT);
                $batch->referencia_tecnica = "OVN-TEC-{$year}-{$seq}";
            }
        });
    }
}
