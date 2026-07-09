<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationLabel = 'Prospects (Leads)';
    protected static ?string $modelLabel = 'Prospect';
    protected static ?string $pluralModelLabel = 'Prospects';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations de Contact')
                    ->description('Coordonnées saisies par le client lors de sa simulation.')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom Complet')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Numéro de Téléphone')
                            ->tel()
                            ->required()
                            ->maxLength(50),
                        Toggle::make('whatsapp_enabled')
                            ->label('Disponible sur WhatsApp')
                            ->default(false),
                    ])->columns(2),

                Section::make('Suivi Commercial')
                    ->description('Suivi du traitement de l\'opportunité.')
                    ->schema([
                        Select::make('status')
                            ->label('Statut du Lead')
                            ->options([
                                'new' => 'Nouveau',
                                'contacted' => 'Contacté / En discussion',
                                'signed' => 'Souscription initiée',
                                'closed' => 'Sans suite / Archivé',
                            ])
                            ->required()
                            ->default('new'),
                        Textarea::make('notes')
                            ->label('Notes internes de suivi')
                            ->columnSpanFull()
                            ->placeholder('Saisir le compte-rendu des échanges...'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom Complet')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                IconColumn::make('whatsapp_enabled')
                    ->label('WhatsApp')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'new' => 'info',
                        'contacted' => 'warning',
                        'signed' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'new' => 'Nouveau',
                        'contacted' => 'Contacté',
                        'signed' => 'Souscrit',
                        'closed' => 'Sans suite',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Date d\'intérêt')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Filtrer par Statut')
                    ->options([
                        'new' => 'Nouveau',
                        'contacted' => 'Contacté',
                        'signed' => 'Souscrit',
                        'closed' => 'Sans suite',
                    ]),
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
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
