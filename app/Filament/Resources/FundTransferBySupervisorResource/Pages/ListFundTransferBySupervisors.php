<?php

namespace App\Filament\Resources\FundTransferBySupervisorResource\Pages;

use App\Filament\Resources\FundTransferBySupervisorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFundTransferBySupervisors extends ListRecords
{
    protected static string $resource = FundTransferBySupervisorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
