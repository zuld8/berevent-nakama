<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HeroResource\Pages;
use App\Models\Hero;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Hero as HeroModel;

class HeroResource extends Resource
{
    protected static ?string $model = HeroModel::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Events';

    protected static ?string $navigationLabel = 'Heros';

    protected static ?int $navigationSort = 5; // under Events

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Hero')
                    ->columns(2)
                    ->schema([
                        // Preview current image (uses signed URL when needed)
                        Forms\Components\View::make('filament.components.hero-image-preview')
                            ->viewData(function (Get $get, ?Hero $record) {
                                $state = $get('image_path');
                                $path = $record?->image_path
                                    ?? (is_string($state) ? $state : (is_array($state) ? ($state['path'] ?? ($state[0] ?? null)) : null));
                                // Prefer model accessor which already mimics CampaignMedia signature logic
                                $url = $record?->image_url;
                                if (! $url && $path) {
                                    if (is_string($path) && (str_starts_with($path, 'http://') || str_starts_with($path, 'https://'))) {
                                        $url = $path;
                                    } else {
                                        // Try public URL first
                                        try {
                                            if ($path && is_string($path) && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                                                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                                            }
                                        } catch (\Throwable) {
                                            // ignore
                                        }

                                        if (! $url) {
                                            try {
                                                $ttl = (int) (env('S3_SIGNED_URL_TTL', 300));
                                                if ($path && is_string($path)) {
                                                    $url = \Illuminate\Support\Facades\Storage::disk(media_disk())->temporaryUrl($path, now()->addSeconds($ttl));
                                                }
                                            } catch (\Throwable) {
                                                try {
                                                    if ($path && is_string($path)) {
                                                        $url = \Illuminate\Support\Facades\Storage::disk(media_disk())->url($path);
                                                    }
                                                } catch (\Throwable) {
                                                    $url = null;
                                                }
                                            }
                                        }
                                    }
                                }

                                return [
                                    'previewUrl' => $url,
                                ];
                            })
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Gambar')
                            ->image()
                            ->disk(media_disk())
                            ->directory('heroes')
                            ->visibility('private')
                            ->maxSize(5 * 1024)
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1024')
                            ->imageResizeTargetHeight('1024')
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('event_id')
                            ->label('Event')
                            ->relationship('event', 'title')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'active' => 'Aktif',
                            ])
                            ->native(false)
                            ->default('draft')
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->getStateUsing(fn (Hero $record) => $record->image_url)
                    ->label('Gambar')
                    ->circular(false)
                    ->square(),
                Tables\Columns\TextColumn::make('event.title')
                    ->label('Event')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Diubah')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHeros::route('/'),
            'create' => Pages\CreateHero::route('/create'),
            'edit' => Pages\EditHero::route('/{record}/edit'),
        ];
    }
}
