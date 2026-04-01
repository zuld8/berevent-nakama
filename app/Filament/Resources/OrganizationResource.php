<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrganizationResource\Pages;
use App\Models\Organization;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrganizationResource extends Resource
{
    protected static ?string $model = Organization::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'Organisasi';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Profil Organisasi')->schema([
                Forms\Components\FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->disk(media_disk())
                    ->directory('organization')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->maxLength(50),
                Forms\Components\Textarea::make('summary')
                    ->label('Ringkasan')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('commitment')
                    ->label('Komitmen')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->rows(2)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_verified')
                    ->label('Terverifikasi'),
            ])->columns(2),

            Forms\Components\Section::make('Sosial Media')->schema([
                Forms\Components\TextInput::make('social_json.website')
                    ->label('Website')
                    ->url(),
                Forms\Components\TextInput::make('social_json.instagram')
                    ->label('Instagram'),
                Forms\Components\TextInput::make('social_json.facebook')
                    ->label('Facebook'),
                Forms\Components\TextInput::make('social_json.youtube')
                    ->label('YouTube'),
                Forms\Components\TextInput::make('social_json.tiktok')
                    ->label('TikTok'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk(media_disk())
                    ->circular()
                    ->size(40),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
        ];
    }
}
