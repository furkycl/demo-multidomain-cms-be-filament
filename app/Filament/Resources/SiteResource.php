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
        return $form->schema([
            Forms\Components\Section::make('Kimlik')->schema([
                Forms\Components\TextInput::make('domain')
                    ->required()
                    ->placeholder('acme.com')
                    ->helperText('www. yazma. Sadece kanonik host.')
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(120),
            ])->columns(2),

            Forms\Components\Section::make('Frontend Webhook')->schema([
                Forms\Components\TextInput::make('revalidate_url')
                    ->url()
                    ->placeholder('https://acme.com/api/revalidate')
                    ->helperText('Bu site Vercel\'de açıldıktan sonra doldur.'),
                Forms\Components\TextInput::make('revalidate_secret')
                    ->password()
                    ->revealable()
                    ->helperText('Frontend ile aynı değer olmalı.'),
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
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('domain')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('pages_count')->counts('pages')->label('Sayfa'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
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
