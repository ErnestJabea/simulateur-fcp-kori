<?php

namespace Database\Seeders;

use App\Models\Fund;
use Illuminate\Database\Seeder;

class FundSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Fund::create([
            'name' => 'Kori Horizon Cash (Monétaire)',
            'isin' => 'CM0000001012',
            'description' => 'Fonds monétaire à faible risque, idéal pour placer ses liquidités de court terme avec une disponibilité permanente et une sécurité maximale en zone CEMAC.',
            'subscription_fee_rate' => 0.0000, // 0%
            'management_fee_rate' => 0.0075,   // 0.75%
            'exit_fee_rate' => 0.0000,         // 0%
            'min_initial_investment' => 50000.00,
            'min_periodic_investment' => 10000.00,
            'risk_level' => 1,
            'target_annual_return' => 0.0450, // 4.50%
        ]);

        Fund::create([
            'name' => 'Kori Horizon Patrimoine (Diversifié)',
            'isin' => 'CM0000001020',
            'description' => 'Fonds diversifié équilibré combinant obligations étatiques de la sous-région et actions, conçu pour faire fructifier un patrimoine à moyen terme (3 à 5 ans).',
            'subscription_fee_rate' => 0.0150, // 1.50%
            'management_fee_rate' => 0.0150,   // 1.50%
            'exit_fee_rate' => 0.0050,         // 0.50%
            'min_initial_investment' => 100000.00,
            'min_periodic_investment' => 25000.00,
            'risk_level' => 3,
            'target_annual_return' => 0.0650, // 6.50%
        ]);

        Fund::create([
            'name' => 'Kori Horizon Actions (Actions/Croissance)',
            'isin' => 'CM0000001038',
            'description' => 'Fonds investi principalement en actions cotées sur la BVMAC, ciblant une croissance maximale du capital à long terme (5 ans et plus).',
            'subscription_fee_rate' => 0.0200, // 2.00%
            'management_fee_rate' => 0.0200,   // 2.00%
            'exit_fee_rate' => 0.0100,         // 1.00%
            'min_initial_investment' => 250000.00,
            'min_periodic_investment' => 50000.00,
            'risk_level' => 5,
            'target_annual_return' => 0.0850, // 8.50%
        ]);
    }
}
