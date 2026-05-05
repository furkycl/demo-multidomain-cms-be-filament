<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Page'in blokları — sürükle-bırak sıralı, her tip kendi formuyla.
 *
 * Yeni blok tipi eklemek için:
 * 1. cms-architect ile docs/blocks/<type>.md spec'i yaz
 * 2. App\Models\Block::TYPES dizisine ekle
 * 3. blockSchema() içine kendi field'larını ekle
 * 4. Frontend'de components/blocks/<Name>.tsx ve types.ts'yi güncelle
 */
class BlocksRelationManager extends RelationManager
{
    protected static string $relationship = 'blocks';

    protected static ?string $title = 'Bloklar';

    protected static ?string $recordTitleAttribute = 'type';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->required()
                ->live()
                ->options([
                    'header' => 'Header',
                    'hero' => 'Hero',
                    'rich_text' => 'Rich Text',
                    'footer' => 'Footer',
                ]),

            Forms\Components\TextInput::make('order')
                ->numeric()
                ->default(fn ($livewire) => $livewire->ownerRecord->blocks()->count())
                ->required(),

            Forms\Components\Group::make()
                ->statePath('content')
                ->schema(fn (Forms\Get $get) => self::blockSchema($get('type') ?? 'header')),
        ]);
    }

    /**
     * Tip-spesifik form alanları. Her tipin kendi alanları var.
     */
    public static function blockSchema(string $type): array
    {
        return match ($type) {
            'header' => [
                Forms\Components\TextInput::make('title')->label('Başlık'),
                Forms\Components\ColorPicker::make('background_color')->label('Arkaplan rengi'),
                Forms\Components\TextInput::make('logo_url')->url()->label('Logo URL'),
                Forms\Components\Repeater::make('links')
                    ->label('Menü linkleri')
                    ->schema([
                        Forms\Components\TextInput::make('label')->required(),
                        Forms\Components\TextInput::make('href')->required(),
                    ])
                    ->columns(2),
            ],
            'hero' => [
                Forms\Components\TextInput::make('headline')->label('Ana başlık'),
                Forms\Components\Textarea::make('subheadline')->label('Alt başlık')->rows(2),
                Forms\Components\TextInput::make('cta_label')->label('Buton metni'),
                Forms\Components\TextInput::make('cta_href')->label('Buton link'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
                Forms\Components\FileUpload::make('background_image')
                    ->image()
                    ->disk(config('filesystems.default'))
                    ->visibility('public'),
            ],
            'rich_text' => [
                Forms\Components\Textarea::make('markdown')
                    ->label('Markdown')
                    ->rows(10)
                    ->helperText('Standart markdown. Frontend güvenli şekilde render eder.'),
            ],
            'footer' => [
                Forms\Components\TextInput::make('text')->label('Footer metni'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
            ],
            default => [],
        };
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')->sortable()->label('#'),
                Tables\Columns\TextColumn::make('type')->badge(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
