<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                    'header' => 'Header (basit)',
                    'hero' => 'Hero (basit)',
                    'rich_text' => 'Rich Text',
                    'footer' => 'Footer (basit)',
                    'hero_school' => '🎓 Hero — School',
                    'course_grid' => '🎓 Course Grid',
                    'accommodation_grid' => '🏠 Accommodation Grid',
                    'city_highlights' => '🌆 City Highlights',
                    'article_list' => '📰 Article List',
                    'pricing_table' => '💰 Pricing Table',
                    'contact_form' => '📨 Contact Form',
                    'faq' => '❓ FAQ',
                    'testimonials' => '💬 Testimonials',
                    'trust_bar' => '🤝 Trust Bar (logos)',
                    'cta_banner' => '🔔 CTA Banner',
                    'footer_mega' => '🦶 Footer Mega',
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

    public static function blockSchema(string $type): array
    {
        return match ($type) {
            // ─── Generic ─────────────────────────────────────────────────
            'header' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\TextInput::make('logo_url')->url(),
                Forms\Components\Repeater::make('links')
                    ->schema([
                        Forms\Components\TextInput::make('label')->required(),
                        Forms\Components\TextInput::make('href')->required(),
                    ])->columns(2),
            ],
            'hero' => [
                Forms\Components\TextInput::make('headline'),
                Forms\Components\Textarea::make('subheadline')->rows(2),
                Forms\Components\TextInput::make('cta_label'),
                Forms\Components\TextInput::make('cta_href'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
                Forms\Components\FileUpload::make('background_image')->image()->visibility('public'),
            ],
            'rich_text' => [
                Forms\Components\Textarea::make('markdown')->rows(10)
                    ->helperText('Standart markdown.'),
            ],
            'footer' => [
                Forms\Components\TextInput::make('text'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
            ],

            // ─── School microsite ────────────────────────────────────────
            'hero_school' => [
                Forms\Components\TextInput::make('badge_text')
                    ->placeholder('Yeni')
                    ->helperText('Üst rozet (opsiyonel) — örn "%50 indirim"'),
                Forms\Components\TextInput::make('headline')->required(),
                Forms\Components\Textarea::make('subheadline')->rows(2),
                Forms\Components\TextInput::make('cta_label')->placeholder('Hemen başvur'),
                Forms\Components\TextInput::make('cta_href')->placeholder('#contact'),
                Forms\Components\TextInput::make('secondary_cta_label')->placeholder('Kursları gör'),
                Forms\Components\TextInput::make('secondary_cta_href')->placeholder('/courses'),
                Forms\Components\FileUpload::make('background_image')
                    ->image()->visibility('public')
                    ->helperText('Tam ekran arkaplan görseli'),
                Forms\Components\ColorPicker::make('overlay_color')
                    ->helperText('Görsel üstü koyu/açık overlay rengi'),
            ],

            'course_grid' => [
                Forms\Components\TextInput::make('title')->placeholder('Popüler Kurslarımız'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('items')
                    ->label('Kurslar')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required()->placeholder('General English'),
                        Forms\Components\Select::make('level')->options([
                            'a1' => 'A1', 'a2' => 'A2', 'b1' => 'B1', 'b2' => 'B2', 'c1' => 'C1', 'c2' => 'C2',
                            'mixed' => 'All levels',
                        ]),
                        Forms\Components\TextInput::make('duration')->placeholder('20 hafta / 600 ders saati'),
                        Forms\Components\TextInput::make('price_from')->placeholder('£250/hafta'),
                        Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                        Forms\Components\TextInput::make('href')->placeholder('/courses/general-english'),
                        Forms\Components\Textarea::make('description')->rows(2),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
            ],

            'accommodation_grid' => [
                Forms\Components\TextInput::make('title')->placeholder('Konaklama Seçenekleri'),
                Forms\Components\Repeater::make('items')
                    ->label('Konaklama tipleri')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\Select::make('type')->options([
                            'host_family' => 'Host Family',
                            'residence' => 'Student Residence',
                            'shared_apartment' => 'Shared Apartment',
                            'private_apartment' => 'Private Apartment',
                            'hotel' => 'Hotel',
                        ]),
                        Forms\Components\TextInput::make('price_per_week')->placeholder('£180/hafta'),
                        Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                        Forms\Components\Textarea::make('description')->rows(2),
                        Forms\Components\TagsInput::make('features')->placeholder('Wi-Fi, kahvaltı, ulaşım'),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
            ],

            'city_highlights' => [
                Forms\Components\TextInput::make('title')->placeholder('Şehir Hakkında'),
                Forms\Components\Textarea::make('intro')->rows(3),
                Forms\Components\Repeater::make('highlights')
                    ->schema([
                        Forms\Components\TextInput::make('icon')->placeholder('heroicon-o-map')
                            ->helperText('İkon adı veya emoji'),
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\Textarea::make('description')->rows(2),
                    ])->columns(3)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
            ],

            'article_list' => [
                Forms\Components\TextInput::make('title')->placeholder('Şehir Rehberi'),
                Forms\Components\Repeater::make('items')
                    ->label('Yazılar')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\Textarea::make('excerpt')->rows(2),
                        Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                        Forms\Components\DatePicker::make('date'),
                        Forms\Components\TextInput::make('href')->placeholder('/city-guide/london-tips'),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['title'] ?? null),
            ],

            'pricing_table' => [
                Forms\Components\TextInput::make('title')->placeholder('Fiyatlar'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('plans')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\TextInput::make('price')->required()->placeholder('£250'),
                        Forms\Components\TextInput::make('period')->placeholder('haftalık'),
                        Forms\Components\TagsInput::make('features')->placeholder('15 saat ders, materyal dahil...'),
                        Forms\Components\TextInput::make('cta_label')->placeholder('Seç'),
                        Forms\Components\TextInput::make('cta_href')->placeholder('#contact'),
                        Forms\Components\Toggle::make('highlighted')->label('Önerilen'),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
            ],

            'contact_form' => [
                Forms\Components\TextInput::make('title')->placeholder('Bize ulaşın'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Select::make('form_type')
                    ->options([
                        'contact' => 'Contact (genel)',
                        'brochure' => 'Brochure download',
                        'callback' => 'Call me back',
                        'price_quote' => 'Price quote',
                    ])->default('contact')->required(),
                Forms\Components\TextInput::make('success_message')
                    ->placeholder('Teşekkürler! En kısa sürede dönüş yapacağız.'),
                Forms\Components\TextInput::make('cta_label')->default('Gönder'),
                Forms\Components\Toggle::make('show_phone')->label('Telefon alanı göster')->default(true),
                Forms\Components\Toggle::make('show_message')->label('Mesaj alanı göster')->default(true),
                Forms\Components\Toggle::make('show_course_interest')->label('Kurs ilgi alanı göster'),
            ],

            'faq' => [
                Forms\Components\TextInput::make('title')->placeholder('Sıkça Sorulan Sorular'),
                Forms\Components\Repeater::make('items')
                    ->schema([
                        Forms\Components\TextInput::make('question')->required(),
                        Forms\Components\Textarea::make('answer')->rows(3)->required(),
                    ])->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['question'] ?? null),
            ],

            'testimonials' => [
                Forms\Components\TextInput::make('title')->placeholder('Öğrencilerimiz Ne Diyor'),
                Forms\Components\Repeater::make('items')
                    ->schema([
                        Forms\Components\Textarea::make('quote')->rows(3)->required(),
                        Forms\Components\TextInput::make('author')->required(),
                        Forms\Components\TextInput::make('author_title')->placeholder('Türkiye, B2'),
                        Forms\Components\FileUpload::make('avatar_url')->image()->visibility('public'),
                        Forms\Components\TextInput::make('rating')->numeric()->minValue(1)->maxValue(5),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['author'] ?? null),
            ],

            'trust_bar' => [
                Forms\Components\TextInput::make('title')->placeholder('Akreditasyonlarımız'),
                Forms\Components\Repeater::make('logos')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\FileUpload::make('image_url')->image()->visibility('public')->required(),
                        Forms\Components\TextInput::make('href')->url()
                            ->helperText('Outbound link — yalnızca whitelist domain\'e (Topstudy/Linguland/parent brand) yönlendirin.'),
                    ])->columns(3)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
            ],

            'cta_banner' => [
                Forms\Components\TextInput::make('headline')->required(),
                Forms\Components\Textarea::make('text')->rows(2),
                Forms\Components\TextInput::make('cta_label')->required(),
                Forms\Components\TextInput::make('cta_href')->required(),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
                Forms\Components\FileUpload::make('background_image')->image()->visibility('public'),
            ],

            'footer_mega' => [
                Forms\Components\TextInput::make('logo_url')->url(),
                Forms\Components\Textarea::make('tagline')->rows(2),
                Forms\Components\Repeater::make('columns')
                    ->label('Link sütunları')
                    ->schema([
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\Repeater::make('links')
                            ->schema([
                                Forms\Components\TextInput::make('label')->required(),
                                Forms\Components\TextInput::make('href')->required(),
                            ])->columns(2),
                    ])->collapsed(),
                Forms\Components\Repeater::make('social_links')
                    ->schema([
                        Forms\Components\Select::make('platform')->options([
                            'facebook' => 'Facebook', 'instagram' => 'Instagram',
                            'twitter' => 'Twitter/X', 'youtube' => 'YouTube',
                            'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok',
                        ])->required(),
                        Forms\Components\TextInput::make('href')->url()->required(),
                    ])->columns(2),
                Forms\Components\TextInput::make('copyright_text'),
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
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
