<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PerformanceReviews\Pages;

use App\Filament\Admin\Resources\PerformanceReviews\PerformanceReviewResource;
use App\Models\PerformanceReview;
use App\Services\PerformanceReviewService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePerformanceReview extends CreateRecord
{
    protected static string $resource = PerformanceReviewResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(PerformanceReviewService::class)->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}