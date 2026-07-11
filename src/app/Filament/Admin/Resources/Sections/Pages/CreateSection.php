<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Sections\Pages;

use App\Filament\Admin\Resources\Sections\SectionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateSection extends CreateRecord
{
    protected static string $resource = SectionResource::class;
}
