<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Simulation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'fund_id',
        'initial_investment',
        'periodic_investment',
        'frequency',
        'duration_in_years',
        'total_invested',
        'final_gross_balance',
        'final_net_balance',
        'total_fees',
    ];

    protected $casts = [
        'initial_investment' => 'float',
        'periodic_investment' => 'float',
        'duration_in_years' => 'float',
        'total_invested' => 'float',
        'final_gross_balance' => 'float',
        'final_net_balance' => 'float',
        'total_fees' => 'float',
    ];

    /**
     * Le prospect associé à cette simulation (si qualifié).
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Le fonds simulé.
     */
    public function fund(): BelongsTo
    {
        return $this->belongsTo(Fund::class);
    }
}
