<?php

namespace App\Filament\Resources\CampaignResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;

class MediaRelationManager extends RelationManager
{
    protected static string $relationship = 'media';

    protected static ?string $title = 'Media';

    protected static ?string $recordTitleAttribute = 'path';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Upload Gambar')
                    ->schema([
                        Forms\Components\TextInput::make('type')
                            ->default('image')
                            ->readOnly()
                            ->dehydrated(),
                        Forms\Components\Select::make('platform')
                            ->label('Platform')
                            ->options([
                                'desktop' => 'Desktop',
                                'mobile' => 'Mobile',
                            ])
                            ->default('desktop')
                            ->native(false)
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('sort_order', $state === 'mobile' ? 1 : 2);
                            }),
                        Forms\Components\FileUpload::make('path')
                            ->label('Gambar')
                            ->image()
                            ->disk('s3')
                            ->directory('campaign-media')
                            ->visibility('private')
                            ->maxSize(5 * 1024) // ~5MB
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('1600')
                            ->imageResizeTargetHeight('1200')
                            ->required(),
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(fn (Get $get) => $get('platform') === 'mobile' ? 1 : 2)
                            ->helperText('Urutan tampil, kecil lebih dulu'),
                    ])
                    ->columns(2),
                Forms\Components\Textarea::make('meta_json')
                    ->label('Meta (JSON)')
                    ->rows(4)
                    ->rule('nullable')
                    ->rule('json')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('url')
                    ->label('Preview')
                    ->getStateUsing(fn ($record) => $record->url)
                    ->square(),
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->colors([
                        'info' => 'desktop',
                        'warning' => 'mobile',
                    ]),
                Tables\Columns\TextColumn::make('type')->badge()->color('info'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
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
