<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        $localeOptions = collect(config('locales.supported', []))
            ->map(fn ($v, $k) => "{$k} — {$v['native_name']}")
            ->toArray();

        $brandOptions = collect(config('brands', []))
            ->map(fn ($v, $k) => $v['name'])
            ->toArray();

        return $form->schema([
            Forms\Components\Section::make('Kimlik')->schema([
                Forms\Components\TextInput::make('domain')
                    ->required()
                    ->placeholder('site-a.local veya kaplan-london.com')
                    ->helperText('www. yazma. Sadece kanonik host.')
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->required()->maxLength(120),
                Forms\Components\Select::make('brand')
                    ->options($brandOptions)
                    ->placeholder('Marka seç (opsiyonel)'),
                Forms\Components\TextInput::make('city')->maxLength(80)->placeholder('London'),
                Forms\Components\TextInput::make('country')
                    ->maxLength(2)
                    ->placeholder('GB')
                    ->helperText('ISO 3166-1 alpha-2 (US, GB, FR, DE, ES, ...)'),
                Forms\Components\Select::make('default_locales')
                    ->multiple()
                    ->options($localeOptions)
                    ->helperText('Bu sitenin yayında olduğu diller. Boş bırakırsan tüm desteklenen diller.'),
            ])->columns(2),

            Forms\Components\Section::make('Frontend Webhook')->schema([
                Forms\Components\TextInput::make('revalidate_url')
                    ->url()
                    ->placeholder('https://kaplan-london.com/api/revalidate'),
                Forms\Components\TextInput::make('revalidate_secret')
                    ->password()
                    ->revealable()
                    ->helperText('Frontend ile aynı değer olmalı (openssl rand -hex 32).'),
            ])->columns(2)->collapsible(),

            Forms\Components\Section::make('Tema')->schema([
                Forms\Components\KeyValue::make('theme')
                    ->keyLabel('anahtar')
                    ->valueLabel('değer')
                    ->helperText('Örn: primary_color = #0ea5e9, font = Inter, logo_url = https://...'),
            ])->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $brandLabels = collect(config('brands', []))->map(fn ($v) => $v['name'])->toArray();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('brand')
                    ->formatStateUsing(fn (?string $state) => $state ? ($brandLabels[$state] ?? $state) : '—')
                    ->badge(),
                Tables\Columns\TextColumn::make('city'),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('pages_count')->counts('pages')->label('Sayfa'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brand')->options($brandLabels),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            SiteResource\RelationManagers\PagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
        ];
    }
}
