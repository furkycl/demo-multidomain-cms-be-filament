<?php

declare(strict_types=1);

namespace App\Filament\Resources\SiteResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PagesRelationManager extends RelationManager
{
    protected static string $relationship = 'pages';

    protected static ?string $title = 'Sayfalar';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('slug')->required()->placeholder('/'),
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\Toggle::make('is_published')->label('Yayında'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('slug')->searchable(),
                Tables\Columns\TextColumn::make('title')->searchable(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
                Tables\Columns\TextColumn::make('blocks_count')->counts('blocks')->label('Blok'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('edit_full')
                    ->label('Tam düzenle')
                    ->url(fn ($record) => route('filament.admin.resources.pages.edit', $record))
                    ->icon('heroicon-m-pencil-square'),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
