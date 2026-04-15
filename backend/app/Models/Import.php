<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 */
class Import extends Model
{
    protected $fillable = [
        'file_name',
        'total_records',
        'successful_records',
        'failed_records',
        'status',
    ];

    /**
     * @return HasMany<ImportLog, $this>
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }
}
