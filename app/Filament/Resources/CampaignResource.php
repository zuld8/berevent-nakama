<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignResource\Pages;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\Category;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CampaignResource extends Resource
{
    protected static ?string $model = Campaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Donasi';

    protected static ?string $navigationLabel = 'Campaigns';

    protected static ?int $navigationSort = 20; // setelah Categories



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Cover Campaign')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_path')
                            ->label('Banner / Cover')
                            ->image()
                            ->disk(media_disk())
                            ->directory('campaigns/covers')
                            ->visibility('public')
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imagePreviewHeight('250')
                            ->columnSpanFull()
                            ->helperText('Rekomendasi: 1200x630px (rasio 16:9)'),
                    ]),

                Forms\Components\Section::make('Informasi Utama')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Hidden::make('organization_id')
                            ->default(fn () => Organization::firstOrCreate(
                                ['id' => 1],
                                ['name' => 'Nakama Project Hub', 'slug' => 'nakama-project-hub']
                            )->id),

                        Forms\Components\TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (blank($state)) return;
                                $set('slug', Str::slug($state));
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->rule('alpha_dash')
                            ->unique(Campaign::class, 'slug', ignoreRecord: true),

                        Forms\Components\Textarea::make('summary')
                            ->label('Ringkasan')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\MarkdownEditor::make('description_md')
                            ->label('Deskripsi (Markdown)')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('categories')
                            ->label('Kategori')
                            ->multiple()
                            ->relationship('categories', 'name')
                            ->preload()
                            ->searchable()
                            ->helperText('Pilih satu atau lebih kategori'),
                    ]),

                Forms\Components\Section::make('Target & Periode')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('target_amount')
                            ->label('Target Donasi')
                            ->numeric()
                            ->rule('decimal:0,2')
                            ->default(0),
                        Forms\Components\TextInput::make('raised_amount')
                            ->label('Terkumpul')
                            ->numeric()
                            ->rule('decimal:0,2')
                            ->default(0)
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Aktif',
                                'paused' => 'Pause',
                                'ended' => 'Selesai',
                            ])
                            ->native(false)
                            ->default('draft'),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Mulai'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('Selesai')
                            ->after('start_date'),
                    ]),

                Forms\Components\Section::make('Pengaturan Tambahan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Textarea::make('settings_json')
                            ->label('Settings (JSON)')
                            ->rows(6)
                            ->rule('nullable')
                            ->rule('json'),
                        Forms\Components\Textarea::make('location_json')
                            ->label('Lokasi (JSON)')
                            ->rows(6)
                            ->rule('nullable')
                            ->rule('json'),
                    ]),

                Forms\Components\Section::make('Website Meta')
                    ->description('Pengaturan meta untuk SEO dan share (Open Graph/Twitter)')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->label('Meta Title')
                            ->maxLength(255)
                            ->helperText('Opsional. Default: judul campaign.'),
                        Forms\Components\TextInput::make('meta_image_url')
                            ->label('Meta Image URL')
                            ->maxLength(255)
                            ->helperText('URL gambar untuk preview share. Opsional.'),
                        Forms\Components\Textarea::make('meta_description')
                            ->label('Meta Description')
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText('Opsional. Default: ringkasan campaign.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                        'warning' => 'paused',
                        'danger' => 'ended',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('target_amount')
                    ->label('Target')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('raised_amount')
                    ->label('Terkumpul')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((float) $state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Diubah')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'paused' => 'Pause',
                        'ended' => 'Selesai',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CampaignResource\RelationManagers\MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampaigns::route('/'),
            'create' => Pages\CreateCampaign::route('/create'),
            'edit' => Pages\EditCampaign::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
