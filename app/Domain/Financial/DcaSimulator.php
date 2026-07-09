<?php

declare(strict_types=1);

namespace App\Domain\Financial;

/**
 * Moteur de simulation de DCA et d'objectifs financiers.
 * Conçu selon le principe de Clean Architecture (PHP Pur, aucune dépendance externe).
 */
final class DcaSimulator
{
    /**
     * Exécute la simulation DCA et retourne les détails période par période.
     *
     * @param float $initialInvestment Capital de départ brut (€/FCFA)
     * @param float $periodicInvestment Versement périodique brut (€/FCFA)
     * @param float $durationInYears Durée totale en années (peut être décimal)
     * @param float $annualRate Taux de rendement annuel (ex: 0.06 pour 6%)
     * @param string $frequency Fréquence des versements ('monthly', 'quarterly', 'annually')
     * @param float $subscriptionFeeRate Taux des frais de souscription (ex: 0.02 pour 2%)
     * @param float $managementFeeRate Taux annuel des frais de gestion (ex: 0.015 pour 1.5%)
     * @param float $exitFeeRate Taux des frais de rachat/sortie (ex: 0.01 pour 1%)
     * @return array
     */
    public function simulate(
        float $initialInvestment,
        float $periodicInvestment,
        float $durationInYears,
        float $annualRate,
        string $frequency = 'monthly',
        float $subscriptionFeeRate = 0.0,
        float $managementFeeRate = 0.0,
        float $exitFeeRate = 0.0
    ): array {
        // Déterminer le nombre de périodes par an
        $periodsPerYear = match ($frequency) {
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1,
            default => 12,
        };

        $totalPeriods = (int) round($durationInYears * $periodsPerYear);
        
        // Taux périodique (proportionnel)
        $periodicRate = $annualRate / $periodsPerYear;
        
        // Taux de gestion périodique
        $periodicManagementFeeRate = $managementFeeRate / $periodsPerYear;

        // Préparation des variables
        $capital = $initialInvestment * (1 - $subscriptionFeeRate);
        $totalInvested = $initialInvestment;
        $totalFees = $initialInvestment * $subscriptionFeeRate;
        
        $schedule = [];
        
        // Période 0 (Situation initiale après frais de souscription de départ)
        $schedule[] = [
            'period' => 0,
            'date_label' => 'Départ',
            'periodic_payment' => $initialInvestment,
            'net_payment' => $initialInvestment * (1 - $subscriptionFeeRate),
            'interest_earned' => 0.0,
            'management_fees' => 0.0,
            'total_fees' => $totalFees,
            'total_invested' => $totalInvested,
            'ending_balance' => round($capital, 2),
            'total_gains' => 0.0,
        ];

        for ($t = 1; $t <= $totalPeriods; $t++) {
            // Versement périodique brut
            $paymentRaw = $periodicInvestment;
            // Frais de souscription sur ce versement
            $subFee = $paymentRaw * $subscriptionFeeRate;
            $paymentNet = $paymentRaw - $subFee;
            
            // Cumul des investissements et frais
            $totalInvested += $paymentRaw;
            $totalFees += $subFee;

            // Le versement est ajouté en début de période (Due)
            $capitalBeforeInterest = $capital + $paymentNet;
            
            // Intérêts gagnés sur la période
            $interestEarned = $capitalBeforeInterest * $periodicRate;
            $capitalWithInterest = $capitalBeforeInterest + $interestEarned;
            
            // Frais de gestion sur la période (calculés sur l'actif net avec intérêts)
            $managementFee = $capitalWithInterest * $periodicManagementFeeRate;
            $totalFees += $managementFee;
            
            // Solde de fin de période
            $capital = $capitalWithInterest - $managementFee;

            // Calcul du gain latent actuel
            $totalGains = $capital - $totalInvested;

            $schedule[] = [
                'period' => $t,
                'date_label' => $this->getPeriodLabel($t, $frequency),
                'periodic_payment' => $paymentRaw,
                'net_payment' => $paymentNet,
                'interest_earned' => round($interestEarned, 2),
                'management_fees' => round($managementFee, 2),
                'total_fees' => round($totalFees, 2),
                'total_invested' => round($totalInvested, 2),
                'ending_balance' => round($capital, 2),
                'total_gains' => round($totalGains, 2),
            ];
        }

        // Application des frais de sortie (rachat) à la fin
        $finalGrossBalance = $capital;
        $exitFee = $finalGrossBalance * $exitFeeRate;
        $finalNetBalance = $finalGrossBalance - $exitFee;
        $totalFees += $exitFee;
        
        return [
            'summary' => [
                'total_invested' => round($totalInvested, 2),
                'final_gross_balance' => round($finalGrossBalance, 2),
                'final_net_balance' => round($finalNetBalance, 2),
                'total_fees' => round($totalFees, 2),
                'net_gains' => round($finalNetBalance - $totalInvested, 2),
                'global_performance_pct' => $totalInvested > 0 
                    ? round((($finalNetBalance - $totalInvested) / $totalInvested) * 100, 2) 
                    : 0.0,
            ],
            'schedule' => $schedule,
        ];
    }

    /**
     * Calcule le versement périodique brut requis pour atteindre un objectif de capital.
     */
    public function calculateRequiredPayment(
        float $targetCapital,
        float $initialInvestment,
        float $durationInYears,
        float $annualRate,
        string $frequency = 'monthly',
        float $subscriptionFeeRate = 0.0,
        float $managementFeeRate = 0.0,
        float $exitFeeRate = 0.0
    ): float {
        $periodsPerYear = match ($frequency) {
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1,
            default => 12,
        };

        $n = (int) round($durationInYears * $periodsPerYear);
        if ($n <= 0) {
            return 0.0;
        }

        $periodicRate = $annualRate / $periodsPerYear;
        $periodicManagementFeeRate = $managementFeeRate / $periodsPerYear;

        // w = Multiplicateur de croissance périodique net
        $w = (1 + $periodicRate) * (1 - $periodicManagementFeeRate);

        $targetGrossBalance = $targetCapital / (1 - $exitFeeRate);
        $initialInvestmentNet = $initialInvestment * (1 - $subscriptionFeeRate);

        if (abs($w - 1.0) < 1e-9) {
            $requiredNetPayment = ($targetGrossBalance - $initialInvestmentNet) / $n;
        } else {
            $requiredNetPayment = ($targetGrossBalance - $initialInvestmentNet * pow($w, $n)) 
                * ($w - 1) / ($w * (pow($w, $n) - 1));
        }

        $requiredGrossPayment = $requiredNetPayment / (1 - $subscriptionFeeRate);

        return max(0.0, $requiredGrossPayment);
    }

    /**
     * Calcule la durée nécessaire (en années) pour atteindre un objectif de capital.
     */
    public function calculateRequiredDuration(
        float $targetCapital,
        float $initialInvestment,
        float $periodicInvestment,
        float $annualRate,
        string $frequency = 'monthly',
        float $subscriptionFeeRate = 0.0,
        float $managementFeeRate = 0.0,
        float $exitFeeRate = 0.0
    ): float {
        $periodsPerYear = match ($frequency) {
            'monthly' => 12,
            'quarterly' => 4,
            'annually' => 1,
            default => 12,
        };

        $periodicRate = $annualRate / $periodsPerYear;
        $periodicManagementFeeRate = $managementFeeRate / $periodsPerYear;

        // w = Multiplicateur de croissance périodique net
        $w = (1 + $periodicRate) * (1 - $periodicManagementFeeRate);

        $targetGrossBalance = $targetCapital / (1 - $exitFeeRate);
        $initialInvestmentNet = $initialInvestment * (1 - $subscriptionFeeRate);
        $periodicInvestmentNet = $periodicInvestment * (1 - $subscriptionFeeRate);

        // Si l'investissement initial net dépasse déjà la cible
        if ($initialInvestmentNet >= $targetGrossBalance) {
            return 0.0;
        }

        // Si aucun versement n'est effectué et pas de croissance
        if ($periodicInvestmentNet <= 0.0 && $w <= 1.0) {
            return INF;
        }

        if (abs($w - 1.0) < 1e-9) {
            if ($periodicInvestmentNet <= 0.0) {
                return INF;
            }
            $n = ($targetGrossBalance - $initialInvestmentNet) / $periodicInvestmentNet;
        } else {
            $C = $periodicInvestmentNet * $w / ($w - 1);
            $A = $initialInvestmentNet + $C;

            if ($A <= 0.0) {
                return INF;
            }

            $ratio = ($targetGrossBalance + $C) / $A;
            if ($ratio <= 0.0) {
                return INF; // Impossible à atteindre
            }

            $n = log($ratio) / log($w);
        }

        // Arrondir au nombre entier de périodes supérieur (plafond) pour garantir l'atteinte de l'objectif
        $n = ceil($n);

        return max(0.0, $n / $periodsPerYear);
    }

    /**
     * Génère un libellé pour chaque période (ex: "Mois 1", "Trimestre 3")
     */
    private function getPeriodLabel(int $period, string $frequency): string
    {
        return match ($frequency) {
            'monthly' => "Mois {$period}",
            'quarterly' => "Trimestre {$period}",
            'annually' => "Année {$period}",
            default => "Période {$period}",
        };
    }
}
