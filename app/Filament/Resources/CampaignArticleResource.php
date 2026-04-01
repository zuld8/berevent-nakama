<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CampaignArticleResource\Pages;
use App\Models\Campaign;
use App\Models\CampaignArticle;
use App\Models\Payout;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CampaignArticleResource extends Resource
{
    protected static ?string $model = CampaignArticle::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Laporan Penyaluran';

    protected static ?int $navigationSort = 5;

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
                Forms\Components\Section::make('Artikel Laporan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('campaign_id')
                            ->label('Campaign')
                            ->options(fn () => Campaign::query()->pluck('title', 'id'))
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('title')->label('Judul')->required()->maxLength(255),
                        Forms\Components\Select::make('payout_id')
                            ->label('Terkait Payout')
                            ->options(fn () => Payout::query()->with('wallet.owner')->get()->mapWithKeys(fn ($p) => [
                                $p->id => 'Payout #' . $p->id . ' - ' . (optional($p->wallet->owner)->title ?? 'Wallet') . ' - Rp ' . number_format((float)$p->amount, 2, ',', '.'),
                            ]))
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->hint('Opsional'),
                        Forms\Components\DateTimePicker::make('published_at')->label('Terbit')->seconds(false),
                        Forms\Components\FileUpload::make('cover_path')
                            ->label('Cover Image')
                            ->image()
                            ->disk('s3')
                            ->directory('articles/covers')
                            ->visibility('private')
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imagePreviewHeight('200')
                            ->columnSpanFull(),
                        Forms\Components\RichEditor::make('body_md')
                            ->label('Isi')
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('s3')
                            ->fileAttachmentsDirectory('articles')
                            ->fileAttachmentsVisibility('private')
                            ->toolbarButtons([
                                'bold', 'italic', 'strike', 'underline', 'link', 'blockquote', 'codeBlock', 'h2', 'h3', 'bulletList', 'orderedList', 'attachFiles', 'undo', 'redo'
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('campaign.title')->label('Campaign')->searchable(),
                Tables\Columns\TextColumn::make('payout_id')->label('Payout')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('published_at')->dateTime()->label('Terbit'),
                Tables\Columns\TextColumn::make('author_id')->label('Penulis')->getStateUsing(fn ($record) => optional($record->author)->name),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListCampaignArticles::route('/'),
            'create' => Pages\CreateCampaignArticle::route('/create'),
            'edit' => Pages\EditCampaignArticle::route('/{record}/edit'),
        ];
    }
}
