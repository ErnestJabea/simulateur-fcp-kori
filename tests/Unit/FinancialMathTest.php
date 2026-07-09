<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\Financial\DcaSimulator;
use App\Domain\Financial\IrrCalculator;
use PHPUnit\Framework\TestCase;

class FinancialMathTest extends TestCase
{
    /**
     * Teste la simulation DCA sans intérêts ni frais.
     * Le solde final doit être égal au cumul des versements.
     */
    public function test_dca_simulation_without_fees_and_interest(): void
    {
        $simulator = new DcaSimulator();
        
        $initial = 1000.0;
        $periodic = 100.0;
        $durationYears = 1;
        $rate = 0.0;
        $frequency = 'monthly'; // 12 périodes

        $result = $simulator->simulate(
            $initial,
            $periodic,
            $durationYears,
            $rate,
            $frequency,
            0.0, // Taux de souscription
            0.0, // Taux de gestion
            0.0  // Taux de rachat
        );

        $expectedInvested = $initial + ($periodic * 12);
        
        $this->assertEquals($expectedInvested, $result['summary']['total_invested']);
        $this->assertEquals($expectedInvested, $result['summary']['final_gross_balance']);
        $this->assertEquals($expectedInvested, $result['summary']['final_net_balance']);
        $this->assertEquals(0.0, $result['summary']['total_fees']);
        $this->assertEquals(0.0, $result['summary']['net_gains']);
    }

    /**
     * Teste le calcul du TRI sur une suite simple de flux.
     * Exemple : investir 1000, recevoir 1100 après 1 période.
     * Le TRI doit être de 10% (0.1).
     */
    public function test_irr_calculation_simple_case(): void
    {
        $calculator = new IrrCalculator();
        
        $cashFlows = [
            0 => -1000.0,
            1 => 1100.0,
        ];

        $periodicRate = $calculator->calculatePeriodic($cashFlows);
        
        $this->assertNotNull($periodicRate);
        $this->assertEqualsWithDelta(0.1, $periodicRate, 0.0001);
    }

    /**
     * Teste le calcul du TRI sur une suite plus réaliste de flux DCA.
     * Si l'on investit 1000 au départ puis 100 par mois pendant 3 mois,
     * et qu'à la fin le solde vaut 1350.
     */
    public function test_irr_calculation_with_multiple_flows(): void
    {
        $calculator = new IrrCalculator();
        
        $cashFlows = [
            0 => -1000.0,
            1 => -100.0,
            2 => -100.0,
            3 => -100.0 + 1350.0, // Flux final incluant la valeur finale
        ];

        $periodicRate = $calculator->calculatePeriodic($cashFlows);
        
        $this->assertNotNull($periodicRate);
        // Le taux périodique mensuel calculé doit donner une VAN nulle
        $npv = 0.0;
        foreach ($cashFlows as $t => $cf) {
            $npv += $cf / pow(1 + $periodicRate, $t);
        }
        
        $this->assertEqualsWithDelta(0.0, $npv, 0.00001);
    }

    /**
     * Teste la cohérence entre le calcul du versement requis (inversion)
     * et la simulation forward.
     */
    public function test_required_payment_matches_simulation_target(): void
    {
        $simulator = new DcaSimulator();
        
        $targetCapital = 10000000.0; // 10 millions
        $initial = 1000000.0;       // 1 million
        $durationYears = 5.0;
        $rate = 0.07;               // 7%
        $frequency = 'monthly';
        $subFee = 0.02;             // 2%
        $mgmtFee = 0.015;           // 1.5%
        $exitFee = 0.01;            // 1%

        // Calculer le versement périodique requis brut
        $requiredPayment = $simulator->calculateRequiredPayment(
            $targetCapital,
            $initial,
            $durationYears,
            $rate,
            $frequency,
            $subFee,
            $mgmtFee,
            $exitFee
        );

        // Lancer la simulation forward avec ce montant calculé
        $simResult = $simulator->simulate(
            $initial,
            $requiredPayment,
            $durationYears,
            $rate,
            $frequency,
            $subFee,
            $mgmtFee,
            $exitFee
        );

        // Le capital final net doit être exactement égal à l'objectif de départ (à 1.0 FCFA près)
        $this->assertEqualsWithDelta($targetCapital, $simResult['summary']['final_net_balance'], 1.0);
    }

    /**
     * Teste la cohérence entre la durée calculée requise (inversion)
     * et la simulation forward.
     */
    public function test_required_duration_matches_simulation_target(): void
    {
        $simulator = new DcaSimulator();
        
        $targetCapital = 5000000.0; // 5 millions
        $initial = 500000.0;        // 500k
        $periodic = 75000.0;        // 75k par mois
        $rate = 0.065;              // 6.5%
        $frequency = 'monthly';
        $subFee = 0.015;            // 1.5%
        $mgmtFee = 0.015;           // 1.5%
        $exitFee = 0.005;           // 0.5%

        // Calculer la durée nécessaire (en années)
        $requiredDuration = $simulator->calculateRequiredDuration(
            $targetCapital,
            $initial,
            $periodic,
            $rate,
            $frequency,
            $subFee,
            $mgmtFee,
            $exitFee
        );

        // Lancer la simulation avec cette durée calculée (qui est arrondie à la période supérieure)
        $simResult = $simulator->simulate(
            $initial,
            $periodic,
            $requiredDuration,
            $rate,
            $frequency,
            $subFee,
            $mgmtFee,
            $exitFee
        );

        // Le capital final net doit être supérieur ou égal à l'objectif cible (puisqu'on a arrondi au plafond de la période)
        $this->assertTrue($simResult['summary']['final_net_balance'] >= $targetCapital);

        // Si on enlève une seule période (1/12 d'année pour le mensuel), on doit être sous l'objectif cible
        $simResultLessOnePeriod = $simulator->simulate(
            $initial,
            $periodic,
            $requiredDuration - (1 / 12),
            $rate,
            $frequency,
            $subFee,
            $mgmtFee,
            $exitFee
        );
        $this->assertTrue($simResultLessOnePeriod['summary']['final_net_balance'] < $targetCapital);
    }
}
