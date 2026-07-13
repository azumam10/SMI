<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PerformanceReviews\Schemas;

use App\Models\Employee;
use App\Models\PerformanceReview;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;


class PerformanceReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = auth()->user();
        if (!$user) {
            return $schema;
            }
        $isHrdOrAdmin = $user->hasAnyRole(['hrd', 'super_admin']);
        $employee = Employee::where('user_id', $user->id)->first();

        return $schema
            ->components([

                Section::make('Data Penilaian')
                    ->columns(2)
                    ->schema([

                        // ─── Employee ──────────────────────────────
                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->relationship(
                                name: 'employee',
                                titleAttribute: 'name',
                                modifyQueryUsing: function ($query) use ($isHrdOrAdmin, $employee) {
                                    if ($isHrdOrAdmin) {
                                        return $query->orderBy('name');
                                    }
                                    // Supervisor: hanya bawahannya
                                    return $query
                                        ->where('supervisor_id', $employee?->id)
                                        ->orderBy('name');
                                }
                            )
                            ->getOptionLabelFromRecordUsing(
                                fn ($record) => "{$record->id_number} - {$record->name}"
                            )
                            ->searchable(['name', 'id_number'])
                            ->preload()
                            ->required()
                            ->placeholder('Pilih karyawan...')
                            ->unique(
                                table: 'performance_reviews',
                                column: 'employee_id',
                                ignoreRecord: true,
                                modifyRuleUsing: function ($rule, Get $get) {
                                    return $rule
                                        ->where('year', $get('year'))
                                        ->where('semester', $get('semester'));
                                }
                            )
                            ->helperText('Setiap karyawan hanya dapat dinilai 1x per semester.'),

                        // ─── Reviewer (user) ───────────────────────
                        Hidden::make('reviewer_id')
                            ->default(auth()->id())
                            ->dehydrated(),

                        // ─── Tahun ──────────────────────────────────
                        TextInput::make('year')
                            ->label('Tahun')
                            ->numeric()
                            ->default(now()->year)
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        // ─── Semester ──────────────────────────────
                        Select::make('semester')
                            ->label('Semester')
                            ->options([
                                1 => 'Semester 1',
                                2 => 'Semester 2',
                            ])
                            ->default(now()->month <= 6 ? 1 : 2)
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        // ─── 5 Indikator ──────────────────────────
                        TextInput::make('discipline_score')
                            ->label('Disiplin')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('/100')
                            ->helperText('Nilai 0 - 100')
                            ->required()
                            ->live(),

                        TextInput::make('quality_score')
                            ->label('Kualitas Kerja')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('/100')
                            ->helperText('Nilai 0 - 100')
                            ->required()
                            ->live(),

                        TextInput::make('teamwork_score')
                            ->label('Kerjasama')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('/100')
                            ->helperText('Nilai 0 - 100')
                            ->required()
                            ->live(),

                        TextInput::make('ethic_score')
                            ->label('Etika')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('/100')
                            ->helperText('Nilai 0 - 100')
                            ->required()
                            ->live(),

                        TextInput::make('initiative_score')
                            ->label('Inisiatif')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->step(0.01)
                            ->suffix('/100')
                            ->helperText('Nilai 0 - 100')
                            ->required()
                            ->live(),
                    ]),

                // ─── Preview ───────────────────────────────────────
                Section::make('Preview Nilai Akhir')
                    ->columns(1)
                    ->schema([
                        Placeholder::make('preview')
                            ->label('')
                            ->live()
                            ->content(function (Get $get): HtmlString {
                                $discipline = (float) ($get('discipline_score') ?? 0);
                                $quality    = (float) ($get('quality_score') ?? 0);
                                $teamwork   = (float) ($get('teamwork_score') ?? 0);
                                $ethic      = (float) ($get('ethic_score') ?? 0);
                                $initiative = (float) ($get('initiative_score') ?? 0);

                                $finalScore = PerformanceReview::calculateFinalScore([
                                    'discipline_score' => $discipline,
                                    'quality_score'    => $quality,
                                    'teamwork_score'   => $teamwork,
                                    'ethic_score'      => $ethic,
                                    'initiative_score' => $initiative,
                                ]);

                                $category = PerformanceReview::resolveCategory($finalScore);

                                $color = match ($category) {
                                    'Outstanding' => '#22c55e',
                                    'Excellent'   => '#3b82f6',
                                    'Good'        => '#eab308',
                                    'Fair'        => '#6b7280',
                                    default       => '#ef4444',
                                };


                                return new HtmlString("
                                    <div class='flex gap-6 p-4 rounded-xl bg-gray-50 border border-gray-200'>
                                        <div>
                                            <div class='text-sm text-gray-500'>Nilai Akhir</div>
                                            <div class='text-3xl font-bold' style='color: #0f172a;'>"
                                                . number_format($finalScore, 2) .
                                            "</div>
                                        </div>
                                        <div>
                                            <div class='text-sm text-gray-500'>Kategori</div>
                                            <div class='text-3xl font-bold' style='color: {$color};'>{$category}</div>
                                        </div>
                                    </div>
                                ");
                            }),
                    ]),

                // ─── Catatan ───────────────────────────────────────
                Section::make('Catatan')
                    ->columns(1)
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(5)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}