<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EventMaterialResource\Pages;
use App\Models\EventMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EventMaterialResource extends Resource
{
    protected static ?string $model = EventMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Events';
    protected static ?string $navigationLabel = 'Materi';
    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool { return false; }
    public static function canCreate(): bool { return false; }
    public static function canDeleteAny(): bool { return false; }
    public static function canView($record): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Materi')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('event_id')
                            ->relationship('event', 'title')
                            ->label('Event')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false),
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Materi')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('date_at')
                            ->label('Tanggal')
                            ->seconds(false),
                        Forms\Components\Select::make('mentor_id')
                            ->relationship('mentor', 'name')
                            ->label('Mentor')
                            ->searchable()
                            ->preload()
                            ->native(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('event.title')->label('Event')->searchable(),
                Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date_at')->label('Tanggal')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('mentor.name')->label('Mentor')->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Diubah')->dateTime()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListEventMaterials::route('/'),
            'create' => Pages\CreateEventMaterial::route('/create'),
            'edit' => Pages\EditEventMaterial::route('/{record}/edit'),
        ];
    }
}
