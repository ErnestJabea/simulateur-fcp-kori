<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport de Simulation Financière - Kori Asset Management</title>

    <!-- Google Fonts: Inter & Plus Jakarta Sans -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'Inter', 'sans-serif'],
                    },
                    colors: {
                        kori: {
                            brown: '#4A2306',
                            gold: '#e5a900',
                            dark: '#2d1402',
                            light: '#fcfaf7',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background-color: white;
            color: #1e293b;
        }

        /* Styles spécifiques à l'impression */
        @media print {
            body {
                background-color: white;
                color: black;
                font-size: 11pt;
            }

            .no-print {
                display: none !important;
            }

            .page-break {
                page-break-before: always;
            }

            @page {
                size: A4;
                margin: 15mm;
            }

            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>

<body class="p-6 md:p-12 max-w-4xl mx-auto">

    <!-- Bouton de retour / Impression manuelle si besoin -->
    <div
        class="no-print mb-8 flex justify-between items-center bg-kori-light p-4 rounded-xl border border-kori-brown/10">
        <a href="/" class="text-sm font-semibold text-kori-brown hover:text-kori-dark transition flex items-center">
            <svg class="w-4 h-4 mr-2 text-kori-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Retour au simulateur
        </a>
        <button onclick="window.print()"
            class="px-5 py-2.5 bg-kori-brown hover:bg-kori-dark text-white font-bold text-sm rounded-lg shadow-sm transition">
            Imprimer / Enregistrer en PDF
        </button>
    </div>

    <!-- En-tête Institutionnelle avec Logo Officiel Kori -->
    <div class="flex items-center justify-between border-b-2 border-kori-brown pb-6 mb-8">
        <div class="flex flex-col items-start select-none">
            <div class="flex items-center space-x-1 font-bold text-3xl tracking-tight text-kori-brown uppercase">
                <img src="{{ asset('logo.png') }}" width="150" alt="Logo Kori">
            </div>
        </div>
        <div class="text-right text-xs text-slate-500 font-medium">
            <p>Date : {{ now()->format('d/m/Y') }}</p>
            <p>Réf : KAM-SIM-{{ str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT) }}</p>
        </div>
    </div>

    <!-- Titre du Document -->
    <div class="text-center mb-8">
        <h1 class="text-xl font-extrabold text-kori-brown tracking-tight uppercase">PROPOSITION COMMERCIALE DE PLACEMENT
        </h1>
        <p class="text-xs text-slate-500 mt-1">Calculateur Financier FCP - Projection de Performance Latente</p>
    </div>

    <!-- Informations du Client -->
    <div class="grid grid-cols-2 gap-8 mb-8 bg-kori-light p-6 rounded-xl border border-kori-brown/10">
        <div>
            <h2 class="text-[10px] font-bold text-kori-brown/60 uppercase tracking-wider mb-2">Destinataire</h2>
            <p class="font-bold text-kori-brown text-base">{{ $lead->name }}</p>
            <p class="text-sm text-slate-600 mt-0.5">{{ $lead->email }}</p>
            <p class="text-sm text-slate-600">{{ $lead->phone }}</p>
        </div>
        <div>
            <h2 class="text-[10px] font-bold text-kori-brown/60 uppercase tracking-wider mb-2">Fonds Sélectionné</h2>
            <p class="font-bold text-kori-brown text-base">{{ $fund->name }}</p>
            <p class="text-xs text-emerald-600 font-bold">Rendement Cible :
                {{ number_format($fund->target_annual_return * 100, 2) }} % annuel</p>
        </div>
    </div>

    <!-- Synthèse de la simulation -->
    @php
        $yearsInt = (int) floor($simulation->duration_in_years);
        $monthsInt = (int) round(($simulation->duration_in_years - $yearsInt) * 12);
        $durationText =
            $yearsInt > 0
                ? $yearsInt . ($yearsInt > 1 ? ' ans' : ' an') . ($monthsInt > 0 ? ' et ' . $monthsInt . ' mois' : '')
                : $monthsInt . ' mois';
    @endphp
    <div class="mb-10">
        <h2 class="text-xs font-bold text-kori-brown uppercase tracking-wider border-b border-slate-200 pb-2 mb-4">
            Synthèse du projet d'épargne</h2>

        <div class="grid grid-cols-4 gap-4 text-center">
            <div class="border border-slate-100 p-4 rounded-xl">
                <span class="text-[10px] font-bold text-slate-400 block mb-1">DURÉE</span>
                <span class="text-lg font-extrabold text-slate-800">{{ $durationText }}</span>
            </div>
            <div class="border border-slate-100 p-4 rounded-xl">
                <span class="text-[10px] font-bold text-slate-400 block mb-1">TOTAL INVESTI</span>
                <span
                    class="text-lg font-extrabold text-slate-800">{{ number_format($result['summary']['total_invested'], 0, ',', ' ') }}
                    FCFA</span>
            </div>
            <div class="border border-slate-100 p-4 rounded-xl">
                <span class="text-[10px] font-bold text-slate-400 block mb-1">PLUS-VALUES NETTES</span>
                <span
                    class="text-lg font-extrabold text-emerald-600">+{{ number_format($result['summary']['net_gains'], 0, ',', ' ') }}
                    FCFA</span>
            </div>
            <div class="bg-kori-light border border-kori-gold/30 p-4 rounded-xl shadow-inner">
                <span class="text-[10px] font-bold text-kori-brown block mb-1">CAPITAL NET ESTIMÉ</span>
                <span
                    class="text-lg font-black text-kori-gold">{{ number_format($result['summary']['final_net_balance'], 0, ',', ' ') }}
                    FCFA</span>
            </div>
        </div>
    </div>

    <!-- Tableau de l'historique de projection -->
    <div class="mb-10">
        <h2 class="text-xs font-bold text-kori-brown uppercase tracking-wider border-b border-slate-200 pb-2 mb-4">
            Tableau de projection financière</h2>

        <table class="w-full text-left border-collapse text-xs">
            <thead>
                <tr class="border-b border-slate-300 text-slate-500 font-bold">
                    <th class="py-2.5 px-2">Période</th>
                    <th class="py-2.5 px-2 text-right">Versement</th>
                    <th class="py-2.5 px-2 text-right">Frais déduits</th>
                    <th class="py-2.5 px-2 text-right">Plus-values générées</th>
                    <th class="py-2.5 px-2 text-right">Solde Final</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($result['schedule'] as $row)
                    @if (
                        $row['period'] === 0 ||
                            $row['period'] %
                                ($simulation->frequency === 'monthly' ? 12 : ($simulation->frequency === 'quarterly' ? 4 : 1)) ===
                                0 ||
                            $loop->last)
                        <tr class="border-b border-slate-100 hover:bg-slate-50/50">
                            <td class="py-2 px-2 font-semibold">
                                @if ($row['period'] === 0)
                                    Départ
                                @else
                                    Année
                                    {{ ceil($row['period'] / ($simulation->frequency === 'monthly' ? 12 : ($simulation->frequency === 'quarterly' ? 4 : 1))) }}
                                @endif
                            </td>
                            <td class="py-2 px-2 text-right">{{ number_format($row['periodic_payment'], 0, ',', ' ') }}
                                FCFA</td>
                            <td class="py-2 px-2 text-right text-red-600">
                                -{{ number_format($row['management_fees'] + ($row['periodic_payment'] - $row['net_payment']), 0, ',', ' ') }}
                                FCFA</td>
                            <td class="py-2 px-2 text-right text-emerald-600">
                                +{{ number_format($row['interest_earned'], 0, ',', ' ') }} FCFA</td>
                            <td class="py-2 px-2 text-right font-bold text-kori-brown">
                                {{ number_format($row['ending_balance'], 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Signatures & Mentions -->
    <div class="page-break pt-8">
        <div class="grid grid-cols-2 gap-16 mb-16">
            <div>
                <p class="text-xs font-bold text-kori-brown/60 uppercase mb-8">Pour Kori Asset Management</p>
                <div class="border-b border-slate-300 h-16 w-48"></div>
                <p class="text-[10px] text-slate-500 mt-2">Le Conseiller en Gestion de Patrimoine</p>
            </div>
            <div>
                <p class="text-xs font-bold text-kori-brown/60 uppercase mb-8">Pour validation du client</p>
                <div class="border-b border-slate-300 h-16 w-48"></div>
                <p class="text-[10px] text-slate-500 mt-2">Signature du client (Précédée de la mention "Lu et approuvé")
                </p>
            </div>
        </div>

        <div
            class="bg-kori-light p-6 rounded-xl border border-kori-brown/10 text-[10px] text-slate-500 leading-relaxed">
            <h3 class="font-bold text-kori-brown uppercase tracking-wider mb-2">Avertissements & Informations
                Réglementaires</h3>
            <p class="mb-2 font-light">
                Les performances cibles mentionnées dans ce document sont des objectifs de gestion et ne constituent en
                aucun cas une promesse ou une garantie de rendement. Les placements en Parts de Fonds Communs de
                Placement (FCP) sont soumis aux fluctuations des marchés financiers de la zone CEMAC (BVMAC) et peuvent
                varier à la hausse comme à la baisse. Le soussigné reconnaît avoir été informé des risques inhérents à
                l'investissement financier.
            </p>
            <p class="font-light">
                Ce document est confidentiel et est établi sous la réglementation en vigueur agréée par la Commission de
                Surveillance du Marché Financier de l'Afrique Centrale (COSUMAF).
            </p>
        </div>
    </div>

    <!-- Impression automatique -->
    <script>
        window.addEventListener('load', () => {
            setTimeout(() => {
                window.print();
            }, 800);
        });
    </script>
</body>

</html>
