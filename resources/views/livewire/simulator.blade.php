<?php

use function Livewire\Volt\{state, rules};
use App\Models\Fund;
use App\Models\Lead;
use App\Models\Simulation;
use App\Domain\Financial\DcaSimulator;

state([
    'step' => 1,

    // Lead info (Step 2)
    'name' => '',
    'email' => '',
    'phone' => '',
    'whatsapp_enabled' => false,

    // Placement parameters (Step 3)
    'funds' => fn() => Fund::all(),
    'selectedFundId' => null,
    'simulationMode' => 'capital', // 'capital', 'payment', 'duration'
    'targetCapital' => 50000000,   // 50 Millions par défaut
    'initialInvestment' => 0,
    'periodicInvestment' => 100000,
    'frequency' => 'monthly',
    'durationInYears' => 5.0,

    // Results
    'simulationResult' => null,

    // Status
    'leadId' => null,
    'contactRequested' => false,
]);

rules([
    'name' => 'required|string|min:3|max:100',
    'email' => 'required|email|max:100',
    'phone' => 'required|string|min:8|max:30',
]);

$nextStep = function () {
    if ($this->step === 1) {
        $this->step = 2;
        return;
    }

    if ($this->step === 2) {
        $this->validate();

        // Sauvegarder ou mettre à jour le prospect (lead)
        $lead = Lead::updateOrCreate(
            ['email' => $this->email],
            [
                'name' => $this->name,
                'phone' => $this->phone,
                'whatsapp_enabled' => $this->whatsapp_enabled,
                'status' => 'new',
            ],
        );
        $this->leadId = $lead->id;

        // Sélectionner par défaut le premier fonds s'il n'est pas choisi
        if (!$this->selectedFundId && count($this->funds) > 0) {
            $this->selectedFundId = $this->funds[0]->id;
        }

        $this->step = 3;
        return;
    }

    if ($this->step === 3) {
        $fund = Fund::find($this->selectedFundId);
        if (!$fund) {
            $this->addError('selectedFundId', 'Veuillez sélectionner un fonds d\'investissement.');
            return;
        }

        // Validation par rapport aux minimums du fonds
        if ($this->initialInvestment < $fund->min_initial_investment && $this->initialInvestment > 0) {
            $this->addError('initialInvestment', 'Le montant initial minimum pour ce fonds est de ' . number_format($fund->min_initial_investment, 0, ',', ' ') . ' FCFA.');
            return;
        }

        $dcaSimulator = new DcaSimulator();

        if ($this->simulationMode === 'capital') {
            if ($this->periodicInvestment < $fund->min_periodic_investment) {
                $this->addError('periodicInvestment', 'Le versement périodique minimum pour ce fonds est de ' . number_format($fund->min_periodic_investment, 0, ',', ' ') . ' FCFA.');
                return;
            }
            $this->simulationResult = $dcaSimulator->simulate((float) $this->initialInvestment, (float) $this->periodicInvestment, (float) $this->durationInYears, (float) $fund->target_annual_return, $this->frequency, (float) $fund->subscription_fee_rate, (float) $fund->management_fee_rate, (float) $fund->exit_fee_rate);
        } elseif ($this->simulationMode === 'payment') {
            $this->periodicInvestment = $dcaSimulator->calculateRequiredPayment((float) $this->targetCapital, (float) $this->initialInvestment, (float) $this->durationInYears, (float) $fund->target_annual_return, $this->frequency, (float) $fund->subscription_fee_rate, (float) $fund->management_fee_rate, (float) $fund->exit_fee_rate);
            
            $this->simulationResult = $dcaSimulator->simulate((float) $this->initialInvestment, (float) $this->periodicInvestment, (float) $this->durationInYears, (float) $fund->target_annual_return, $this->frequency, (float) $fund->subscription_fee_rate, (float) $fund->management_fee_rate, (float) $fund->exit_fee_rate);
        } elseif ($this->simulationMode === 'duration') {
            if ($this->periodicInvestment < $fund->min_periodic_investment) {
                $this->addError('periodicInvestment', 'Le versement périodique minimum pour ce fonds est de ' . number_format($fund->min_periodic_investment, 0, ',', ' ') . ' FCFA.');
                return;
            }
            $this->durationInYears = $dcaSimulator->calculateRequiredDuration((float) $this->targetCapital, (float) $this->initialInvestment, (float) $this->periodicInvestment, (float) $fund->target_annual_return, $this->frequency, (float) $fund->subscription_fee_rate, (float) $fund->management_fee_rate, (float) $fund->exit_fee_rate);

            if ($this->durationInYears === INF || is_infinite($this->durationInYears)) {
                $this->addError('periodicInvestment', "L'objectif ne peut pas être atteint (les versements ou performances du fonds sont insuffisants pour couvrir les frais). Veuillez augmenter vos versements.");
                return;
            }

            $this->simulationResult = $dcaSimulator->simulate((float) $this->initialInvestment, (float) $this->periodicInvestment, (float) $this->durationInYears, (float) $fund->target_annual_return, $this->frequency, (float) $fund->subscription_fee_rate, (float) $fund->management_fee_rate, (float) $fund->exit_fee_rate);
        }

        // Enregistrer la simulation en base de données
        Simulation::create([
            'lead_id' => $this->leadId,
            'fund_id' => $fund->id,
            'initial_investment' => $this->initialInvestment,
            'periodic_investment' => $this->periodicInvestment,
            'frequency' => $this->frequency,
            'duration_in_years' => $this->durationInYears,
            'total_invested' => $this->simulationResult['summary']['total_invested'],
            'final_gross_balance' => $this->simulationResult['summary']['final_gross_balance'],
            'final_net_balance' => $this->simulationResult['summary']['final_net_balance'],
            'total_fees' => $this->simulationResult['summary']['total_fees'],
        ]);

        $this->step = 4;

        // Dispatche l'événement JS pour recharger le graphique
        $schedule = $this->simulationResult['schedule'];

        $totalPoints = count($schedule);
        $step = max(1, (int) ($totalPoints / 20));

        $filteredLabels = [];
        $filteredInvested = [];
        $filteredBalance = [];

        for ($i = 0; $i < $totalPoints; $i += $step) {
            $filteredLabels[] = $schedule[$i]['date_label'];
            $filteredInvested[] = $schedule[$i]['total_invested'];
            $filteredBalance[] = $schedule[$i]['ending_balance'];
        }

        if (($totalPoints - 1) % $step !== 0) {
            $filteredLabels[] = end($schedule)['date_label'];
            $filteredInvested[] = end($schedule)['total_invested'];
            $filteredBalance[] = end($schedule)['ending_balance'];
        }

        $this->dispatch('simulation-updated', [
            'labels' => $filteredLabels,
            'invested' => $filteredInvested,
            'balance' => $filteredBalance,
        ]);

        return;
    }
};

$prevStep = function () {
    if ($this->step > 1) {
        $this->step--;
    }
};

$requestContact = function (string $type) {
    if ($this->leadId) {
        $lead = Lead::find($this->leadId);
        if ($lead) {
            $lead->status = 'contacted';
            $fundName = Fund::find($this->selectedFundId)?->name ?? 'Fonds inconnu';
            $lead->notes = "Type de demande : {$type}. Intérêt pour le fonds {$fundName}. " . 'Simulation : Initiale = ' . number_format($this->initialInvestment) . ' FCFA, ' . 'Périodique = ' . number_format($this->periodicInvestment) . ' FCFA/mois, ' . "Durée = {$this->durationInYears} ans.";
            $lead->save();
        }
    }
    $this->contactRequested = true;
};

?>

<div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100 font-sans">

    <!-- Fil d'Ariane (Progress Indicators) -->
    <div class="bg-kori-light border-b border-gray-100 px-8 py-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-2 text-xs md:text-sm font-semibold">
                <span class="{{ $step >= 1 ? 'text-kori-brown' : 'text-gray-400' }}">1. Accueil</span>
                <span class="text-gray-300">/</span>
                <span class="{{ $step >= 2 ? 'text-kori-brown' : 'text-gray-400' }}">2. Vos Informations</span>
                <span class="text-gray-300">/</span>
                <span class="{{ $step >= 3 ? 'text-kori-brown' : 'text-gray-400' }}">3. Paramètres</span>
                <span class="text-gray-300">/</span>
                <span class="{{ $step >= 4 ? 'text-kori-brown' : 'text-gray-400' }}">4. Résultats</span>
            </div>


        </div>

        <!-- Barre de progression visuelle -->
        <div class="w-full bg-gray-200 h-1.5 rounded-full mt-3 overflow-hidden">
            <div class="bg-gradient-to-r from-kori-brown to-kori-gold h-1.5 rounded-full transition-all duration-500 ease-out"
                style="width: {{ (($step - 1) / 3) * 100 }}%"></div>
        </div>
    </div>

    <!-- Corps de l'étape -->
    <div class="p-8 md:p-12">

        <!-- ÉTAPE 1 : ACCUEIL -->
        @if ($step === 1)
            <div class="text-center space-y-6 max-w-2xl mx-auto py-6">
                <div class="inline-flex p-3 rounded-full bg-kori-light text-kori-brown">
                    <!-- Icon Cauri du logo -->
                    <svg class="h-16 w-auto" viewBox="0 0 100 120" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M49 5C24.5 5 19 36.8 19 60c0 23.2 8.5 55 30 55 C42 98.4 44 73 42 53c-1.8-18-0.5-29 7-48Z"
                            fill="#e5a900" />
                        <path d="M51 5c7.5 19 8.8 30 7 48c-2 20 0 45.4-7 62c21.5 0 30-31.8 30-55C81 36.8 75.5 5 51 5Z"
                            fill="#e5a900" />
                        <path
                            d="M50 8c-3 10-1.5 14-4 18c3 4 1 8-1 12c3 4 0.5 8-1.5 12c2.5 4 0.5 8-.5 12c2.5 4 0 8 1.5 12c-2 4 1.5 8-1.5 12 c1 4-1.5 8-3.5 14c5-8 3-12 5-16c-1.5-4 1-8 2.5-12c-2-4 .5-8 1.5-12c-2-4 .5-8 1.5-12c-1.5-4 1-8 2-12 c-2-4 0.5-8-1.5-12c1.5-4-1-8-2.5-12c2-4 0-8-1-12c2-4-0.5-8-1.5-12c2-4 0.5-6-1.5-10"
                            fill="#fff" />
                    </svg>
                </div>
                <h1 class="text-3xl font-extrabold text-kori-brown tracking-tight leading-tight">
                    Optimisez vos placements avec notre simulateur financier premium
                </h1>
                <p class="text-gray-600 text-lg leading-relaxed font-light">
                    Découvrez comment l'investissement régulier et la performance de nos FCP valorisent votre épargne au
                    sein de la zone CEMAC.
                </p>
                <div class="pt-6">
                    <button wire:click="nextStep"
                        class="inline-flex items-center px-8 py-4 bg-kori-brown hover:bg-kori-dark text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all transform hover:-translate-y-0.5 duration-150">
                        <span>Démarrer ma simulation</span>
                        <svg class="w-5 h-5 ml-2 text-kori-gold" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- ÉTAPE 2 : INFORMATIONS PERSONNELLES -->
        @if ($step === 2)
            <div class="space-y-6 max-w-xl mx-auto">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-kori-brown">Faisons connaissance</h2>
                    <p class="text-gray-500 text-sm mt-1">Vos données sont protégées et servent uniquement à préparer
                        votre proposition personnalisée.</p>
                </div>

                <div class="space-y-4 pt-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Nom
                            Complet</label>
                        <input type="text" wire:model.defer="name"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-kori-brown focus:border-kori-brown transition duration-150 @error('name') border-red-500 @enderror"
                            placeholder="Ex: Jean Koffi">
                        @error('name')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Adresse
                            Email</label>
                        <input type="email" wire:model.defer="email"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-kori-brown focus:border-kori-brown transition duration-150 @error('email') border-red-500 @enderror"
                            placeholder="Ex: jean.koffi@example.com">
                        @error('email')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>

                    <div x-data="phoneInput()" x-init="initPhone()" class="relative" wire:ignore>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Numéro de
                            Téléphone</label>
                        <input x-ref="phoneInputEl" type="tel"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-kori-brown focus:border-kori-brown transition duration-150 @error('phone') border-red-500 @enderror"
                            placeholder="Ex: 699 00 00 00">
                        <input type="hidden" wire:model="phone" x-ref="phoneHiddenEl">
                        @error('phone')
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                        @enderror
                        <span x-show="!isValid && touched" class="text-red-500 text-xs mt-1 block">Numéro de téléphone
                            invalide pour ce pays.</span>
                    </div>

                    <div class="flex items-center pt-2">
                        <input type="checkbox" id="whatsapp_enabled" wire:model.defer="whatsapp_enabled"
                            class="rounded border-gray-300 text-kori-brown focus:ring-kori-brown h-4 w-4">
                        <label for="whatsapp_enabled" class="ml-2 text-sm text-gray-600">Je souhaite recevoir également
                            mon rapport de simulation via WhatsApp</label>
                    </div>
                </div>

                <div class="flex justify-between pt-6 border-t border-gray-100">
                    <button wire:click="prevStep"
                        class="px-6 py-3 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition duration-150">
                        Retour
                    </button>
                    <button wire:click="nextStep"
                        class="px-6 py-3 bg-kori-brown hover:bg-kori-dark text-white font-semibold rounded-xl shadow-md transition duration-150">
                        Étape Suivante
                    </button>
                </div>
            </div>
        @endif

        <!-- ÉTAPE 3 : PARAMÈTRES DU PLACEMENT (TRI-MODE) -->
        @if ($step === 3)
            <div class="space-y-8" x-data="{
                mode: @entangle('simulationMode'),
                initial: @entangle('initialInvestment'),
                periodic: @entangle('periodicInvestment'),
                target: @entangle('targetCapital')
            }">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-kori-brown">Configurez votre projet de placement</h2>
                    <p class="text-gray-500 text-sm mt-1">Choisissez votre mode de simulation et glissez les curseurs.</p>
                </div>

                <!-- Sélecteur de Mode (Tabs) -->
                <div class="flex border-b border-gray-200">
                    <button @click="mode = 'capital'" class="flex-1 py-3 text-center border-b-2 text-xs md:text-sm font-bold transition-all"
                        :class="mode === 'capital' ? 'border-kori-brown text-kori-brown' : 'border-transparent text-gray-400 hover:text-gray-600'">
                        Estimer mon capital futur
                    </button>
                    <button @click="mode = 'payment'" class="flex-1 py-3 text-center border-b-2 text-xs md:text-sm font-bold transition-all"
                        :class="mode === 'payment' ? 'border-kori-brown text-kori-brown' : 'border-transparent text-gray-400 hover:text-gray-600'">
                        Calculer mon versement requis
                    </button>
                    <button @click="mode = 'duration'" class="flex-1 py-3 text-center border-b-2 text-xs md:text-sm font-bold transition-all"
                        :class="mode === 'duration' ? 'border-kori-brown text-kori-brown' : 'border-transparent text-gray-400 hover:text-gray-600'">
                        Déterminer le temps nécessaire
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-4">
                    <!-- Paramètres Sliders -->
                    <div class="space-y-6">
                        <!-- Sélection du Fonds -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Fonds d'investissement</label>
                            <select wire:model.live="selectedFundId"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-kori-brown focus:border-kori-brown @error('selectedFundId') border-red-500 @enderror">
                                @foreach ($funds as $fund)
                                    <option value="{{ $fund->id }}">{{ $fund->name }} (Rendement: {{ number_format($fund->target_annual_return * 100, 2) }}%)</option>
                                @endforeach
                            </select>
                            @error('selectedFundId')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- 1. Capital Cible (Mode B et C) -->
                        <div x-show="mode !== 'capital'" x-transition>
                            <div class="flex justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Capital Cible à acquérir (FCFA)</label>
                                <span class="text-sm font-extrabold text-kori-brown" x-text="Number(target).toLocaleString('fr-FR') + ' FCFA'"></span>
                            </div>
                            <input type="range" min="1000000" max="250000000" step="1000000" x-model="target"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-kori-brown">
                            @error('targetCapital')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- 2. Investissement Initial (Tous Modes) -->
                        <div>
                            <div class="flex justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Apport Initial (FCFA) - Optionnel</label>
                                <span class="text-sm font-extrabold text-kori-brown" x-text="Number(initial).toLocaleString('fr-FR') + ' FCFA'"></span>
                            </div>
                            <input type="range" min="0" max="50000000" step="100000" x-model="initial"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-kori-brown">
                            @error('initialInvestment')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- 3. Versement Périodique (Mode A et C) -->
                        <div x-show="mode !== 'payment'" x-transition>
                            <div class="flex justify-between mb-2">
                                <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">Versement Périodique souhaité (FCFA)</label>
                                <span class="text-sm font-extrabold text-kori-brown" x-text="Number(periodic).toLocaleString('fr-FR') + ' FCFA'"></span>
                            </div>
                            <input type="range" min="10000" max="5000000" step="10000" x-model="periodic"
                                class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-kori-brown">
                            @error('periodicInvestment')
                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Fréquence et Durée -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Fréquence</label>
                                <select wire:model.live="frequency"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-kori-brown focus:border-kori-brown">
                                    <option value="monthly">Mensuelle</option>
                                    <option value="quarterly">Trimestrielle</option>
                                    <option value="annually">Annuelle</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Durée (Années)</label>
                                <template x-if="mode === 'duration'">
                                    <div class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 font-semibold text-sm">
                                        Calculée automatiquement
                                    </div>
                                </template>
                                <template x-if="mode !== 'duration'">
                                    <select wire:model.live="durationInYears"
                                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-kori-brown focus:border-kori-brown">
                                        @for ($i = 1; $i <= 30; $i++)
                                            <option value="{{ $i }}">{{ $i }} {{ $i > 1 ? 'ans' : 'an' }}</option>
                                        @endfor
                                    </select>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Détails du fonds choisi -->
                    <div class="bg-kori-light rounded-2xl p-6 border border-kori-brown/10 flex flex-col justify-between">
                        @php
                            $currentFund = $funds->firstWhere('id', $selectedFundId) ?? $funds->first();
                        @endphp

                        @if ($currentFund)
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold px-2.5 py-1 rounded bg-kori-brown/10 text-kori-brown uppercase">Fonds choisi</span>
                                    <span class="text-xs font-bold px-2 py-0.5 rounded {{ $currentFund->risk_level <= 2 ? 'bg-emerald-100 text-emerald-800' : ($currentFund->risk_level <= 4 ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800') }}">
                                        Risque : {{ $currentFund->risk_level }}/7
                                    </span>
                                </div>
                                <h3 class="text-lg font-bold text-kori-brown">{{ $currentFund->name }}</h3>
                                <p class="text-gray-500 text-sm leading-relaxed font-light">{{ $currentFund->description }}</p>

                                <hr class="border-gray-200">

                                <div class="grid grid-cols-2 gap-4 text-sm pt-2">
                                    <div>
                                        <span class="text-xs text-gray-400 block">Rendement Cible Annuel</span>
                                        <span class="font-extrabold text-emerald-600 text-lg">{{ number_format($currentFund->target_annual_return * 100, 2) }} %</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Code ISIN</span>
                                        <span class="font-semibold text-gray-700">{{ $currentFund->isin }}</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Frais de souscription</span>
                                        <span class="font-semibold text-gray-700">{{ number_format($currentFund->subscription_fee_rate * 100, 2) }} %</span>
                                    </div>
                                    <div>
                                        <span class="text-xs text-gray-400 block">Frais de gestion annuels</span>
                                        <span class="font-semibold text-gray-700">{{ number_format($currentFund->management_fee_rate * 100, 2) }} %</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex justify-between pt-6 border-t border-gray-100">
                    <button wire:click="prevStep"
                        class="px-6 py-3 border border-gray-200 hover:bg-gray-50 text-gray-700 font-semibold rounded-xl transition duration-150">
                        Retour
                    </button>
                    <button wire:click="nextStep"
                        class="px-8 py-3 bg-kori-brown hover:bg-kori-dark text-white font-bold rounded-xl shadow-md hover:shadow-lg transition duration-150">
                        Calculer la simulation
                    </button>
                </div>
            </div>
        @endif

        <!-- ÉTAPE 4 : RÉSULTATS DÉTAILLÉS & GRAPHIC -->
        @if ($step === 4 && $simulationResult)
            @php
                $yearsInt = (int) floor($durationInYears);
                $monthsInt = (int) round(($durationInYears - $yearsInt) * 12);
                $durationText = $yearsInt > 0 
                    ? $yearsInt . ($yearsInt > 1 ? ' ans' : ' an') . ($monthsInt > 0 ? ' et ' . $monthsInt . ' mois' : '')
                    : $monthsInt . ' mois';
            @endphp
            <div class="space-y-8" x-data="chartContainer()">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-kori-brown">Votre projection de placement</h2>
                    <p class="text-gray-500 text-sm mt-1">Voici les résultats de votre simulation sur {{ $durationText }}.</p>
                </div>

                <!-- Bloc Synthèse Recommandation commerciale FCP -->
                <div class="bg-kori-light border border-kori-brown/10 rounded-2xl p-6 flex items-start space-x-4">
                    <div class="p-3 bg-kori-brown/10 text-kori-brown rounded-xl flex-shrink-0 mt-0.5">
                        <svg class="w-7 h-7 text-kori-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-kori-brown text-base">Plan de Placement Personnalisé</h4>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">
                            @if ($simulationMode === 'capital')
                                En versant <strong>{{ number_format($periodicInvestment, 0, ',', ' ') }} FCFA</strong> par {{ $frequency === 'monthly' ? 'mois' : ($frequency === 'quarterly' ? 'trimestre' : 'an') }} pendant <strong>{{ $durationText }}</strong>, votre capital net final estimé sera de <strong class="text-kori-brown text-base">{{ number_format($simulationResult['summary']['final_net_balance'], 0, ',', ' ') }} FCFA</strong>.
                            @elseif ($simulationMode === 'payment')
                                Pour acquérir un capital de <strong>{{ number_format($targetCapital, 0, ',', ' ') }} FCFA</strong> net sous <strong>{{ $durationText }}</strong>, vous devez effectuer un versement périodique de <strong class="text-kori-brown text-base">{{ number_format($periodicInvestment, 0, ',', ' ') }} FCFA</strong> par {{ $frequency === 'monthly' ? 'mois' : ($frequency === 'quarterly' ? 'trimestre' : 'an') }}.
                            @elseif ($simulationMode === 'duration')
                                Pour atteindre votre objectif de <strong>{{ number_format($targetCapital, 0, ',', ' ') }} FCFA</strong> net avec un versement régulier de <strong>{{ number_format($periodicInvestment, 0, ',', ' ') }} FCFA</strong>, il vous faudra épargner pendant <strong class="text-kori-brown text-base">{{ $durationText }}</strong>.
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Cartes KPI de résultats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
                    <!-- Solde Final Net -->
                    <div
                        class="bg-gradient-to-br from-kori-dark to-kori-brown text-white rounded-2xl p-6 shadow-md border-l-4 border-kori-gold transform hover:scale-[1.02] transition duration-200">
                        <span class="text-xs text-kori-gold/80 font-bold uppercase tracking-wider block mb-1">Capital Net Obtenu</span>
                        <span class="text-3xl font-black text-kori-gold">{{ number_format($simulationResult['summary']['final_net_balance'], 0, ',', ' ') }}</span>
                        <span class="text-xs text-slate-200 block mt-2 font-light">Net de frais de rachat de {{ number_format($simulationResult['summary']['final_gross_balance'] - $simulationResult['summary']['final_net_balance'], 0, ',', ' ') }} FCFA</span>
                    </div>

                    <!-- Total Investi -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm transform hover:scale-[1.02] transition duration-200">
                        <span class="text-xs text-gray-400 font-bold uppercase tracking-wider block mb-1">Total versé</span>
                        <span class="text-2xl font-extrabold text-kori-brown">{{ number_format($simulationResult['summary']['total_invested'], 0, ',', ' ') }} FCFA</span>
                        <span class="text-xs text-gray-500 block mt-2 font-light">Apport initial de {{ number_format($initialInvestment, 0, ',', ' ') }} FCFA inclus</span>
                    </div>

                    <!-- Plus-values Net -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-6 shadow-sm transform hover:scale-[1.02] transition duration-200">
                        <span class="text-xs text-gray-400 font-bold uppercase tracking-wider block mb-1">Plus-values Net cumulées</span>
                        <span class="text-2xl font-extrabold text-emerald-600">+ {{ number_format($simulationResult['summary']['net_gains'], 0, ',', ' ') }} FCFA</span>
                        <span class="text-xs text-gray-500 block mt-2 font-light">Rendement de la période : {{ number_format($simulationResult['summary']['global_performance_pct'], 1) }} %</span>
                    </div>
                </div>

                <!-- Onglets Graphique / Tableau -->
                <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden">
                    <div class="border-b border-gray-100 bg-slate-50 px-6 py-4 flex items-center justify-between">
                        <span class="font-bold text-sm text-kori-brown uppercase tracking-wider">Évolution de la croissance</span>

                        <!-- Toggle de vue -->
                        <div class="flex space-x-2 bg-gray-200 p-0.5 rounded-lg text-xs font-semibold">
                            <button @click="showTab = 'chart'" :class="showTab === 'chart' ? 'bg-white text-kori-brown shadow-xs' : 'text-gray-600'"
                                class="px-3 py-1.5 rounded-md transition">Graphique</button>
                            <button @click="showTab = 'table'" :class="showTab === 'table' ? 'bg-white text-kori-brown shadow-xs' : 'text-gray-600'"
                                class="px-3 py-1.5 rounded-md transition">Tableau</button>
                        </div>
                    </div>

                    <div class="p-6">
                        <!-- Vue Graphique -->
                        <div x-show="showTab === 'chart'" class="w-full">
                            <div id="simulation-chart" class="w-full h-80"></div>
                        </div>

                        <!-- Vue Tableau -->
                        <div x-show="showTab === 'table'" class="overflow-x-auto">
                            <table class="w-full text-left border-collapse text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 text-gray-400 font-bold">
                                        <th class="py-3 px-4">Période</th>
                                        <th class="py-3 px-4 text-right">Versement</th>
                                        <th class="py-3 px-4 text-right">Frais déduits</th>
                                        <th class="py-3 px-4 text-right">Plus-values estimées</th>
                                        <th class="py-3 px-4 text-right">Valorisation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach (array_slice($simulationResult['schedule'], 0, 15) as $row)
                                        <tr class="border-b border-gray-50 hover:bg-gray-50 text-gray-700">
                                            <td class="py-3 px-4 font-semibold">{{ $row['date_label'] }}</td>
                                            <td class="py-3 px-4 text-right">
                                                {{ number_format($row['periodic_payment'], 0, ',', ' ') }} FCFA</td>
                                            <td class="py-3 px-4 text-right text-red-500">
                                                -{{ number_format($row['management_fees'] + ($row['periodic_payment'] - $row['net_payment']), 0, ',', ' ') }}
                                                FCFA</td>
                                            <td class="py-3 px-4 text-right text-emerald-600">
                                                +{{ number_format($row['interest_earned'], 0, ',', ' ') }} FCFA</td>
                                            <td class="py-3 px-4 text-right font-extrabold text-kori-brown">
                                                {{ number_format($row['ending_balance'], 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endforeach
                                    @if (count($simulationResult['schedule']) > 15)
                                        <tr class="text-gray-400">
                                            <td colspan="5" class="py-3 text-center italic text-xs">... tableau
                                                tronqué pour l'affichage (données complètes dans le PDF) ...</td>
                                        </tr>
                                        @php $last = end($simulationResult['schedule']); @endphp
                                        <tr class="border-t-2 border-double border-gray-200 text-kori-brown font-bold bg-slate-50">
                                            <td class="py-4 px-4 font-extrabold">{{ $last['date_label'] }} (Fin)</td>
                                            <td class="py-4 px-4 text-right">
                                                {{ number_format($last['periodic_payment'], 0, ',', ' ') }} FCFA</td>
                                            <td class="py-4 px-4 text-right text-red-500">
                                                -{{ number_format($last['total_fees'], 0, ',', ' ') }} FCFA</td>
                                            <td class="py-4 px-4 text-right text-emerald-600">
                                                +{{ number_format($last['total_gains'], 0, ',', ' ') }} FCFA</td>
                                            <td class="py-4 px-4 text-right font-black text-kori-brown text-lg">
                                                {{ number_format($last['ending_balance'], 0, ',', ' ') }} FCFA</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS ET CONVERSIONS (CTA) -->
                <div class="bg-gradient-to-br from-slate-50 to-kori-light border border-kori-brown/10 rounded-2xl p-8 space-y-6">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <h3 class="text-lg font-bold text-kori-brown">Vous souhaitez concrétiser ce projet ?</h3>
                            <p class="text-sm text-gray-500 mt-1">Obtenez les conseils d'un gestionnaire de patrimoine agréé Kori Asset Management.</p>
                        </div>

                        @if (!$contactRequested)
                            <div class="flex flex-wrap gap-3">
                                <button wire:click="requestContact('call')"
                                    class="px-6 py-3 bg-kori-brown hover:bg-kori-dark text-white font-bold rounded-xl shadow-md transition duration-150 text-sm">
                                    Être rappelé par un conseiller
                                </button>
                                <button wire:click="requestContact('appointment')"
                                    class="px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition duration-150 text-sm">
                                    Prendre RDV en ligne
                                </button>
                            </div>
                        @else
                            <div class="bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 flex items-center space-x-2 text-sm font-semibold">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Votre demande a été prise en compte ! Un conseiller Kori prendra contact avec vous rapidement.</span>
                            </div>
                        @endif
                    </div>

                    <hr class="border-kori-brown/10">

                    <!-- Actions secondaires -->
                    <div class="flex flex-wrap items-center justify-between gap-4 text-sm font-medium">
                        <div class="flex items-center space-x-4">
                            <a href="/pdf-export/{{ $leadId }}" target="_blank"
                                class="inline-flex items-center text-kori-brown hover:text-kori-dark hover:underline">
                                <svg class="w-5 h-5 mr-1.5 text-kori-gold" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>Télécharger le rapport PDF</span>
                            </a>

                            <a href="https://wa.me/?text={{ urlencode('Bonjour, j\'ai effectué une simulation d\'investissement sur le fonds de Kori Asset Management. Mon capital projeté est de ' . number_format($simulationResult['summary']['final_net_balance'], 0, ',', ' ') . ' FCFA !') }}"
                                target="_blank"
                                class="inline-flex items-center text-emerald-600 hover:text-emerald-700 hover:underline">
                                <svg class="w-5 h-5 mr-1.5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.514 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.5-5.739-1.446L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.37 9.864-9.799.002-2.63-1.023-5.101-2.885-6.965C16.528 1.977 14.07 1.01 11.999 1.01 6.562 1.01 2.138 5.381 2.135 10.81c0 1.679.444 3.315 1.285 4.747l-.982 3.58 3.69-.968z" />
                                </svg>
                                <span>Partager par WhatsApp</span>
                            </a>
                        </div>

                        <button wire:click="prevStep"
                            class="text-slate-400 hover:text-slate-600 font-semibold underline">
                            Recommencer la simulation
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>

    <!-- Alpine.js & ApexCharts Integration -->
    <style>
        .iti {
            width: 100% !important;
        }

        .iti__country-list {
            color: #1e293b !important;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('phoneInput', () => ({
                iti: null,
                isValid: true,
                touched: false,
                initPhone() {
                    const input = this.$refs.phoneInputEl;
                    const hiddenInput = this.$refs.phoneHiddenEl;

                    this.iti = window.intlTelInput(input, {
                        initialCountry: "auto",
                        geoIpLookup: function(callback) {
                            fetch("https://ipapi.co/json")
                                .then(res => res.json())
                                .then(data => callback(data.country_code))
                                .catch(() => callback("CM"));
                        },
                        preferredCountries: ["cm", "ga", "cg", "td", "cf", "gq", "ci", "sn",
                            "fr"
                        ],
                        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@19.2.16/build/js/utils.js"
                    });

                    input.addEventListener('change', () => this.validateAndSync());
                    input.addEventListener('keyup', () => this.validateAndSync());
                    input.addEventListener('countrychange', () => this.validateAndSync());

                    if (hiddenInput.value) {
                        this.iti.setNumber(hiddenInput.value);
                    }
                },
                validateAndSync() {
                    this.touched = true;
                    const input = this.$refs.phoneInputEl;
                    const hiddenInput = this.$refs.phoneHiddenEl;

                    if (input.value.trim()) {
                        this.isValid = this.iti.isValidNumber();
                        if (this.isValid) {
                            hiddenInput.value = this.iti.getNumber();
                            hiddenInput.dispatchEvent(new Event('input'));
                        } else {
                            hiddenInput.value = '';
                            hiddenInput.dispatchEvent(new Event('input'));
                        }
                    } else {
                        this.isValid = true;
                        hiddenInput.value = '';
                        hiddenInput.dispatchEvent(new Event('input'));
                    }
                }
            }));

            Alpine.data('chartContainer', () => ({
                showTab: 'chart',
                chart: null,
                init() {
                    this.initChart();

                    window.addEventListener('simulation-updated', (event) => {
                        const payload = event.detail[0] || event.detail;
                        if (payload) {
                            this.updateChartData(payload);
                        }
                    });
                },
                initChart() {
                    const options = {
                        chart: {
                            type: 'area',
                            height: 320,
                            fontFamily: 'Plus Jakarta Sans, sans-serif',
                            toolbar: {
                                show: false
                            }
                        },
                        series: [{
                                name: 'Total Versé (FCFA)',
                                data: []
                            },
                            {
                                name: 'Capital Estimé (FCFA)',
                                data: []
                            }
                        ],
                        xaxis: {
                            categories: [],
                            labels: {
                                style: {
                                    colors: '#94a3b8'
                                }
                            }
                        },
                        yaxis: {
                            labels: {
                                formatter: function(value) {
                                    if (value >= 1000000) {
                                        return (value / 1000000).toLocaleString('fr-FR', {maximumFractionDigits: 1}) + " M";
                                    }
                                    if (value >= 1000) {
                                        return (value / 1000).toLocaleString('fr-FR', {maximumFractionDigits: 0}) + " k";
                                    }
                                    return value.toLocaleString('fr-FR');
                                },
                                style: {
                                    colors: '#94a3b8'
                                }
                            }
                        },
                        colors: ['#4A2306', '#e5a900'], // Kori Brown & Kori Gold
                        dataLabels: {
                            enabled: false
                        },
                        stroke: {
                            curve: 'smooth',
                            width: [2, 3]
                        },
                        fill: {
                            type: 'gradient',
                            gradient: {
                                shadeIntensity: 1,
                                opacityFrom: 0.35,
                                opacityTo: 0.05,
                                stops: [0, 90, 100]
                            }
                        },
                        grid: {
                            borderColor: '#f1f5f9'
                        },
                        tooltip: {
                            y: {
                                formatter: function(val) {
                                    return val.toLocaleString() + " FCFA";
                                }
                            }
                        }
                    };

                    this.chart = new ApexCharts(document.querySelector("#simulation-chart"), options);
                    this.chart.render();
                },
                updateChartData(data) {
                    if (this.chart) {
                        this.chart.updateOptions({
                            xaxis: {
                                categories: data.labels
                            }
                        });
                        this.chart.updateSeries([
                            {
                                name: 'Total Versé (FCFA)',
                                data: data.invested
                            },
                            {
                                name: 'Capital Estimé (FCFA)',
                                data: data.balance
                            }
                        ]);
                    }
                }
            }));
        });
    </script>
</div>
