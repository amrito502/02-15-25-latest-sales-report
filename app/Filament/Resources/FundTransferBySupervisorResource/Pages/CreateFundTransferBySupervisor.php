<?php

namespace App\Filament\Resources\FundTransferBySupervisorResource\Pages;

use App\Filament\Resources\FundTransferBySupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateFundTransferBySupervisor extends CreateRecord
{
    protected static string $resource = FundTransferBySupervisorResource::class;

    protected function getFormActions(): array
    {
        return [];
    }
}
