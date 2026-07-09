<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FundResource\Pages;
use App\Models\Fund;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FundResource extends Resource
{
    protected static ?string $model = Fund::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    
    protected static ?string $navigationLabel = 'Fonds d\'Investissement';
    protected static ?string $modelLabel = 'Fonds';
    protected static ?string $pluralModelLabel = 'Fonds';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations Générales')
                    ->description('Caractéristiques de base du fonds commun de placement.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du Fonds')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('isin')
                            ->label('Code ISIN')
                            ->unique(ignoreRecord: true)
                            ->maxLength(12),
                        Select::make('risk_level')
                            ->label('Niveau de Risque (SRRI)')
                            ->options([
                                1 => '1 - Très Faible (Monétaire)',
                                2 => '2 - Faible',
                                3 => '3 - Modéré',
                                4 => '4 - Moyen',
                                5 => '5 - Élevé',
                                6 => '6 - Très Élevé',
                                7 => '7 - Spéculatif',
                            ])
                            ->required()
                            ->default(1),
                        Textarea::make('description')
                            ->label('Description du fonds')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Tarification et Rendements Cibles')
                    ->description('Paramètres financiers utilisés pour le moteur de simulation.')
                    ->schema([
                        TextInput::make('target_annual_return')
                            ->label('Taux cible annuel (%)')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->hydrateStateUsing(fn ($state) => $state !== null ? (float) $state * 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (float) $state / 100 : null),
                        TextInput::make('subscription_fee_rate')
                            ->label('Frais de souscription (%)')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->hydrateStateUsing(fn ($state) => $state !== null ? (float) $state * 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (float) $state / 100 : null),
                        TextInput::make('management_fee_rate')
                            ->label('Frais de gestion annuels (%)')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->hydrateStateUsing(fn ($state) => $state !== null ? (float) $state * 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (float) $state / 100 : null),
                        TextInput::make('exit_fee_rate')
                            ->label('Frais de rachat / sortie (%)')
                            ->numeric()
                            ->required()
                            ->suffix('%')
                            ->hydrateStateUsing(fn ($state) => $state !== null ? (float) $state * 100 : null)
                            ->dehydrateStateUsing(fn ($state) => $state !== null ? (float) $state / 100 : null),
                    ])->columns(4),

                Section::make('Seuils d\'Investissement')
                    ->description('Montants minimaux requis pour souscrire au fonds.')
                    ->schema([
                        TextInput::make('min_initial_investment')
                            ->label('Minimum investissement initial')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA')
                            ->default(0.00),
                        TextInput::make('min_periodic_investment')
                            ->label('Minimum versement périodique')
                            ->numeric()
                            ->required()
                            ->suffix('FCFA')
                            ->default(0.00),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom du Fonds')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('isin')
                    ->label('Code ISIN')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('target_annual_return')
                    ->label('Rendement Cible')
                    ->formatStateUsing(fn ($state) => number_format((float)$state * 100, 2) . ' %')
                    ->sortable(),
                TextColumn::make('risk_level')
                    ->label('Risque')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 2 => 'success',
                        $state <= 4 => 'warning',
                        default => 'danger',
                    })
                    ->formatStateUsing(fn ($state) => "Niv. {$state}")
                    ->sortable(),
                TextColumn::make('min_initial_investment')
                    ->label('Min Initial')
                    ->money('XAF')
                    ->sortable(),
                TextColumn::make('min_periodic_investment')
                    ->label('Min Périodique')
                    ->money('XAF')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFunds::route('/'),
            'create' => Pages\CreateFund::route('/create'),
            'edit' => Pages\EditFund::route('/{record}/edit'),
        ];
    }
}
