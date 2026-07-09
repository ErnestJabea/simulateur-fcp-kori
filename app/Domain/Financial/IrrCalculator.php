<?php

declare(strict_types=1);

namespace App\Domain\Financial;

/**
 * Calculateur de Taux de Rendement Interne (TRI / IRR)
 * Utilise la méthode numérique de Newton-Raphson pour résoudre l'équation de la VAN.
 */
final class IrrCalculator
{
    private const MAX_ITERATIONS = 100;
    private const PRECISION = 0.0000001;

    /**
     * Calcule le TRI périodique à partir d'une série de flux de trésorerie (cash flows).
     * Les flux sortants (investissements) doivent être négatifs.
     * Le dernier flux doit inclure la valeur finale positive du portefeuille.
     *
     * @param array $cashFlows Tableau de flux [0 => -1000, 1 => -100, ..., T => +2200]
     * @return float|null Le taux périodique (ex: mensuel si flux mensuels), ou null si échec.
     */
    public function calculatePeriodic(array $cashFlows): ?float
    {
        $n = count($cashFlows);
        if ($n < 2) {
            return null;
        }

        // Taux de départ estimé (0.1% ou 0.001)
        $r = 0.001;

        for ($i = 0; $i < self::MAX_ITERATIONS; $i++) {
            $npv = 0.0;
            $dnpv = 0.0; // Dérivée de la VAN

            foreach ($cashFlows as $t => $cf) {
                $denom = pow(1 + $r, $t);
                $npv += $cf / $denom;

                if ($t > 0) {
                    $dnpv += -$t * $cf / pow(1 + $r, $t + 1);
                }
            }

            if (abs($dnpv) < self::PRECISION) {
                break;
            }

            $rNext = $r - $npv / $dnpv;

            if (abs($rNext - $r) < self::PRECISION) {
                return $rNext;
            }

            $r = $rNext;
        }

        return $r;
    }

    /**
     * Calcule le TRI annualisé (annualized IRR) à partir du taux périodique.
     *
     * @param float $periodicRate Taux de la période (ex: mensuel)
     * @param string $frequency Fréquence des périodes ('monthly', 'quarterly', 'annually')
     * @return float Le taux annuel équivalent
     */
    public function annualizeRate(float $periodicRate, string $frequency): float
    {
        $periodsPerYear = match ($frequency) {
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1,
            default => 12,
        };

        // Formule actuarielle de passage du taux périodique au taux annuel : (1 + r)^p - 1
        return pow(1 + $periodicRate, $periodsPerYear) - 1;
    }
}
