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
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('isin')->nullable()->unique();
            $table->text('description')->nullable();
            
            // Frais en taux décimal (ex: 0.0200 = 2.00%)
            $table->decimal('subscription_fee_rate', 5, 4)->default(0.0000);
            $table->decimal('management_fee_rate', 5, 4)->default(0.0000);
            $table->decimal('exit_fee_rate', 5, 4)->default(0.0000);
            
            // Seuils d'investissement (ex: 15 chiffres dont 2 décimales pour de grands montants FCFA)
            $table->decimal('min_initial_investment', 15, 2)->default(0.00);
            $table->decimal('min_periodic_investment', 15, 2)->default(0.00);
            
            // Indicateurs financiers
            $table->integer('risk_level')->default(1); // Échelle SRRI 1-7
            $table->decimal('target_annual_return', 5, 4)->default(0.0000); // Taux cible (ex: 0.0600 = 6%)
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funds');
    }
};
