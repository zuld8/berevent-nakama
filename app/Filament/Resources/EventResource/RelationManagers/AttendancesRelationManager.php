<?php

namespace App\Filament\Resources\EventResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';
    protected static ?string $title = 'Attendances';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket.code')->label('Ticket Code')->copyable()->sortable(),
                Tables\Columns\TextColumn::make('ticket.order.user.name')->label('User')->sortable(),
                Tables\Columns\TextColumn::make('material.title')->label('Session')->wrap(),
                Tables\Columns\TextColumn::make('checked_in_at')->label('Checked In')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Recorded')->dateTime()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('material_id')
                    ->label('Session')
                    ->options(fn () => \App\Models\EventMaterial::query()
                        ->where('event_id', $this->getOwnerRecord()->id)
                        ->orderBy('date_at')->orderBy('id')
                        ->pluck('title','id')->toArray()),
            ])
            ->headerActions([
                Tables\Actions\Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        $event = $this->getOwnerRecord();
                        $rows = $event->attendances()->with(['ticket.order.user','material'])->orderBy('checked_in_at')->get();
                        $callback = function () use ($rows, $event) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['Event', 'Ticket Code', 'User', 'Session', 'Checked In At']);
                            foreach ($rows as $a) {
                                fputcsv($out, [
                                    $event->title,
                                    $a->ticket?->code,
                                    $a->ticket?->order?->user?->name,
                                    $a->material?->title,
                                    optional($a->checked_in_at)->format('Y-m-d H:i:s'),
                                ]);
                            }
                            fclose($out);
                        };
                        return response()->streamDownload($callback, 'attendance-' . now()->format('Ymd-His') . '.csv', [
                            'Content-Type' => 'text/csv',
                        ]);
                    }),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}

