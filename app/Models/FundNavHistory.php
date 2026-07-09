<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FundNavHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'fund_id',
        'date',
        'nav',
    ];

    protected $casts = [
        'date' => 'date',
        'nav' => 'float',
    ];

    /**
     * Le fonds associé à cette valeur liquidative.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
