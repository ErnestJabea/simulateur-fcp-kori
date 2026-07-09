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
        Schema::create('fund_nav_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fund_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            
            // Valeur Liquidative (ex: 15 chiffres avec 4 décimales pour plus de précision VL)
            $table->decimal('nav', 15, 4);
            
            $table->timestamps();

            // Indexation pour les performances
            $table->unique(['fund_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_nav_histories');
    }
};
