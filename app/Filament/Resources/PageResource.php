<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Models\Page as StaticPage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = StaticPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?string $navigationLabel = 'Pages';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Halaman')->columns(2)->schema([
                Forms\Components\TextInput::make('title')->required()->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set) { if ($state) $set('slug', Str::slug($state)); }),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\RichEditor::make('body_md')->label('Isi')->columnSpanFull()
                    ->fileAttachmentsDisk('s3')->fileAttachmentsDirectory('pages')->fileAttachmentsVisibility('private')
                    ->toolbarButtons(['bold','italic','underline','strike','link','blockquote','h2','h3','bulletList','orderedList','attachFiles','undo','redo']),
                Forms\Components\DateTimePicker::make('published_at')->label('Terbit')->seconds(false),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->native(false),
            ]),
            Forms\Components\Section::make('SEO')->columns(2)->schema([
                Forms\Components\TextInput::make('meta_title')->maxLength(255),
                Forms\Components\TextInput::make('meta_image_url')->maxLength(255),
                Forms\Components\Textarea::make('meta_description')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('title')->label('Judul')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('slug')->searchable(),
            Tables\Columns\TextColumn::make('published_at')->dateTime()->label('Terbit'),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'gray' => 'draft',
                    'success' => 'published',
                    'warning' => 'archived',
                ])
                ->label('Status')
                ->sortable(),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('updated_at','desc')->actions([
            Tables\Actions\EditAction::make(),
        ])->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
