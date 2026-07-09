<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fund extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'isin',
        'description',
        'subscription_fee_rate',
        'management_fee_rate',
        'exit_fee_rate',
        'min_initial_investment',
        'min_periodic_investment',
        'risk_level',
        'target_annual_return',
    ];

    protected $casts = [
        'subscription_fee_rate' => 'float',
        'management_fee_rate' => 'float',
        'exit_fee_rate' => 'float',
        'min_initial_investment' => 'float',
        'min_periodic_investment' => 'float',
        'risk_level' => 'integer',
        'target_annual_return' => 'float',
    ];

    /**
     * Historique des valeurs liquidatives du fonds.
     */
    public function navHistories(): HasMany
    {
        return $this->hasMany(FundNavHistory::class);
    }

    /**
     * Simulations effectuées sur ce fonds.
     */
    public function simulations(): HasMany
    {
        return $this->hasMany(Simulation::class);
    }
}
