<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventResource\Pages;
use App\Models\Event;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ImageCompressor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Resources\EventResource\RelationManagers\AttendancesRelationManager;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Events';
    protected static ?string $navigationLabel = 'Event List';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Event')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('organization_id')
                            ->label('Organisasi')
                            ->relationship('organization', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->placeholder('Pilih organisasi')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(255),
                        Forms\Components\Select::make('category_id')->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('session_count')->label('Jumlah Sesi')->numeric()->default(1)->minValue(1),
                        Forms\Components\Select::make('mode')->label('Mode')
                            ->options(['online' => 'Online', 'offline' => 'Offline', 'both' => 'Keduanya'])
                            ->default('online'),
                        Forms\Components\Select::make('type')->label('Tipe Event')
                            ->options(['umum' => 'Umum', 'khusus' => 'Khusus'])
                            ->default('umum'),
                        Forms\Components\DateTimePicker::make('start_date')->label('Mulai')->seconds(false),
                        Forms\Components\DateTimePicker::make('end_date')->label('Selesai')->seconds(false)
                            ->after('start_date'),
                        Forms\Components\Select::make('price_type')->label('Tipe Harga')
                            ->options(['fixed' => 'Harga Tetap', 'donation' => 'Infak Terbaik / Dinamis'])
                            ->default('fixed')
                            ->live(),
                        Forms\Components\TextInput::make('price')->label('Harga (IDR)')->numeric()
                            ->visible(fn (callable $get) => $get('price_type') === 'fixed'),
                        Forms\Components\Select::make('status')->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Publish',
                                'completed' => 'Selesai',
                            ])->default('draft'),
                    ]),

                Forms\Components\Section::make('Cover & Deskripsi')
                    ->schema([
                        Forms\Components\FileUpload::make('cover_path')
                            ->label('Cover')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->directory('events')
                            ->disk(media_disk())
                            ->visibility('private')
                            ->maxSize(8192)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $get) {
                                $data = file_get_contents($file->getRealPath());
                                // compress to ~200KB, 1:1 square (e.g., 1024x1024)
                                $compressed = ImageCompressor::squareJpegUnder($data, 200 * 1024, 1024);
                                $path = 'events/' . Str::random(40) . '.jpg';
                                Storage::disk(media_disk())->put($path, $compressed, 'private');
                                return $path;
                            }),
                        Forms\Components\RichEditor::make('description')->label('Deskripsi')->columnSpanFull(),
                    ])->columnSpanFull(),

                Forms\Components\Section::make('Materi')
                    ->description('Tambah daftar materi/sesi untuk event ini')
                    ->schema([
                        Forms\Components\Repeater::make('materials')
                            ->relationship('materials')
                            ->columns(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Judul Materi')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),
                                Forms\Components\DateTimePicker::make('date_at')
                                    ->label('Tanggal')
                                    ->seconds(false)
                                    ->columnSpan(1),
                                Forms\Components\Select::make('mentor_id')
                                    ->label('Mentor')
                                    ->relationship('mentor', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->minItems(0)
                            ->cloneable()
                            ->reorderable()
                            ->addActionLabel('Tambah Materi')
                            ->columnSpanFull(),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori')->toggleable(),
                Tables\Columns\TextColumn::make('type')->label('Tipe')->badge(),
                Tables\Columns\TextColumn::make('mode')->label('Mode')->badge(),
                Tables\Columns\TextColumn::make('session_count')->label('Sesi')->sortable(),
                Tables\Columns\TextColumn::make('start_date')->label('Mulai')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('end_date')->label('Selesai')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('price')->label('Harga')
                    ->formatStateUsing(fn ($state, $record) => $record->price_type === 'fixed'
                        ? ('Rp ' . number_format((float) $state, 0, ',', '.'))
                        : 'Dinamis'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                        'warning' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Publish',
                    'completed' => 'Selesai',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            AttendancesRelationManager::class,
        ];
    }
}
