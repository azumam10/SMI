<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PerformanceReviews\Pages;

use App\Filament\Admin\Resources\PerformanceReviews\PerformanceReviewResource;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPerformanceReview extends EditRecord
{
    protected static string $resource = PerformanceReviewResource::class;

    protected function authorizeAccess(): void
    {
        $canEdit = app(PerformanceReviewService::class)->canEdit(auth()->user(), $this->record);
        if (! $canEdit) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit penilaian ini.');
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Hitung ulang nilai akhir
        $data['final_score'] = PerformanceReview::calculateFinalScore($data);
        $data['category'] = PerformanceReview::resolveCategory($data['final_score']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var PerformanceReview $record */
        $payload = [
            'discipline_score' => $data['discipline_score'],
            'quality_score'    => $data['quality_score'],
            'teamwork_score'   => $data['teamwork_score'],
            'ethic_score'      => $data['ethic_score'],
            'initiative_score' => $data['initiative_score'],
            'final_score'      => $data['final_score'],
            'category'         => $data['category'],
            'notes'            => $data['notes'] ?? $record->notes,
        ];

        // Jika status sebelumnya 'revised', ubah ke 'submitted' agar HRD bisa approve ulang
        if ($record->status === PerformanceReview::STATUS_REVISED) {
            $payload['status'] = PerformanceReview::STATUS_SUBMITTED;
            $payload['approved_by'] = null;
            $payload['approved_at'] = null;
        }

        $record->update($payload);

        return $record;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}