<?php

namespace App\Filament\Pages;

use App\Models\Donation;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class Donors extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Manajemen';
    protected static ?string $navigationLabel = 'Donatur';
    protected static ?int $navigationSort = 60;

    protected static string $view = 'filament.pages.donors';

    public function getTitle(): string
    {
        return 'Donatur';
    }

    protected function getTableQuery(): Builder
    {
        // Grouping preference: phone number first, then email, then name
        $identityExpr = "COALESCE(NULLIF(TRIM(donor_phone), ''), NULLIF(TRIM(donor_email), ''), NULLIF(TRIM(donor_name), ''))";
        $identity = DB::raw($identityExpr);

        return Donation::query()
            ->select([
                DB::raw('MIN(id) as id'),
                DB::raw("MAX(donor_name) as donor_name"),
                DB::raw("MAX(donor_email) as donor_email"),
                DB::raw("MAX(donor_phone) as donor_phone"),
                DB::raw("SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as total_amount"),
                DB::raw("SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as donation_count"),
                DB::raw("MAX(paid_at) as last_paid_at"),
                DB::raw($identityExpr . " as identity"),
            ])
            ->where(function ($w) {
                $w->whereNotNull('donor_email')
                  ->orWhereNotNull('donor_phone')
                  ->orWhereNotNull('donor_name');
            })
            ->groupBy(DB::raw($identityExpr));
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('donor_name')
                ->label('Nama')
                ->formatStateUsing(function ($state, $record) {
                    $name = $record->donor_name;
                    $email = $record->donor_email;
                    $phone = $record->donor_phone;
                    return $name ?: ($email ?: ($phone ?: '—'));
                })
                ->searchable(['donor_name', 'donor_email', 'donor_phone'])
                ->wrap(),
            Tables\Columns\TextColumn::make('donor_email')
                ->label('Email')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('donor_phone')
                ->label('Nomor HP')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('total_amount')
                ->label('Total Donasi')
                ->money('idr', true)
                ->sortable(),
            Tables\Columns\TextColumn::make('donation_count')
                ->label('Transaksi')
                ->sortable(),
            Tables\Columns\TextColumn::make('last_paid_at')
                ->label('Terakhir')
                ->dateTime()
                ->sortable(),
        ];
    }

    

    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('detail')
                ->label('Detail')
                ->icon('heroicon-o-eye')
                ->modalHeading('Detail Donatur')
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function ($record) {
                    $identity = (string) ($record->getAttribute('identity') ?? '');
                    // Use same identity expression (phone first) as the table query
                    $identityExpr = "COALESCE(NULLIF(TRIM(donor_phone), ''), NULLIF(TRIM(donor_email), ''), NULLIF(TRIM(donor_name), ''))";

                    $rows = Donation::query()
                        ->select([
                            'campaign_id',
                            DB::raw('COUNT(*) as trx'),
                            DB::raw("SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as total"),
                            DB::raw('MAX(paid_at) as last_paid_at'),
                        ])
                        ->whereRaw($identityExpr . ' = ?', [$identity])
                        ->groupBy('campaign_id')
                        ->with(['campaign:id,title,slug'])
                        ->orderByDesc(DB::raw('MAX(paid_at)'))
                        ->get();

                    return view('filament.pages.partials.donor-detail', [
                        'identity' => $identity,
                        'rows' => $rows,
                    ]);
                }),
        ];
    }

    protected function getTableEmptyStateHeading(): ?string
    {
        return 'Belum ada data donatur';
    }

    // Avoid default sorting by base table key (donations.id) which breaks with ONLY_FULL_GROUP_BY.
    // Sort by an aggregated field instead.
    protected function getDefaultTableSortColumn(): ?string
    {
        return 'last_paid_at';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'desc';
    }

    protected function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('export')
                ->label('Export CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Rebuild the same aggregate query used for the table
                    $identityExpr = "COALESCE(NULLIF(TRIM(donor_phone), ''), NULLIF(TRIM(donor_email), ''), NULLIF(TRIM(donor_name), ''))";

                    $rows = Donation::query()
                        ->select([
                            DB::raw("MAX(donor_name) as donor_name"),
                            DB::raw("MAX(donor_email) as donor_email"),
                            DB::raw("MAX(donor_phone) as donor_phone"),
                            DB::raw("SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) as total_amount"),
                            DB::raw("SUM(CASE WHEN status='paid' THEN 1 ELSE 0 END) as donation_count"),
                            DB::raw("MAX(paid_at) as last_paid_at"),
                            DB::raw($identityExpr . " as identity"),
                        ])
                        ->where(function ($w) {
                            $w->whereNotNull('donor_email')
                              ->orWhereNotNull('donor_phone')
                              ->orWhereNotNull('donor_name');
                        })
                        ->groupBy(DB::raw($identityExpr))
                        ->orderByDesc(DB::raw('MAX(paid_at)'))
                        ->get();

                    return response()->streamDownload(function () use ($rows) {
                        $out = fopen('php://output', 'w');
                        // Header
                        fputcsv($out, ['Identity', 'Nama', 'Email', 'Nomor HP', 'Total Donasi', 'Transaksi', 'Terakhir Bayar']);
                        foreach ($rows as $r) {
                            fputcsv($out, [
                                (string) ($r->identity ?? ''),
                                (string) ($r->donor_name ?? ''),
                                (string) ($r->donor_email ?? ''),
                                (string) ($r->donor_phone ?? ''),
                                (string) ($r->total_amount ?? '0'),
                                (string) ($r->donation_count ?? '0'),
                                optional($r->last_paid_at)->toDateTimeString() ?? '',
                            ]);
                        }
                        fclose($out);
                    }, 'donors.csv');
                }),
        ];
    }
}
