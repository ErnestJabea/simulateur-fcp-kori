<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'whatsapp_enabled',
        'status',
        'notes',
    ];

    protected $casts = [
        'whatsapp_enabled' => 'boolean',
    ];

    /**
     * Simulations associées à ce prospect (lead).
     */
    public function simulations(): HasMany
    {
        return $this->hasMany(Simulation::class);
    }
}
