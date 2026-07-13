<?php

namespace App\Filament\Admin\Resources\PerformanceReviews;

use App\Filament\Admin\Resources\PerformanceReviews\Pages\CreatePerformanceReview;
use App\Filament\Admin\Resources\PerformanceReviews\Pages\EditPerformanceReview;
use App\Filament\Admin\Resources\PerformanceReviews\Pages\ListPerformanceReviews;
use App\Filament\Admin\Resources\PerformanceReviews\Schemas\PerformanceReviewForm;
use App\Filament\Admin\Resources\PerformanceReviews\Tables\PerformanceReviewsTable;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;


class PerformanceReviewResource extends Resource
{
    protected static ?string $model = PerformanceReview::class;
    
    protected static ?string $navigationLabel = 'Penilaian Kinerja';
    
    protected static ?string $modelLabel = 'Penilaian Kinerja';
    
    protected static ?string $pluralModelLabel = 'Penilaian Kinerja';
    
    protected static string|UnitEnum|null $navigationGroup = 'Management HR';
    
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;
    
    protected static ?int $navigationSort = 4;

    // ─── Form ─────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return PerformanceReviewForm::configure($schema);
    }

    // ─── Table ────────────────────────────────────────

    public static function table(Table $table): Table
    {
        return PerformanceReviewsTable::configure($table)
            ->actions([
                Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) =>
                        auth()->user()->hasAnyRole(['hrd', 'super_admin'])
                        && $record->status === PerformanceReview::STATUS_SUBMITTED
                    )
                    ->action(function ($record) {
                        app(PerformanceReviewService::class)->approve($record);
                        Notification::make()
                            ->title('Penilaian disetujui')
                            ->success()
                            ->send();
                    }),

                Action::make('revise')
                    ->label('Kembalikan / Revisi')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn ($record) =>
                        auth()->user()->hasAnyRole(['hrd', 'super_admin'])
                        && in_array($record->status, [PerformanceReview::STATUS_SUBMITTED, PerformanceReview::STATUS_APPROVED])
                    )
                    ->requiresConfirmation()
                    ->modalDescription('Anda akan mengembalikan penilaian ini ke status revisi. Supervisor dapat mengedit ulang.')
                    ->action(function ($record) {
                        app(PerformanceReviewService::class)->revise($record);
                        Notification::make()
                            ->title('Penilaian dikembalikan ke revisi')
                            ->warning()
                            ->send();
                    }),

                EditAction::make()
                    ->visible(fn ($record) =>
                        app(PerformanceReviewService::class)->canEdit(auth()->user(), $record)
                    ),

                DeleteAction::make()
                    ->visible(fn ($record) =>
                        app(PerformanceReviewService::class)->canDelete(auth()->user(), $record)
                    ),
            ]);
    }

    // ─── Permissions ──────────────────────────────────

    public static function canViewAny(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd', 'kepala_bagian']);
    }

    public static function canCreate(): bool
    {
        return auth()->user()->hasAnyRole(['super_admin', 'hrd', 'kepala_bagian']);
    }

    // Perbaiki type hint: gunakan Model dari Illuminate, atau hapus type hint
    public static function canEdit($record): bool
    {
        return app(PerformanceReviewService::class)->canEdit(auth()->user(), $record);
    }

    public static function canDelete($record): bool
    {
        return app(PerformanceReviewService::class)->canDelete(auth()->user(), $record);
    }

    // ─── Pages ────────────────────────────────────────

    public static function getPages(): array
    {
        return [
            'index'  => ListPerformanceReviews::route('/'),
            'create' => CreatePerformanceReview::route('/create'),
            'edit'   => EditPerformanceReview::route('/{record}/edit'),
        ];
    }
}