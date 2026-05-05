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

    private static function typeOptions(): array
    {
        $labels = [
            'hero' => 'Hero (basit)', 'hero_school' => 'Hero — School', 'hero_video' => 'Hero — Video (YouTube)',
            'rich_text' => 'Rich Text', 'course_grid' => 'Programs / Courses Grid',
            'accommodation_grid' => 'Accommodation Grid', 'destinations_grid' => 'Destinations Grid',
            'about' => 'About / Why Choose Us', 'city_highlights' => 'City Highlights',
            'article_list' => 'Article List (blog)', 'header' => 'Header', 'footer' => 'Footer (basit)',
            'footer_mega' => 'Footer Mega', 'trust_bar' => 'Trust Bar (logos)',
            'cta_banner' => 'CTA Banner', 'contact_form' => 'Contact Form',
            'pricing_table' => 'Pricing Table', 'faq' => 'FAQ', 'testimonials' => 'Testimonials',
        ];
        $grouped = [];
        foreach (Block::CATEGORIES as $group => $types) {
            foreach ($types as $t) $grouped[$group][$t] = $labels[$t] ?? $t;
        }
        return $grouped;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->required()->live()->searchable()->options(self::typeOptions()),
            Forms\Components\TextInput::make('order')->numeric()
                ->default(fn ($livewire) => $livewire->ownerRecord->blocks()->count())->required(),
            Forms\Components\Group::make()->statePath('content')
                ->schema(fn (Forms\Get $get) => self::blockSchema($get('type') ?? 'hero_video')),
        ]);
    }

    public static function blockSchema(string $type): array
    {
        return match ($type) {
            'hero_video' => [
                Forms\Components\TextInput::make('youtube_url')
                    ->placeholder('https://www.youtube.com/watch?v=...')
                    ->helperText('YouTube link\'i — autoplay+mute+loop ile arkaplan oynatılır.'),
                Forms\Components\TextInput::make('badge_text')->placeholder('30+ destinations'),
                Forms\Components\TextInput::make('headline')->required()->placeholder('Live, study, thrive'),
                Forms\Components\Textarea::make('subheadline')->rows(3),
                Forms\Components\TextInput::make('cta_label')->placeholder('Explore destinations'),
                Forms\Components\TextInput::make('cta_href')->placeholder('#locations'),
                Forms\Components\TextInput::make('secondary_cta_label')->placeholder('Free consultation'),
                Forms\Components\TextInput::make('secondary_cta_href')->placeholder('#contact'),
                Forms\Components\TextInput::make('poster_image')->url(),
                Forms\Components\Repeater::make('stats')->label('İstatistikler (4 kutu önerilir)')
                    ->schema([
                        Forms\Components\TextInput::make('value')->required()->placeholder('80+'),
                        Forms\Components\TextInput::make('label')->required()->placeholder('Years'),
                    ])->columns(2)->collapsed()->itemLabel(fn ($s) => ($s['value'] ?? '').' '.($s['label'] ?? '')),
            ],
            'hero_school' => [
                Forms\Components\TextInput::make('badge_text'),
                Forms\Components\TextInput::make('headline')->required(),
                Forms\Components\Textarea::make('subheadline')->rows(2),
                Forms\Components\TextInput::make('cta_label'), Forms\Components\TextInput::make('cta_href'),
                Forms\Components\TextInput::make('secondary_cta_label'), Forms\Components\TextInput::make('secondary_cta_href'),
                Forms\Components\FileUpload::make('background_image')->image()->visibility('public'),
                Forms\Components\ColorPicker::make('overlay_color')->default('rgba(15,30,61,0.55)'),
            ],
            'hero' => [
                Forms\Components\TextInput::make('headline'),
                Forms\Components\Textarea::make('subheadline')->rows(2),
                Forms\Components\TextInput::make('cta_label'), Forms\Components\TextInput::make('cta_href'),
                Forms\Components\ColorPicker::make('background_color'), Forms\Components\ColorPicker::make('text_color'),
                Forms\Components\FileUpload::make('background_image')->image()->visibility('public'),
            ],
            'destinations_grid' => [
                Forms\Components\TextInput::make('title')->placeholder('Our Destinations'),
                Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('items')->label('Destinations')->schema([
                    Forms\Components\TextInput::make('city')->required(),
                    Forms\Components\TextInput::make('country'),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public')
                        ->helperText('Kart kapak görseli (16:9)'),
                    Forms\Components\TextInput::make('youtube_url')
                        ->helperText('Hover\'da oynayacak video URL\'i (opsiyonel)'),
                    Forms\Components\TextInput::make('href')->placeholder('/destinations/london'),
                    Forms\Components\Textarea::make('description')->rows(2),
                    Forms\Components\TextInput::make('badge')->placeholder('Popular'),
                ])->columns(2)->collapsed()
                  ->itemLabel(fn ($s) => isset($s['city']) ? $s['city'].(isset($s['country']) ? ' — '.$s['country'] : '') : null),
            ],
            'about' => [
                Forms\Components\TextInput::make('title')->required()->placeholder('Why Choose Us'),
                Forms\Components\Textarea::make('description')->rows(3),
                Forms\Components\FileUpload::make('image_url')->image()->visibility('public')
                    ->helperText('Sağ taraftaki görsel (önerilen 700x500)'),
                Forms\Components\TextInput::make('badge_value')->placeholder('80+')
                    ->helperText('Görsel üzerindeki kırmızı rozet rakamı'),
                Forms\Components\TextInput::make('badge_label')->placeholder('Years of Excellence'),
                Forms\Components\Repeater::make('features')->label('Özellikler (4 önerilir)')
                    ->schema([
                        Forms\Components\TextInput::make('icon')->placeholder('🏆')
                            ->helperText('Emoji veya ikon adı'),
                        Forms\Components\TextInput::make('title')->required(),
                        Forms\Components\Textarea::make('description')->rows(2),
                    ])->columns(3)->collapsed()->itemLabel(fn ($s) => $s['title'] ?? null),
            ],
            'course_grid' => [
                Forms\Components\TextInput::make('title'), Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('level')->options(['a1'=>'A1','a2'=>'A2','b1'=>'B1','b2'=>'B2','c1'=>'C1','c2'=>'C2','mixed'=>'All levels']),
                    Forms\Components\TextInput::make('duration'), Forms\Components\TextInput::make('price_from'),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                    Forms\Components\TextInput::make('href'), Forms\Components\Textarea::make('description')->rows(2),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['name'] ?? null),
            ],
            'accommodation_grid' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('type')->options(['host_family'=>'Host Family','residence'=>'Residence','shared_apartment'=>'Shared Apt','private_apartment'=>'Private Apt','hotel'=>'Hotel']),
                    Forms\Components\TextInput::make('price_per_week'),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                    Forms\Components\Textarea::make('description')->rows(2),
                    Forms\Components\TagsInput::make('features'),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['name'] ?? null),
            ],
            'city_highlights' => [
                Forms\Components\TextInput::make('title'), Forms\Components\Textarea::make('intro')->rows(3),
                Forms\Components\Repeater::make('highlights')->schema([
                    Forms\Components\TextInput::make('icon'), Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Textarea::make('description')->rows(2),
                ])->columns(3)->collapsed()->itemLabel(fn ($s) => $s['title'] ?? null),
            ],
            'article_list' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\Repeater::make('items')->schema([
                    Forms\Components\TextInput::make('title')->required(),
                    Forms\Components\Textarea::make('excerpt')->rows(2),
                    Forms\Components\FileUpload::make('image_url')->image()->visibility('public'),
                    Forms\Components\DatePicker::make('date'),
                    Forms\Components\TextInput::make('href'), Forms\Components\TextInput::make('category'),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['title'] ?? null),
            ],
            'rich_text' => [Forms\Components\Textarea::make('markdown')->rows(10)],
            'contact_form' => [
                Forms\Components\TextInput::make('title'), Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Select::make('form_type')->options(['contact'=>'Contact','brochure'=>'Brochure','callback'=>'Callback','price_quote'=>'Price quote'])->default('contact'),
                Forms\Components\TextInput::make('success_message'), Forms\Components\TextInput::make('cta_label'),
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
                Forms\Components\ColorPicker::make('background_color')->default('#0F1A3C'),
                Forms\Components\ColorPicker::make('text_color')->default('#ffffff'),
                Forms\Components\FileUpload::make('background_image')->image()->visibility('public'),
            ],
            'pricing_table' => [
                Forms\Components\TextInput::make('title'), Forms\Components\Textarea::make('intro')->rows(2),
                Forms\Components\Repeater::make('plans')->schema([
                    Forms\Components\TextInput::make('name')->required(), Forms\Components\TextInput::make('price')->required(),
                    Forms\Components\TextInput::make('period'), Forms\Components\TagsInput::make('features'),
                    Forms\Components\TextInput::make('cta_label'), Forms\Components\TextInput::make('cta_href'),
                    Forms\Components\Toggle::make('highlighted'),
                ])->columns(2)->collapsed()->itemLabel(fn ($s) => $s['name'] ?? null),
            ],
            'faq' => [
                Forms\Components\TextInput::make('title'),
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
            'header' => [
                Forms\Components\TextInput::make('title'),
                Forms\Components\ColorPicker::make('background_color')->default('#ffffff'),
                Forms\Components\TextInput::make('logo_url')->url()
                    ->helperText('Header logosu — değişen tüm sayfalara yansır.'),
                Forms\Components\TextInput::make('operated_by_text')->placeholder('Operated by'),
                Forms\Components\TextInput::make('operated_by_logo')->url(),
                Forms\Components\TextInput::make('operated_by_href')->url(),
                Forms\Components\TextInput::make('cta_label')->default('Free consultation'),
                Forms\Components\TextInput::make('cta_href')->default('#contact'),
                Forms\Components\Repeater::make('links')->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('href')->required(),
                ])->columns(2)->itemLabel(fn ($s) => $s['label'] ?? null),
            ],
            'footer' => [
                Forms\Components\TextInput::make('text'),
                Forms\Components\ColorPicker::make('background_color')->default('#0F1A3C'),
                Forms\Components\ColorPicker::make('text_color')->default('#cbd5e1'),
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
                    Forms\Components\Select::make('platform')->options(['facebook'=>'Facebook','instagram'=>'Instagram','twitter'=>'Twitter/X','youtube'=>'YouTube','linkedin'=>'LinkedIn','tiktok'=>'TikTok'])->required(),
                    Forms\Components\TextInput::make('href')->url()->required(),
                ])->columns(2),
                Forms\Components\TextInput::make('copyright_text'),
                Forms\Components\ColorPicker::make('background_color')->default('#0F1A3C'),
                Forms\Components\ColorPicker::make('text_color')->default('#cbd5e1'),
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
            ->defaultSort('order')->reorderable('order')
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
