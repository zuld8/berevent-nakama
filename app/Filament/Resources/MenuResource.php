<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers\ItemsRelationManager;
use App\Models\Menu;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?string $navigationLabel = 'Menu';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Menu')
                ->schema([
                    Forms\Components\TextInput::make('name')->label('Nama')->required()->maxLength(255),
                    Forms\Components\TextInput::make('code')->label('Kode')->required()->unique(ignoreRecord: true)->helperText('Kode unik, misal: main, footer, header'),
                ]),

            Forms\Components\Section::make('Item Menu')
                ->description('Kelola item menu, pilih tipe (Halaman/Berita/Campaign/Custom) dan atur sub menu dengan children.')
                ->collapsible()
                ->schema([
                    Forms\Components\Repeater::make('items')
                        ->relationship('topItems')
                        ->orderable('sort_order')
                        ->columns(2)
                        ->collapsed()
                        ->live()
                        ->itemLabel(fn ($state) => ($state['title'] ?? 'Item'))
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('Judul')->required()->columnSpanFull(),
                            Forms\Components\Select::make('parent_id')
                                ->label('Parent (jadikan Sub Menu)')
                                ->relationship('parent', 'title')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->nullable()
                                ->helperText('Pilih induk jika item ini harus menjadi child dari item lain.'),
                            Forms\Components\Select::make('type')
                                ->label('Tipe')
                                ->options([
                                    'custom' => 'Tautan Kustom',
                                    'page' => 'Halaman',
                                    'news' => 'Berita',
                                    'campaign' => 'Campaign',
                                ])
                                ->native(false)
                                ->reactive()->live()
                                ->default('custom')
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if ($state === 'custom') {
                                        $set('page_id', null);
                                        $set('news_id', null);
                                        $set('campaign_id', null);
                                    } elseif ($state === 'page') {
                                        $set('url', null);
                                        $set('news_id', null);
                                        $set('campaign_id', null);
                                    } elseif ($state === 'news') {
                                        $set('url', null);
                                        $set('page_id', null);
                                        $set('campaign_id', null);
                                    } elseif ($state === 'campaign') {
                                        $set('url', null);
                                        $set('page_id', null);
                                        $set('news_id', null);
                                    }
                                }),
                            Forms\Components\TextInput::make('url')
                                ->label('URL')
                                ->helperText('Diisi jika memilih Tautan Kustom')
                                ->required(fn (callable $get) => ($get('type') ?? 'custom') === 'custom')
                                ->visible(fn (callable $get) => ($get('type') ?? 'custom') === 'custom')
                                ->columnSpanFull(),
                            Forms\Components\Select::make('page_id')
                                ->label('Page')
                                ->relationship('page', 'title')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->nullable()
                                ->required(fn (callable $get) => $get('type') === 'page')
                                ->visible(fn (callable $get) => $get('type') === 'page')
                                ->helperText('Memilih Page akan otomatis menggunakan slug page.'),
                            Forms\Components\Select::make('news_id')
                                ->label('Berita')
                                ->relationship('news', 'title')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->nullable()
                                ->required(fn (callable $get) => $get('type') === 'news')
                                ->visible(fn (callable $get) => $get('type') === 'news')
                                ->helperText('Memilih Berita akan otomatis menggunakan slug berita.'),
                            Forms\Components\Select::make('campaign_id')
                                ->label('Campaign')
                                ->relationship('campaign', 'title')
                                ->searchable()
                                ->preload()
                                ->native(false)
                                ->nullable()
                                ->required(fn (callable $get) => $get('type') === 'campaign')
                                ->visible(fn (callable $get) => $get('type') === 'campaign')
                                ->helperText('Memilih Campaign akan otomatis menggunakan slug campaign.'),
                            Forms\Components\TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),

                            Forms\Components\Toggle::make('show_children')
                                ->label('Tambah Sub Menu?')
                                ->reactive()
                                ->live()
                                ->dehydrated(false)
                                ->inline(false)
                                ->columnSpanFull(),

                            Forms\Components\Section::make('Sub Menu')
                                ->collapsible()
                                ->collapsed()
                                ->columnSpanFull()
                                ->visible(fn (callable $get) => (bool) $get('show_children') === true)
                                ->schema([
                                    Forms\Components\Repeater::make('children')
                                        ->relationship('children')
                                        ->orderable('sort_order')
                                        ->columns(2)
                                        ->collapsed()
                                        ->live()
                                        ->defaultItems(0)
                                        ->itemLabel(fn ($state) => ($state['title'] ?? 'Sub Item'))
                                        ->schema([
                                            Forms\Components\TextInput::make('title')->label('Judul')->required()->columnSpanFull(),
                                            Forms\Components\Select::make('type')
                                                ->label('Tipe')
                                                ->options([
                                                    'custom' => 'Tautan Kustom',
                                                    'page' => 'Halaman',
                                                    'news' => 'Berita',
                                                    'campaign' => 'Campaign',
                                                ])
                                                ->native(false)
                                                ->reactive()->live()
                                                ->default('custom')
                                                ->afterStateUpdated(function ($state, callable $set) {
                                                    if ($state === 'custom') {
                                                        $set('page_id', null);
                                                        $set('news_id', null);
                                                        $set('campaign_id', null);
                                                    } elseif ($state === 'page') {
                                                        $set('url', null);
                                                        $set('news_id', null);
                                                        $set('campaign_id', null);
                                                    } elseif ($state === 'news') {
                                                        $set('url', null);
                                                        $set('page_id', null);
                                                        $set('campaign_id', null);
                                                    } elseif ($state === 'campaign') {
                                                        $set('url', null);
                                                        $set('page_id', null);
                                                        $set('news_id', null);
                                                    }
                                                }),
                                            Forms\Components\TextInput::make('url')
                                                ->label('URL')
                                                ->helperText('Diisi jika memilih Tautan Kustom')
                                                ->required(fn (callable $get) => ($get('type') ?? 'custom') === 'custom')
                                                ->visible(fn (callable $get) => ($get('type') ?? 'custom') === 'custom')
                                                ->columnSpanFull(),
                                            Forms\Components\Select::make('page_id')
                                                ->label('Page')
                                                ->relationship('page', 'title')
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->nullable()
                                                ->required(fn (callable $get) => $get('type') === 'page')
                                                ->visible(fn (callable $get) => $get('type') === 'page')
                                                ->helperText('Memilih Page akan otomatis menggunakan slug page.'),
                                            Forms\Components\Select::make('news_id')
                                                ->label('Berita')
                                                ->relationship('news', 'title')
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->nullable()
                                                ->required(fn (callable $get) => $get('type') === 'news')
                                                ->visible(fn (callable $get) => $get('type') === 'news')
                                                ->helperText('Memilih Berita akan otomatis menggunakan slug berita.'),
                                            Forms\Components\Select::make('campaign_id')
                                                ->label('Campaign')
                                                ->relationship('campaign', 'title')
                                                ->searchable()
                                                ->preload()
                                                ->native(false)
                                                ->nullable()
                                                ->required(fn (callable $get) => $get('type') === 'campaign')
                                                ->visible(fn (callable $get) => $get('type') === 'campaign')
                                                ->helperText('Memilih Campaign akan otomatis menggunakan slug campaign.'),
                                            Forms\Components\TextInput::make('sort_order')->label('Urutan')->numeric()->default(0),
                                            Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
                                        ]),
                                ]),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
            Tables\Columns\TextColumn::make('code')->searchable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
