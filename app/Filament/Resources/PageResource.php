<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Resources\PageResource\RelationManagers\BlocksRelationManager;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $localeOptions = collect(config('locales.supported', []))
            ->map(fn ($v, $k) => "{$k} — {$v['native_name']}")
            ->toArray();

        return $form->schema([
            Forms\Components\Section::make('Sayfa')->schema([
                Forms\Components\Select::make('site_id')
                    ->relationship('site', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('locale')
                    ->required()
                    ->options($localeOptions)
                    ->default(config('locales.default'))
                    ->helperText('Bu sayfanın dili. Aynı slug + farklı locale = aynı sayfanın çeviri kaydı.'),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->placeholder('/ veya /courses')
                    ->helperText('Locale-bağımsız slug. URL\'de /[locale]/[slug] olur.'),
                Forms\Components\TextInput::make('title')->required()->maxLength(200),
                Forms\Components\Toggle::make('is_published')->label('Yayında'),
            ])->columns(2),

            Forms\Components\Section::make('SEO')->schema([
                Forms\Components\TextInput::make('seo.title')->label('SEO başlık'),
                Forms\Components\Textarea::make('seo.description')->label('SEO açıklama')->rows(2),
                Forms\Components\TextInput::make('seo.og_image')->label('OG görsel URL')->url(),
            ])->columns(1)->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.domain')->sortable(),
                Tables\Columns\TextColumn::make('locale')->badge()->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('blocks_count')->counts('blocks')->label('Blok'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site')->relationship('site', 'name'),
                Tables\Filters\SelectFilter::make('locale')
                    ->options(fn () => collect(config('locales.supported', []))
                        ->map(fn ($v, $k) => "{$k} — {$v['native_name']}")
                        ->toArray()),
                Tables\Filters\TernaryFilter::make('is_published'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [BlocksRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
