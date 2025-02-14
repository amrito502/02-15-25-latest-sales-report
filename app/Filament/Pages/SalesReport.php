<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Livewire\WithPagination;
class SalesReport extends Page
{
    use WithPagination;
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationLabel = 'Sales Report';
    protected static bool $hasPageHeader = false;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.sales-report';

    public $filter = 'today';
    public $users;

    public function mount()
    {
        $this->applyFilter($this->filter);
        $this->users = User::with('card')->withCount([
            'cards as silver_cards' => function ($query) {
                $query->where('type', 'Silver')->where('status', 'Inactive');
            },
            'cards as gold_cards' => function ($query) {
                $query->where('type', 'Gold')->where('status', 'Inactive');
            },
            'cards as platinum_cards' => function ($query) {
                $query->where('type', 'Platinum')->where('status', 'Inactive');
            },
        ])->get();

    }

    public function applyFilter($filter)
    {
        $this->filter = $filter;

        switch ($filter) {
            case 'today':
                $this->users = User::whereDate('created_at', Carbon::today())->get();
                break;
            case 'this_week':
                $this->users = User::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();
                break;
            case 'this_month':
                $this->users = User::whereMonth('created_at', Carbon::now()->month)->get();
                break;
        }
    }

}

