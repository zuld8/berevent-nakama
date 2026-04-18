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
                        Forms\Components\TextInput::make('price')
                            ->label(fn (callable $get) => $get('price_type') === 'donation'
                                ? 'Harga Coret / Referensi (IDR)'
                                : 'Harga (IDR)')
                            ->numeric()
                            ->placeholder(fn (callable $get) => $get('price_type') === 'donation' ? 'Contoh: 100000' : '0')
                            ->helperText(fn (callable $get) => $get('price_type') === 'donation'
                                ? 'Opsional. Ditampilkan sebagai harga coret di atas tulisan "Infaq Terbaik". Kosongkan jika tidak ingin tampilkan harga coret.'
                                : null)
                            ->visible(fn (callable $get) => in_array($get('price_type'), ['fixed', 'donation'])),
                        Forms\Components\TextInput::make('min_price')
                            ->label('Minimal Infak (IDR)')
                            ->numeric()
                            ->minValue(1000)
                            ->placeholder('Contoh: 10000')
                            ->helperText('Kosongkan = minimal default Rp 10.000. Isi angka untuk atur batas minimum infak.')
                            ->visible(fn (callable $get) => $get('price_type') === 'donation'),
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

                Forms\Components\Section::make('📱 Follow-Up WhatsApp')
                    ->description('Atur pesan follow-up WhatsApp per event.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Select::make('meta_json.followup_mode')
                            ->label('Mode Pengiriman')
                            ->options([
                                'text' => '📝 Teks Biasa (WA Service)',
                                'waba' => '📲 WABA Template (Replai.id)',
                            ])
                            ->default('text')
                            ->native(false)
                            ->live()
                            ->helperText('Pilih cara pengiriman: teks langsung atau template WABA yang sudah diapprove Meta.'),

                        Forms\Components\Placeholder::make('followup_vars_info')
                            ->label('📋 Placeholder yang tersedia')
                            ->content(new \Illuminate\Support\HtmlString(
                                '<div class="text-xs space-y-1 text-gray-500">' .
                                '<div class="grid grid-cols-2 gap-x-4 gap-y-0.5">' .
                                '<div><code class="text-amber-600">{name}</code> — Nama pembeli</div>' .
                                '<div><code class="text-amber-600">{email}</code> — Email pembeli</div>' .
                                '<div><code class="text-amber-600">{phone}</code> — No. HP pembeli</div>' .
                                '<div><code class="text-amber-600">{reference}</code> — Kode referensi order</div>' .
                                '<div><code class="text-amber-600">{total}</code> — Total bayar (formatted)</div>' .
                                '<div><code class="text-amber-600">{event_title}</code> — Judul event</div>' .
                                '<div><code class="text-amber-600">{pay_url}</code> — Link pembayaran</div>' .
                                '<div><code class="text-amber-600">{organization_name}</code> — Nama organisasi</div>' .
                                '</div></div>'
                            )),

                        // === TEXT MODE ===
                        Forms\Components\Tabs::make('followup_text_tabs')
                            ->visible(fn (callable $get) => ($get('meta_json.followup_mode') ?? 'text') === 'text')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('🟢 Welcome')
                                    ->schema([
                                        Forms\Components\Textarea::make('meta_json.followup_text.welcome')
                                            ->label('Pesan Welcome')
                                            ->rows(6)
                                            ->placeholder("Assalamu'alaikum {name},\n\nTerima kasih telah mendaftar di {event_title}.\nRef: {reference}\nTotal: {total}\n\nSilakan selesaikan pembayaran:\n{pay_url}")
                                            ->helperText('Placeholder: {name}, {reference}, {total}, {event_title}, {pay_url}'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('🟡 Follow Up 1')
                                    ->schema([
                                        Forms\Components\Textarea::make('meta_json.followup_text.fu1')
                                            ->label('Follow Up 1')->rows(6),
                                    ]),
                                Forms\Components\Tabs\Tab::make('🟡 Follow Up 2')
                                    ->schema([
                                        Forms\Components\Textarea::make('meta_json.followup_text.fu2')
                                            ->label('Follow Up 2')->rows(6),
                                    ]),
                                Forms\Components\Tabs\Tab::make('🟡 Follow Up 3')
                                    ->schema([
                                        Forms\Components\Textarea::make('meta_json.followup_text.fu3')
                                            ->label('Follow Up 3')->rows(6),
                                    ]),
                                Forms\Components\Tabs\Tab::make('✅ Paid')
                                    ->schema([
                                        Forms\Components\Textarea::make('meta_json.followup_text.paid')
                                            ->label('Konfirmasi Bayar')->rows(6),
                                    ]),
                            ])
                            ->columnSpanFull(),

                        // === WABA MODE ===
                        Forms\Components\Tabs::make('followup_waba_tabs')
                            ->visible(fn (callable $get) => ($get('meta_json.followup_mode') ?? 'text') === 'waba')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('🟢 Welcome')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_json.followup_waba.welcome_template_id')
                                            ->label('Template ID')
                                            ->placeholder('welcome_order_123')
                                            ->helperText('Template ID yang sudah diapprove di Meta Business Suite.'),
                                        Forms\Components\Textarea::make('meta_json.followup_waba.welcome_body')
                                            ->label('Body Parameters (1 per baris)')
                                            ->rows(4)
                                            ->placeholder("{name}\n{event_title}\n{total}\n{pay_url}")
                                            ->helperText('Setiap baris = 1 body parameter. Gunakan placeholder.'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('🟡 Follow Up 1')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_json.followup_waba.fu1_template_id')
                                            ->label('Template ID'),
                                        Forms\Components\Textarea::make('meta_json.followup_waba.fu1_body')
                                            ->label('Body Parameters (1 per baris)')->rows(4),
                                    ]),
                                Forms\Components\Tabs\Tab::make('🟡 Follow Up 2')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_json.followup_waba.fu2_template_id')
                                            ->label('Template ID'),
                                        Forms\Components\Textarea::make('meta_json.followup_waba.fu2_body')
                                            ->label('Body Parameters (1 per baris)')->rows(4),
                                    ]),
                                Forms\Components\Tabs\Tab::make('🟡 Follow Up 3')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_json.followup_waba.fu3_template_id')
                                            ->label('Template ID'),
                                        Forms\Components\Textarea::make('meta_json.followup_waba.fu3_body')
                                            ->label('Body Parameters (1 per baris)')->rows(4),
                                    ]),
                                Forms\Components\Tabs\Tab::make('✅ Paid')
                                    ->schema([
                                        Forms\Components\TextInput::make('meta_json.followup_waba.paid_template_id')
                                            ->label('Template ID'),
                                        Forms\Components\Textarea::make('meta_json.followup_waba.paid_body')
                                            ->label('Body Parameters (1 per baris)')->rows(4),
                                    ]),
                            ])
                            ->columnSpanFull(),
                    ])->columnSpanFull(),

                Forms\Components\Section::make('🎬 Rekaman Event')
                    ->description('Isi URL rekaman setelah event selesai. Peserta (pemilik tiket) langsung bisa nonton gratis selamanya.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('replay_url')
                            ->label('URL Rekaman (YouTube / Link Lain)')
                            ->placeholder('https://www.youtube.com/watch?v=xxxxx')
                            ->url()
                            ->columnSpanFull()
                            ->helperText('Tempel link YouTube unlisted. Player embed otomatis muncul di halaman event untuk user yang punya akses.'),

                        Forms\Components\TextInput::make('replay_price')
                            ->label('Harga Rekaman untuk Non-Peserta (IDR)')
                            ->numeric()
                            ->minValue(0)
                            ->placeholder('Contoh: 50000')
                            ->helperText('Kosongkan = hanya peserta bisa nonton. Isi 0 = gratis untuk semua. Isi angka = harga beli replay.'),
                    ])->columns(1)->columnSpanFull(),

                Forms\Components\Section::make('📎 Materi & File')
                    ->description('Upload file materi (PDF, PPT, Excel, dll). Hanya bisa didownload oleh peserta yang punya tiket atau sudah beli akses rekaman.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Repeater::make('resources')
                            ->relationship('resources')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('Nama File / Keterangan')
                                    ->placeholder('Contoh: Slide Sesi 1, Template Excel...')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Forms\Components\FileUpload::make('path')
                                    ->label('Upload File')
                                    ->required()
                                    ->disk('private')
                                    ->directory('event-resources')
                                    ->visibility('private')
                                    ->maxSize(51200) // 50MB
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/vnd.ms-powerpoint',
                                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/zip',
                                        'image/jpeg',
                                        'image/png',
                                    ])
                                    ->saveUploadedFileUsing(function ($file, $get) {
                                        $ext      = $file->getClientOriginalExtension();
                                        $safeName = \Illuminate\Support\Str::random(40) . '.' . $ext;
                                        $path     = 'event-resources/' . $safeName;
                                        \Illuminate\Support\Facades\Storage::disk('private')->put($path, file_get_contents($file->getRealPath()));
                                        return $path;
                                    })
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('original_name')
                                    ->afterStateHydrated(fn ($state) => $state)
                                    ->dehydrateStateUsing(fn ($state, $get) => $state),

                                Forms\Components\Hidden::make('disk')
                                    ->default('private'),

                                Forms\Components\Hidden::make('sort')
                                    ->default(0),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->reorderable('sort')
                            ->addActionLabel('+ Tambah File Materi')
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
