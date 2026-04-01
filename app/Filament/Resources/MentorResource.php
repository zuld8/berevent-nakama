<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MentorResource\Pages;
use App\Models\Mentor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\ImageCompressor;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MentorResource extends Resource
{
    protected static ?string $model = Mentor::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationGroup = 'Events';
    protected static ?string $navigationLabel = 'Mentor';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Mentor')
                    ->columns(2)
                    ->schema([
                        Forms\Components\FileUpload::make('photo_path')
                            ->label('Foto')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth('512')
                            ->imageResizeTargetHeight('512')
                            // Allow larger uploads; we compress on server to <100KB
                            ->maxSize(4096)
                            ->directory('mentors')
                            ->disk(media_disk())
                            ->visibility('private')
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, $get) {
                                $contents = file_get_contents($file->getRealPath());
                                $compressed = ImageCompressor::squareJpegUnder($contents, 100 * 1024, 512);
                                $path = 'mentors/' . Str::random(40) . '.jpg';
                                Storage::disk(media_disk())->put($path, $compressed, 'private');
                                return $path;
                            })
                            ->columnSpan(1),
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('profession')
                            ->label('Profesi')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo_path')
                    ->label('Foto')
                    ->getStateUsing(fn (Mentor $record) => $record->photo_url)
                    ->circular(),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('profession')->label('Profesi')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')->label('Dihapus')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
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
            'index' => Pages\ListMentors::route('/'),
            'create' => Pages\CreateMentor::route('/create'),
            'edit' => Pages\EditMentor::route('/{record}/edit'),
        ];
    }
}
