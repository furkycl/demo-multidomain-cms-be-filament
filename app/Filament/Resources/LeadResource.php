<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('source_domain')->disabled(),
            Forms\Components\TextInput::make('locale')->disabled(),
            Forms\Components\TextInput::make('brand')->disabled(),
            Forms\Components\TextInput::make('crm_target')->disabled(),
            Forms\Components\TextInput::make('crm_status')->disabled(),
            Forms\Components\KeyValue::make('payload')->disabled(),
            Forms\Components\KeyValue::make('crm_response')->disabled(),
            Forms\Components\TextInput::make('utm_source')->disabled(),
            Forms\Components\TextInput::make('utm_medium')->disabled(),
            Forms\Components\TextInput::make('utm_campaign')->disabled(),
            Forms\Components\TextInput::make('referrer')->disabled(),
            Forms\Components\TextInput::make('ip')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M H:i')->sortable(),
                Tables\Columns\TextColumn::make('source_domain')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('locale')->badge(),
                Tables\Columns\TextColumn::make('brand')->badge(),
                Tables\Columns\TextColumn::make('crm_target')->badge(),
                Tables\Columns\TextColumn::make('crm_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        'skipped' => 'gray',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('form_type'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('crm_status')->options([
                    'sent' => 'Sent', 'pending' => 'Pending', 'failed' => 'Failed', 'skipped' => 'Skipped',
                ]),
                Tables\Filters\SelectFilter::make('crm_target')->options([
                    'omnigos' => 'Omnigos', 'linguland' => 'Linguland',
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'view' => Pages\ViewLead::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
