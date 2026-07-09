<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            
            // Entrées de la simulation
            $table->decimal('initial_investment', 15, 2);
            $table->decimal('periodic_investment', 15, 2);
            $table->string('frequency'); // monthly, quarterly, annually
            $table->decimal('duration_in_years', 8, 4);
            
            // Résultats de la simulation
            $table->decimal('total_invested', 15, 2);
            $table->decimal('final_gross_balance', 15, 2);
            $table->decimal('final_net_balance', 15, 2);
            $table->decimal('total_fees', 15, 2);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulations');
    }
};
