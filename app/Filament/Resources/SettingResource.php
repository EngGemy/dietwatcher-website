<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SettingResource\Pages;
use App\Models\Settings\Setting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.settings');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('settings')
                ->tabs([
                    // General Settings Tab
                    Tab::make(__('admin.settings.tabs.general'))
                        ->icon('heroicon-o-globe-alt')
                        ->schema([
                            Section::make(__('admin.settings.sections.site_info'))
                                ->schema([
                                    TextInput::make('site_name')
                                        ->label(__('admin.settings.fields.site_name'))
                                        ->placeholder('Diet Watchers')
                                        ->required(),
                                    
                                    TextInput::make('contact_email')
                                        ->label(__('admin.settings.fields.contact_email'))
                                        ->placeholder('info@dietwatchers.com')
                                        ->email()
                                        ->required(),
                                    
                                    TextInput::make('contact_phone')
                                        ->label(__('admin.settings.fields.contact_phone'))
                                        ->placeholder('+966 XX XXX XXXX'),
                                    
                                    Textarea::make('contact_address')
                                        ->label(__('admin.settings.fields.contact_address'))
                                        ->placeholder('Riyadh, Saudi Arabia')
                                        ->rows(2),
                                ])->columns(2),
                        ]),

                    // Logo Settings Tab
                    Tab::make(__('admin.settings.tabs.logo'))
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Section::make(__('admin.settings.sections.logos'))
                                ->description(__('admin.settings.descriptions.logos'))
                                ->schema([
                                    FileUpload::make('logo_header')
                                        ->label(__('admin.settings.fields.logo_header'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('settings/logos')
                                        ->maxSize(2048)
                                        ->helperText(__('admin.settings.helpers.logo_header')),

                                    FileUpload::make('logo_footer')
                                        ->label(__('admin.settings.fields.logo_footer'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('settings/logos')
                                        ->maxSize(2048)
                                        ->helperText(__('admin.settings.helpers.logo_footer')),

                                    FileUpload::make('favicon')
                                        ->label(__('admin.settings.fields.favicon'))
                                        ->image()
                                        ->disk('public')
                                        ->directory('settings/logos')
                                        ->maxSize(1024)
                                        ->acceptedFileTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon'])
                                        ->helperText(__('admin.settings.helpers.favicon')),
                                ])->columns(1),
                        ]),

                    // Footer Settings Tab
                    Tab::make(__('admin.settings.tabs.footer'))
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make(__('admin.settings.sections.footer_content'))
                                ->schema([
                                    Textarea::make('footer_description_en')
                                        ->label(__('admin.settings.fields.footer_description_en'))
                                        ->placeholder('Healthy Meals Delivered Daily...')
                                        ->rows(3),
                                    
                                    Textarea::make('footer_description_ar')
                                        ->label(__('admin.settings.fields.footer_description_ar'))
                                        ->placeholder('وجبات صحية تصلك يومياً...')
                                        ->rows(3),
                                ]),
                            
                            Section::make(__('admin.settings.sections.copyright'))
                                ->schema([
                                    TextInput::make('copyright_en')
                                        ->label(__('admin.settings.fields.copyright_en'))
                                        ->placeholder('© 2026 Diet Watchers. All rights reserved.'),
                                    
                                    TextInput::make('copyright_ar')
                                        ->label(__('admin.settings.fields.copyright_ar'))
                                        ->placeholder('© 2026 دايت ووتشرز. جميع الحقوق محفوظة.'),
                                ])->columns(1),
                        ]),

                    // Social Links Tab
                    Tab::make(__('admin.settings.tabs.social'))
                        ->icon('heroicon-o-share')
                        ->schema([
                            Section::make(__('admin.settings.sections.social_links'))
                                ->description(__('admin.settings.descriptions.social_links'))
                                ->schema([
                                    TextInput::make('social_instagram')
                                        ->label(__('admin.settings.fields.social_instagram'))
                                        ->placeholder('https://instagram.com/dietwatchers')
                                        ->url(),
                                    
                                    TextInput::make('social_facebook')
                                        ->label(__('admin.settings.fields.social_facebook'))
                                        ->placeholder('https://facebook.com/dietwatchers')
                                        ->url(),
                                    
                                    TextInput::make('social_twitter')
                                        ->label(__('admin.settings.fields.social_twitter'))
                                        ->placeholder('https://twitter.com/dietwatchers')
                                        ->url(),
                                    
                                    TextInput::make('social_youtube')
                                        ->label(__('admin.settings.fields.social_youtube'))
                                        ->placeholder('https://youtube.com/dietwatchers')
                                        ->url(),
                                    
                                    TextInput::make('social_linkedin')
                                        ->label(__('admin.settings.fields.social_linkedin'))
                                        ->placeholder('https://linkedin.com/company/dietwatchers')
                                        ->url(),
                                ])->columns(2),
                        ]),

                    // App Links Tab
                    Tab::make(__('admin.settings.tabs.apps'))
                        ->icon('heroicon-o-device-phone-mobile')
                        ->schema([
                            Section::make(__('admin.settings.sections.app_links'))
                                ->description(__('admin.settings.descriptions.app_links'))
                                ->schema([
                                    TextInput::make('app_store_url')
                                        ->label(__('admin.settings.fields.app_store_url'))
                                        ->placeholder('https://apps.apple.com/...')
                                        ->url(),
                                    
                                    TextInput::make('play_store_url')
                                        ->label(__('admin.settings.fields.play_store_url'))
                                        ->placeholder('https://play.google.com/store/apps/...')
                                        ->url(),
                                ])->columns(1),
                        ]),

                    // Meal Plans Page Tab
                    Tab::make(__('admin.settings.tabs.meal_plans'))
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Section::make(__('admin.settings.sections.meal_plans_page'))
                                ->schema([
                                    TextInput::make('meal_plans_title_en')
                                        ->label(__('admin.settings.fields.meal_plans_title_en'))
                                        ->placeholder('Choose the Meal Plan That Fits Your Lifestyle'),
                                    
                                    TextInput::make('meal_plans_title_ar')
                                        ->label(__('admin.settings.fields.meal_plans_title_ar'))
                                        ->placeholder('اختر خطة الوجبات التي تناسب أسلوب حياتك'),
                                    
                                    Textarea::make('meal_plans_description_en')
                                        ->label(__('admin.settings.fields.meal_plans_description_en'))
                                        ->placeholder('All Diet Watchers plans are nutritionist-approved...')
                                        ->rows(3),
                                    
                                    Textarea::make('meal_plans_description_ar')
                                        ->label(__('admin.settings.fields.meal_plans_description_ar'))
                                        ->placeholder('جميع خطط Diet Watchers معتمدة من أخصائيي التغذية...')
                                        ->rows(3),
                                ]),
                        ]),

                    // Checkout Tab
                    Tab::make(__('Checkout'))
                        ->icon('heroicon-o-shopping-cart')
                        ->schema([
                            Section::make(__('Delivery Settings'))
                                ->schema([
                                    TextInput::make('delivery_fee')
                                        ->label(__('Delivery Fee (SAR)'))
                                        ->numeric()
                                        ->default(25)
                                        ->helperText(__('Delivery fee for individual meals. Meal plan subscriptions have free delivery.')),
                                ]),
                            Section::make(__('admin.settings.sections.tax_vat'))
                                ->schema([
                                    TextInput::make('vat_rate')
                                        ->label(__('admin.settings.fields.vat_rate'))
                                        ->numeric()
                                        ->suffix('%')
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->step(0.01)
                                        ->default(15)
                                        ->helperText(__('admin.settings.helpers.vat_rate')),
                                ]),
                        ]),

                    // SEO Tab
                    Tab::make(__('admin.settings.tabs.seo'))
                        ->icon('heroicon-o-magnifying-glass')
                        ->schema([
                            Section::make(__('admin.settings.sections.seo'))
                                ->schema([
                                    TextInput::make('meta_title_en')
                                        ->label(__('admin.settings.fields.meta_title_en'))
                                        ->placeholder('Diet Watchers - Healthy Meals Delivered Daily'),
                                    
                                    TextInput::make('meta_title_ar')
                                        ->label(__('admin.settings.fields.meta_title_ar'))
                                        ->placeholder('دايت ووتشرز - وجبات صحية تصلك يومياً'),
                                    
                                    Textarea::make('meta_description_en')
                                        ->label(__('admin.settings.fields.meta_description_en'))
                                        ->placeholder('Chef-made, calorie-smart meals delivered in Saudi Arabia...')
                                        ->rows(3),
                                    
                                    Textarea::make('meta_description_ar')
                                        ->label(__('admin.settings.fields.meta_description_ar'))
                                        ->placeholder('وجبات مصنوعة من الطهاة، ذات سعرات حرارية ذكية...')
                                        ->rows(3),
                                ]),
                        ]),
                ])
                ->persistTabInQueryString()
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSettings::route('/'),
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.settings.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.settings.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.settings.plural_model_label');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }
}
