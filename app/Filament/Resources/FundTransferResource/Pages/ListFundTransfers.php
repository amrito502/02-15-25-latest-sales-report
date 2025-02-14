<?php
namespace App\Filament\Resources\FundTransferResource\Pages;

use App\Models\Team;
use Filament\Actions;
use App\Models\TeamAdmin;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\FundTransferResource;

class ListFundTransfers extends ListRecords
{
    protected static string $resource = FundTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Send Money to Supervisor')->color('success')->icon('heroicon-o-paper-airplane'),
            Actions\Action::make('SendMoneytoAdmin')
                ->label('Send Money to Admin')
                ->color('info')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    Select::make('team_id')
                        ->label('Select Team')
                        ->options(function () {
                            // Get all teams
                            return Team::all()->pluck('name', 'id');
                        })
                        ->reactive()                                                     // Make this field reactive
                        ->afterStateUpdated(fn($state, $set) => $set('admin_id', null)), // Reset admin selection when team changes

                    Select::make('admin_id')
                        ->label('Select Admin')
                        ->options(function ($get) {
                            $teamId = $get('team_id');
                            // Fetch admins based on selected team and return as an array
                            return TeamAdmin::where('team_id', $teamId)
                                ->with('admin') // Ensure you're eager loading the admin relationship
                                ->get()
                                ->pluck('admin.name', 'id'); // Return an array of admin names and IDs
                        })
                        ->multiple()
                        ->searchable()
                        ->required()
                        ->preload()
                        ->reactive(),

                    TextInput::make('amount')
                        ->label('Enter Amount')
                        ->numeric()
                        ->required()
                        ->default(0),
                ])->visible(fn ($record): bool => Auth::user()->role === 'SuperAdmin'),
        ];
    }
}
