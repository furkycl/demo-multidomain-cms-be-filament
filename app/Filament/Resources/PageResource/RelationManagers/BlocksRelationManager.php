<?php

declare(strict_types=1);

namespace App\Filament\Resources\PageResource\RelationManagers;

use App\Models\Block;
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

    /** Filament Select için: kategori başlıkları altında gruplandırılmış options. */
    private static function typeOptions(): array
    {
        $labels = [
            'hero' => 'Hero (basit)',
            'hero_school' => 'Hero — School',
            'hero_video' => 'Hero — Video (YouTube embed)',
            'rich_text' => 'Rich Text',
            'course_grid' => 'Course Grid',
            'accommodation_grid' => 'Accommodation Grid',
            'destinations_grid' => 'Destinations Grid',
            'city_highlights' => 'City Highlights',
            'article_list' => 'Article List (blog)',
            'header' => 'Header (basit)',
            'footer' => 'Footer (basit)',
            'footer_mega' => 'Footer Mega',
            'trust_bar' => 'Trust Bar',
            'cta_banner' => 'CTA Banner',
            'contact_form' => 'Contact Form',
            'pricing_table' => 'Pricing Table',
            'faq' => 'FAQ',
            'testimonials' => 'Testimonials',
        ];

        $grouped = [];
        foreach (Block::CATEGORIES as $group => $types) {
            foreach ($types as $t) {
                $grouped[$group][$t] = $labels[$t] ?? $t;
            }
        }

        return $grouped;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->required()
                ->live()
                ->options(self::typeOptions())
                ->searchable(),

            Forms\Components\TextInput::make('order')
                ->numeric()
                ->default(fn ($livewire) => $livewire->ownerRecord->blocks()->count())
                ->required(),

            Forms\Components\Group::make()
                ->statePath('content')
                ->schema(fn (Forms\Get $get) => self::blockSchema($get('type') ?? 'hero_video')),
        ]);
    }

    public static function blockSchema(string $type): array
    {
        return match ($type) {
            // ─── HERO ────────────────────────────────────────────────────
            'hero_video' => [
                Forms\Components\TextInput::make('youtube_url')
                    ->required()
                    ->placeholder('https://www.youtube.com/watch?v=XXXX veya https://youtu.be/XXXX')
                    ->helperText('YouTube link\'i — video ID otomatik çıkarılır, frontend autoplay+mute+loop ile arkaplan video gösterir.'),
                Forms\Components\TextInput::make('badge_text')->placeholder('Yeni dönem kayıtları açıldı'),
                Forms\Components\TextInput::make('headline')->required()->placeholder('Dünyayı keşfet, dilini öğren'),
                Forms\Components\Textarea::make('subheadline')->rows(2),
                Forms\Components\TextInput::make('cta_label')->placeholder('Hemen başvur')->default('Hemen başvur'),
                Forms\Components\TextInput::make('cta_href')->placeholder('#contact')->default('#contact'),
                Forms\Components\TextInput::make('secondary_cta_label')->placeholder('Destinations'),
                Forms\Components\TextInput::make('secondary_cta_href')->placeholder('/destinations')->default('/destinations'),
                Forms\Components\ColorPicker::make('overlay_color')
                    ->default('rgba(15,30,61,0.5)')
                    ->helperText('Video üstü koyu/açık overlay'),
                Forms\Components\TextInput::make('poster_image')
                    ->url()
                    ->helperText('Video yüklenene kadar gösterilen statik görsel (opsiyonel)'),
            ],
            'hero_school' => [
                Forms\Components\TextInput::make('badge_text')->placeholder('Yeni'),
                Forms\Components\TextInput::make('headline')->required(),
                Forms\Components\Textarea::make('subheadline')->rows(2),
                Forms\Components\TextInput::make('cta_label')->placeholder('Hemen başvur'),
                Forms\Components\TextInput::make('cta_href')->placeholder('#contact'),
                Forms\Components\TextInput::make('secondary_cta_label'),
                Forms\Components\TextInput::make('secondary_cta_href'),
                Forms\Components\FileUpload::make('background_image')->image()->visibility('public'),
                Forms\Components\ColorPicker::make('overlay_color'),
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

            // ─── İÇERİK ──────────────────────────────────────────────────
            'destinations_grid' => [
                Forms\Components\TextInput::make('title')->placeholder('Lokasyonlarımız'),
                Forms\Components\Textarea::make('intro')->rows(2)->placeholder('Dünya genelinde 40+ lokasyon. Sana en uyanı seç.'),
                Forms\Components\Repeater::make('items')
                    ->label('Destinations')
                    ->schema([
                        Forms\Components\TextInput::make('city')->required()->placeholder('London'),
                        Forms\Components\TextInput::make('country')->placeholder('United Kingdom'),
                        Forms\Components\FileUpload::make('image_url')
                            ->image()->visibility('public')
                            ->helperText('Şehir kapak görseli (16:9 önerilir)'),
                        Forms\Components\TextInput::make('href')
                            ->placeholder('/destinations/london')
                            ->helperText('Bu site içinde detay sayfası (cross-link YOK).'),
                        Forms\Components\Textarea::make('description')->rows(2),
                        Forms\Components\TextInput::make('badge')
                            ->placeholder('Popular')
                            ->helperText('Köşede gösterilen rozet (opsiyonel)'),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => isset($state['city']) ? $state['city'].' — '.($state['country'] ?? '') : null),
            ],
            'course_grid' => [
                Forms\Components\TextInput::make('title')->placeholder('Programlar'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('items')
                    ->label('Programlar / Ürünler')
                    ->schema([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\Select::make('level')->options([
                            'a1' => 'A1', 'a2' => 'A2', 'b1' => 'B1', 'b2' => 'B2', 'c1' => 'C1', 'c2' => 'C2',
                            'mixed' => 'Tüm seviyeler',
                        ]),
                        Forms\Components\TextInput::make('duration')->placeholder('20 ders/hafta'),
                        Forms\Components\TextInput::make('price_from')->placeholder('£295/hafta'),
                        Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                        Forms\Components\TextInput::make('href')->placeholder('/products/general-english'),
                        Forms\Components\Textarea::make('description')->rows(2),
                    ])->columns(2)->collapsed()
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null),
            ],
            'accommodation_grid' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('type')->options([
                        'host_family' => 'Host Family', 'residence' => 'Student Residence',
                        'shared_apartment' => 'Shared Apartment', 'private_apartment' => 'Private Apartment', 'hotel' => 'Hotel',
                    ]),
                    Forms\Components\TextInput::make('price_per_week'),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                    Forms\Components\Textarea::make('description')->rows(2),
                    Forms\Components\TagsInput::make('features'),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['name'] ?? null),
            ],
            'city_highlights' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Textarea::make('intro')->rows(3),
                Forms\Components\Repeater::make('highlights')->schema([
                    Forms\Components\TextInput::make('icon')->placeholder('🎓'),
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Textarea::make('description')->rows(2),
                ])->columns(3)->collapsed()->itemLabel(fn ($s) => $s['title'] ?? null),
            ],
            'article_list' => [
                Forms\Components\TextInput::make('title')->placeholder('Blog'),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Textarea::make('excerpt')->rows(2),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                    Forms\Components\DatePicker::make('date'),
                    Forms\Components\TextInput::make('href')->placeholder('/blog/london-tips'),
                    Forms\Components\TextInput::make('category')->placeholder('Şehir Rehberi'),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['title'] ?? null),
            ],
            'rich_text' => [
                Forms\Components\Textarea::make('markdown')->rows(10)->helperText('Markdown destekli.'),
            ],

            // ─── DÖNÜŞÜM ─────────────────────────────────────────────────
            'contact_form' => [
                Forms\Components\TextInput::make('title')->placeholder('Bize ulaşın'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Select::make('form_type')->options([
                    'contact' => 'Contact (genel)',
                    'brochure' => 'Brochure download',
                    'callback' => 'Call me back',
                    'price_quote' => 'Price quote',
                ])->default('contact')->required(),
                Forms\Components\TextInput::make('success_message')->default('Teşekkürler! En kısa sürede dönüş yapacağız.'),
                Forms\Components\TextInput::make('cta_label')->default('Gönder'),
                Forms\Components\Toggle::make('show_phone')->default(true),
                Forms\Components\Toggle::make('show_message')->default(true),
                Forms\Components\Toggle::make('show_country')->default(true),
                Forms\Components\Toggle::make('show_course_interest')->default(true),
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
            'pricing_table' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('plans')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('price')->required(),
                    Forms\Components\TextInput::make('period'),
                    Forms\Components\TagsInput::make('features'),
                    Forms\Components\TextInput::make('cta_label'),
                    Forms\Components\TextInput::make('cta_href'),
                    Forms\Components\Toggle::make('highlighted'),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['name'] ?? null),
            ],
            'faq' => [
                Forms\Components\TextInput::make('title')->placeholder('SSS'),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\TextInput::make('question')->required(),
                    Forms\Components\Textarea::make('answer')->rows(3)->required(),
                ])->collapsed()->itemLabel(fn ($s) => $s['question'] ?? null),
            ],
            'testimonials' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\Textarea::make('quote')->rows(3)->required(),
                    Forms\Components\TextInput::make('author')->required(),
                    Forms\Components\TextInput::make('author_title'),
                    Forms\Components\FileUpload::make('avatar_url')->image()->visibility('public'),
                    Forms\Components\TextInput::make('rating')->numeric()->minValue(1)->maxValue(5),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['author'] ?? null),
            ],

            // ─── DÜZEN ───────────────────────────────────────────────────
            'header' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\TextInput::make('logo_url')->url(),
                Forms\Components\Repeater::make('links')->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('href')->required(),
                ])->columns(2),
            ],
            'footer' => [
                Forms\Components\TextInput::make('text'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
            ],
            'footer_mega' => [
                Forms\Components\TextInput::make('logo_url')->url(),
                Forms\Components\Textarea::make('tagline')->rows(2),
                Forms\Components\Repeater::make('columns')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Repeater::make('links')->schema([
                        Forms\Components\TextInput::make('label')->required(),
                        Forms\Components\TextInput::make('href')->required(),
                    ])->columns(2),
                ])->collapsed(),
                Forms\Components\Repeater::make('social_links')->schema([
                    Forms\Components\Select::make('platform')->options([
                        'facebook' => 'Facebook', 'instagram' => 'Instagram',
                        'twitter' => 'Twitter/X', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'tiktok' => 'TikTok',
                    ])->required(),
                    Forms\Components\TextInput::make('href')->url()->required(),
                ])->columns(2),
                Forms\Components\TextInput::make('copyright_text'),
                Forms\Components\ColorPicker::make('background_color'),
                Forms\Components\ColorPicker::make('text_color'),
            ],
            'trust_bar' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Repeater::make('logos')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public')->required(),
                    Forms\Components\TextInput::make('href')->url(),
                ])->columns(3)->collapsed()->itemLabel(fn ($s) => $s['name'] ?? null),
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
