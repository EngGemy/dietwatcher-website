<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Settings\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;

class SettingsPage extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.settings-page';

    protected static ?int $navigationSort = 1000;

    public ?array $data = [];

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.settings');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.settings.title');
    }

    public function mount(): void
    {
        $this->form->fill($this->getFormState());
    }

    protected function getFormState(): array
    {
        $all = Setting::getAll()->keyBy('key');
        $logoHeader = $all->get('logo_header')?->value;
        $logoFooter = $all->get('logo_footer')?->value;
        $favicon = $all->get('favicon')?->value;

        return [
            'general' => [
                'site_name' => $all->get('site_name')?->value ?? config('app.name'),
                'contact_email' => $all->get('contact_email')?->value ?? '',
                'copyright_en' => $all->get('copyright_en')?->value ?? '© ' . date('Y') . ' Diet Watchers. All rights reserved.',
                'copyright_ar' => $all->get('copyright_ar')?->value ?? '© ' . date('Y') . ' ديت واتشرز. جميع الحقوق محفوظة.',
            ],
            'header' => [
                'logo_header' => $logoHeader ? (is_string($logoHeader) ? [$logoHeader] : $logoHeader) : [],
            ],
            'footer' => [
                'logo_footer' => $logoFooter ? (is_string($logoFooter) ? [$logoFooter] : $logoFooter) : [],
                'description_en' => $all->get('footer_description_en')?->value ?? 'Healthy Meals Delivered Daily. Designed for Your Goals.',
                'description_ar' => $all->get('footer_description_ar')?->value ?? 'وجبات صحية تُسلم يومياً. مصممة لأهدافك.',
                'social_instagram' => $all->get('social_instagram')?->value ?? '',
                'social_facebook' => $all->get('social_facebook')?->value ?? '',
                'social_twitter' => $all->get('social_twitter')?->value ?? '',
                'social_youtube' => $all->get('social_youtube')?->value ?? '',
                'app_store_url' => $all->get('app_store_url')?->value ?? '#',
                'play_store_url' => $all->get('play_store_url')?->value ?? '#',
            ],
            'favicon' => $favicon ? (is_string($favicon) ? [$favicon] : $favicon) : [],
            'checkout' => [
                'delivery_fee' => $all->get('delivery_fee')?->value ?? 25,
                'vat_rate' => $all->get('vat_rate')?->value ?? 15,
            ],
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')
                    ->tabs([
                        Tabs\Tab::make(__('admin.settings.groups.general'))
                            ->schema([
                                TextInput::make('general.site_name')
                                    ->label(__('admin.settings.fields.site_name'))
                                    ->maxLength(255),
                                TextInput::make('general.contact_email')
                                    ->label(__('admin.settings.fields.contact_email'))
                                    ->email()
                                    ->maxLength(255),
                                Section::make(__('admin.settings.fields.copyright'))
                                    ->schema([
                                        Textarea::make('general.copyright_en')
                                            ->label('English')
                                            ->rows(2),
                                        Textarea::make('general.copyright_ar')
                                            ->label('العربية')
                                            ->rows(2),
                                    ]),
                            ]),
                        Tabs\Tab::make(__('admin.settings.groups.header'))
                            ->schema([
                                FileUpload::make('header.logo_header')
                                    ->label(__('admin.settings.fields.logo_header'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('settings')
                                    ->nullable()
                                    ->multiple(false),
                            ]),
                        Tabs\Tab::make(__('admin.settings.groups.footer'))
                            ->schema([
                                FileUpload::make('footer.logo_footer')
                                    ->label(__('admin.settings.fields.logo_footer'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('settings')
                                    ->nullable()
                                    ->multiple(false),
                                Section::make(__('admin.settings.fields.footer_description'))
                                    ->schema([
                                        Textarea::make('footer.description_en')
                                            ->label('English')
                                            ->rows(3),
                                        Textarea::make('footer.description_ar')
                                            ->label('العربية')
                                            ->rows(3),
                                    ]),
                                Section::make(__('admin.settings.groups.social'))
                                    ->schema([
                                        TextInput::make('footer.social_instagram')
                                            ->label('Instagram URL')
                                            ->url()
                                            ->placeholder('https://instagram.com/...'),
                                        TextInput::make('footer.social_facebook')
                                            ->label('Facebook URL')
                                            ->url()
                                            ->placeholder('https://facebook.com/...'),
                                        TextInput::make('footer.social_twitter')
                                            ->label('Twitter/X URL')
                                            ->url()
                                            ->placeholder('https://twitter.com/...'),
                                        TextInput::make('footer.social_youtube')
                                            ->label('YouTube URL')
                                            ->url()
                                            ->placeholder('https://youtube.com/...'),
                                    ])->columns(2),
                                Section::make(__('admin.settings.fields.app_links'))
                                    ->schema([
                                        TextInput::make('footer.app_store_url')
                                            ->label('App Store URL')
                                            ->url()
                                            ->placeholder('https://apps.apple.com/...'),
                                        TextInput::make('footer.play_store_url')
                                            ->label('Google Play URL')
                                            ->url()
                                            ->placeholder('https://play.google.com/...'),
                                    ])->columns(2),
                            ]),
                        Tabs\Tab::make(__('admin.settings.fields.favicon'))
                            ->schema([
                                FileUpload::make('favicon')
                                    ->label(__('admin.settings.fields.favicon'))
                                    ->image()
                                    ->disk('public')
                                    ->directory('settings')
                                    ->nullable()
                                    ->multiple(false),
                            ]),
                        Tabs\Tab::make(__('Checkout'))
                            ->schema([
                                Section::make(__('admin.settings.sections.tax_vat'))
                                    ->schema([
                                        TextInput::make('checkout.delivery_fee')
                                            ->label(__('Delivery Fee (SAR)'))
                                            ->numeric()
                                            ->suffix('SAR')
                                            ->default(25),
                                        TextInput::make('checkout.vat_rate')
                                            ->label(__('admin.settings.fields.vat_rate'))
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(15)
                                            ->helperText(__('admin.settings.helpers.vat_rate')),
                                    ])->columns(2),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        // General settings
        Setting::setValue('site_name', $data['general']['site_name'] ?? '', 'general');
        Setting::setValue('contact_email', $data['general']['contact_email'] ?? '', 'general');
        Setting::setValue('copyright_en', $data['general']['copyright_en'] ?? '', 'general');
        Setting::setValue('copyright_ar', $data['general']['copyright_ar'] ?? '', 'general');

        // Header settings
        $logoHeader = $data['header']['logo_header'] ?? null;
        Setting::setValue('logo_header', is_array($logoHeader) ? ($logoHeader[0] ?? '') : (string) $logoHeader, 'header', 'string');

        // Footer settings
        $logoFooter = $data['footer']['logo_footer'] ?? null;
        Setting::setValue('logo_footer', is_array($logoFooter) ? ($logoFooter[0] ?? '') : (string) $logoFooter, 'footer', 'string');
        Setting::setValue('footer_description_en', $data['footer']['description_en'] ?? '', 'footer');
        Setting::setValue('footer_description_ar', $data['footer']['description_ar'] ?? '', 'footer');
        Setting::setValue('social_instagram', $data['footer']['social_instagram'] ?? '', 'social');
        Setting::setValue('social_facebook', $data['footer']['social_facebook'] ?? '', 'social');
        Setting::setValue('social_twitter', $data['footer']['social_twitter'] ?? '', 'social');
        Setting::setValue('social_youtube', $data['footer']['social_youtube'] ?? '', 'social');
        Setting::setValue('app_store_url', $data['footer']['app_store_url'] ?? '#', 'footer');
        Setting::setValue('play_store_url', $data['footer']['play_store_url'] ?? '#', 'footer');

        // Favicon
        $favicon = $data['favicon'] ?? null;
        Setting::setValue('favicon', is_array($favicon) ? ($favicon[0] ?? '') : (string) $favicon, 'general', 'string');

        // Checkout settings
        Setting::setValue('delivery_fee', $data['checkout']['delivery_fee'] ?? 25, 'checkout');
        Setting::setValue('vat_rate', $data['checkout']['vat_rate'] ?? 15, 'checkout');

        Notification::make()
            ->title(__('admin.settings.messages.saved'))
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label(__('common.save'))
                ->submit('save'),
        ];
    }
}
