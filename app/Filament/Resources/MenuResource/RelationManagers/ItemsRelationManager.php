<?php

namespace App\Filament\Resources\MenuResource\RelationManagers;

use App\Models\MenuItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')->required(),
                Forms\Components\Select::make('type')
                    ->label('Tipe Menu')
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
                    })
                    ->afterStateHydrated(function (\Filament\Forms\Components\Select $component, $state, $record) {
                        if ($state) return; // already set
                        if (!$record) return;
                        $type = null;
                        if (!empty($record->page_id)) $type = 'page';
                        elseif (!empty($record->news_id)) $type = 'news';
                        elseif (!empty($record->campaign_id)) $type = 'campaign';
                        elseif (!empty($record->url)) $type = 'custom';
                        if ($type) $component->state($type);
                    })
                    ->helperText('Pilih sumber link: Halaman, Berita, Campaign, atau URL kustom.')
                    ->nullable(),
                Forms\Components\Select::make('parent_id')
                    ->label('Parent')
                    ->options(fn () => MenuItem::query()->where('menu_id', $this->ownerRecord->id)->pluck('title', 'id'))
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->nullable(),
                Forms\Components\TextInput::make('url')
                    ->label('URL')
                    ->helperText('Diisi jika memilih Tautan Kustom')
                    ->required(fn (callable $get) => ($get('type') ?? 'custom') === 'custom')
                    ->visible(fn (callable $get) => ($get('type') ?? 'custom') === 'custom'),
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
                Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
                Forms\Components\Toggle::make('active')->label('Aktif')->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable(),
                Tables\Columns\TextColumn::make('parent.title')->label('Parent')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')->label('Tipe')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('url')->label('URL')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('page.title')->label('Page')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('news.title')->label('Berita')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('campaign.title')->label('Campaign')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('active')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Urutan')->sortable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
