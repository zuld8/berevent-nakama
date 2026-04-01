<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NewsResource\Pages;
use App\Models\News;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class NewsResource extends Resource
{
    protected static ?string $model = News::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'CMS';
    protected static ?string $navigationLabel = 'Berita';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Konten Berita')->columns(2)->schema([
                Forms\Components\TextInput::make('title')->label('Judul')->required()->live(onBlur: true)
                    ->afterStateUpdated(function ($state, $set) { if ($state) $set('slug', Str::slug($state)); }),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('excerpt')->label('Ringkasan')->maxLength(255)->columnSpanFull(),
                Forms\Components\Select::make('author_id')
                    ->label('Penulis')
                    ->relationship('author', 'name')
                    ->default(fn () => Auth::id())
                    ->searchable()
                    ->preload()
                    ->native(false),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ])
                    ->default('draft')
                    ->native(false),
                Forms\Components\FileUpload::make('cover_path')->label('Cover')->image()->disk('s3')->directory('news/covers')->visibility('private')->imageEditor()->imagePreviewHeight('180'),
                Forms\Components\RichEditor::make('body_md')->label('Isi')->columnSpanFull()
                    ->fileAttachmentsDisk('s3')->fileAttachmentsDirectory('news')->fileAttachmentsVisibility('private')
                    ->toolbarButtons(['bold','italic','underline','strike','link','blockquote','h2','h3','bulletList','orderedList','attachFiles','undo','redo']),
                Forms\Components\DateTimePicker::make('published_at')->label('Terbit')->seconds(false),
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
            Tables\Columns\TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('published_at')->dateTime()->label('Terbit')->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->badge()
                ->colors([
                    'gray' => 'draft',
                    'success' => 'published',
                    'warning' => 'archived',
                ])
                ->label('Status')
                ->sortable(),
            Tables\Columns\TextColumn::make('author.name')->label('Penulis')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('published_at','desc')->actions([
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
            'index' => Pages\ListNews::route('/'),
            'create' => Pages\CreateNews::route('/create'),
            'edit' => Pages\EditNews::route('/{record}/edit'),
        ];
    }
}
