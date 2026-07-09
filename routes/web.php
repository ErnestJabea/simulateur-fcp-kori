<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Models\Lead;
use App\Domain\Financial\DcaSimulator;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route principale du simulateur (Livewire Volt Component)
Route::view('/', 'index');

// Route d'export PDF de la simulation pour un prospect qualifié (Lead)
Route::get('/pdf-export/{lead}', function (Lead $lead) {
    // Récupérer la dernière simulation effectuée par ce lead
    $simulation = $lead->simulations()->latest()->firstOrFail();
    $fund = $simulation->fund;

    // Recalculer la simulation pour avoir tout le tableau d'amortissement détaillé
    $dcaSimulator = new DcaSimulator();
    $result = $dcaSimulator->simulate(
        (float) $simulation->initial_investment,
        (float) $simulation->periodic_investment,
        (int) $simulation->duration_in_years,
        (float) $fund->target_annual_return,
        $simulation->frequency,
        (float) $fund->subscription_fee_rate,
        (float) $fund->management_fee_rate,
        (float) $fund->exit_fee_rate
    );

    return view('pdf-report', [
        'lead' => $lead,
        'simulation' => $simulation,
        'fund' => $fund,
        'result' => $result
    ]);
})->name('pdf-export');
