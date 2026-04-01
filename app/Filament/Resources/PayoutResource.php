<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayoutResource\Pages;
use App\Models\Campaign;
use App\Models\Payout;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use App\Models\LedgerEntry;

class PayoutResource extends Resource
{
    protected static ?string $model = Payout::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Laporan';

    protected static ?string $navigationLabel = 'Payouts';

    protected static ?int $navigationSort = 20;

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
                Forms\Components\Section::make('Sumber Dana')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('wallet_id')
                            ->label('Campaign (Wallet)')
                            ->options(fn () => Wallet::query()->where('owner_type', Campaign::class)->with('owner')->get()->mapWithKeys(function ($w) {
                                $owner = $w->owner;
                                $ownerLabel = $owner?->title ?? $owner?->name ?? (class_basename($w->owner_type) . ' #' . $w->owner_id);
                                return [$w->id => $ownerLabel];
                            }))
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('source_campaign_id')
                            ->label('Keterangan Campaign (Opsional)')
                            ->options(fn () => Campaign::query()->pluck('title', 'id'))
                            ->searchable()
                            ->preload()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('amount')
                            ->label('Total')
                            ->numeric()
                            ->readOnly()
                            ->default(0),
                        Forms\Components\TextInput::make('status')
                            ->default('pending')
                            ->readOnly(),
                    ]),

                Forms\Components\Section::make('Detail Penyaluran (Items)')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\TextInput::make('memo')->label('Keterangan')->maxLength(255),
                                Forms\Components\TextInput::make('amount')->label('Jumlah')->numeric()->required(),
                            ])
                            ->mutateDehydratedStateUsing(function ($state, callable $set) {
                                $total = collect($state)->sum(fn ($i) => (float)($i['amount'] ?? 0));
                                $set('amount', $total);
                                return $state;
                            })
                            ->defaultItems(1)
                            ->minItems(1)
                            ->reorderable(false)
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wallet.owner.title')->label('Wallet')
                    ->getStateUsing(function ($record) {
                        $o = optional($record->wallet)->owner;
                        return $o->title ?? $o->name ?? null;
                    }),
                Tables\Columns\TextColumn::make('amount')->formatStateUsing(fn ($state) => 'Rp ' . number_format((float)$state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('meta_json')
                    ->label('Sumber (Campaign)')
                    ->getStateUsing(function ($record) {
                        $meta = $record->meta_json ?? [];
                        if (!is_array($meta)) return null;
                        return $meta['source_campaign_title'] ?? null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\BadgeColumn::make('status')->colors(['gray'=>['pending','cancelled'],'warning'=>['processing'],'success'=>['completed'],'danger'=>['failed']]),
                Tables\Columns\TextColumn::make('creator.name')->label('Dibuat Oleh')->getStateUsing(fn ($record) => optional($record->creator)->name),
                Tables\Columns\TextColumn::make('requested_at')->dateTime()->label('Diminta'),
                Tables\Columns\TextColumn::make('processed_at')->dateTime()->label('Diproses')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Payout $record) => in_array($record->status, ['pending'])),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Payout $record) => $record->status === 'pending')
                    ->action(function (Payout $record) {
                        $record->status = 'processing';
                        $record->processed_by = Auth::id();
                        $record->save();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Payout $record) => in_array($record->status, ['pending','processing']))
                    ->action(function (Payout $record) {
                        $record->status = 'cancelled';
                        $record->processed_by = Auth::id();
                        $record->processed_at = now();
                        $record->save();
                    }),
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (Payout $record) => $record->status === 'processing')
                    ->action(function (Payout $record) {
                        DB::transaction(function () use ($record) {
                            $wallet = \App\Models\Wallet::lockForUpdate()->findOrFail($record->wallet_id);

                            $items = $record->items()->get();
                            $total = (float) $items->sum('amount');

                            // Ensure sufficient balance
                            if ((float) $wallet->balance < $total) {
                                Notification::make()->title('Saldo wallet tidak mencukupi')->danger()->send();
                                return;
                            }

                            $sourceCampaignTitle = $record->meta_json['source_campaign_title'] ?? null;
                            foreach ($items as $item) {
                                $newBalance = (float) $wallet->balance - (float) $item->amount;
                                $memo = trim(($item->memo ?: ''));
                                if ($sourceCampaignTitle) {
                                    $memo = ($memo ? $memo . ' — ' : '') . 'Sumber: ' . $sourceCampaignTitle;
                                }
                                LedgerEntry::create([
                                    'wallet_id' => $wallet->id,
                                    'type' => 'debit',
                                    'amount' => $item->amount,
                                    'source_type' => $record->getMorphClass(),
                                    'source_id' => $record->id,
                                    'memo' => $memo,
                                    'balance_after' => $newBalance,
                                    'created_at' => now(),
                                ]);
                                $wallet->balance = $newBalance;
                            }

                            $wallet->save();

                            $record->amount = $total;
                            $record->status = 'completed';
                            $record->processed_by = Auth::id();
                            $record->processed_at = now();
                            $record->save();
                        });
                    }),
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
            'index' => Pages\ListPayouts::route('/'),
            'create' => Pages\CreatePayout::route('/create'),
            'edit' => Pages\EditPayout::route('/{record}/edit'),
        ];
    }
}
