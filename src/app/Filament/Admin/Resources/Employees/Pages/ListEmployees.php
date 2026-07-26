<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Employees\Pages;

use App\Filament\Admin\Resources\Employees\EmployeeResource;
use App\Jobs\ImportEmployeesJob;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

final class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),

            Action::make('downloadTemplate')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(route('employee.template'))
                ->openUrlInNewTab()
                ->visible(fn () => auth()->user()->hasRole('super_admin'))
                ->authorize(fn () => auth()->user()->hasRole('super_admin')), // ← baris baru

            Action::make('importEmployee')
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => auth()->user()->hasRole('super_admin'))
                ->authorize(fn () => auth()->user()->hasRole('super_admin')) // ← baris baru
                ->form([
                    FileUpload::make('file')
                        ->label('File Excel (.xlsx)')
                        ->required()
                        ->disk('local')
                        ->directory('imports/employees')
                        ->visibility('private')
                        ->maxSize(10240)
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ]),
                ])
                ->action(function (array $data) {
                    ImportEmployeesJob::dispatch($data['file'], auth()->id());

                    Notification::make()
                        ->title('Import sedang diproses')
                        ->body('Kamu akan dapat notifikasi begitu selesai — cek lonceng notifikasi di pojok kanan atas.')
                        ->info()
                        ->send();
                }),
        ];
    }
}