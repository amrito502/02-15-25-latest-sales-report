<?php
namespace App\Filament\Resources;

use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BalanceRequest;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BalanceRequestResource\Pages;

class BalanceRequestResource extends Resource
{
    protected static ?string $model = BalanceRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Balance Request';

    protected static ?string $navigationGroup = 'Funds Administration';

    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return Auth::user()->role === 'Admin' || Auth::user()->role === 'SuperVisor'; // Only Admins can create
    }

    public static function canAccess(): bool
    {
        return Auth::user()->role === 'SuperVisor' || Auth::user()->role === 'SuperAdmin' || Auth::user()->role === 'Admin';
    }

    public static function canView($record): bool
    {
        return Auth::user()->role === 'SuperAdmin'; // Only Superadmin can edit
    }
    public static function canEdit($record): bool
    {
        return Auth::user()->role === 'SuperAdmin'; // Only Superadmin can edit
    }

    public static function canDelete($record): bool
    {
        return Auth::user()->role === 'SuperAdmin'; // Only Superadmin can delete
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Admin Withdrawal Request.')
                            ->description('Instead of requesting a balance, admins can request a withdrawal from the available pool.')
                            ->schema([
                                Select::make('approved_by')
                                    ->label('Select Super Admin')
                                    ->relationship('approvedBy', 'name', function ($query) {
                                        return $query->where('role', 'SuperAdmin');
                                    })
                                    ->required(),
                                TextInput::make('amount')
                                    ->label('Request Amount')
                                    ->numeric()
                                    ->required(),
                                Hidden::make('admin_id')->default(auth()->id()),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(BalanceRequest::query()->when(
                Auth::user()->role === 'Admin' || Auth::user()->role === 'SuperVisor',
                fn($q) => $q->where('admin_id', Auth::id())
            ))
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('admin.name')->label('Requested By')->sortable(),

                TextColumn::make('amount')->label('Amount')->money('BDT')->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger'  => 'rejected',
                    ])
                    ->sortable(),

                TextColumn::make('created_at')->label('Requested At')->dateTime()->sortable(),
            ])
            ->filters([
                // 🔎 Search by Admin Phone (Shows Admin Name & ID)
                Filter::make('phone')
                    ->form([
                        Select::make('phone')
                            ->label('Search by Phone')
                            ->placeholder('Select admin...')
                            ->preload()                                                // ✅ Preload admin data
                            ->searchable()                                             // ✅ Allow searching by name/phone
                            ->options(fn() => \App\Models\User::where('role', 'Admin') // Filter only admins
                                    ->whereNotNull('phone')
                                    ->get()
                                    ->mapWithKeys(fn($admin) => [
                                        $admin->phone => "{$admin->name} ({$admin->phone}) - ID: {$admin->id}",
                                    ])
                                    ->toArray()
                            ),
                    ])
                    ->query(
                        fn(Builder $query, $data) =>
                        $query->when($data['phone'] ?? null, function ($q, $value) {
                            return $q->whereHas('admin', fn($q) => $q->where('phone', 'like', "%$value%"));
                        })
                    ),

                // 📌 Status Filter (Pending, Approved, Rejected)
                Filter::make('status')
                    ->form([
                        Select::make('status')
                            ->label('Select Status')
                            ->options([
                                'pending'  => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                            ])
                            ->default('pending'),
                    ])
                    ->query(fn(Builder $query, $data) => $query->when($data['status'] ?? null, fn($q, $value) => $q->where('status', $value))),

                // 📅 Requested At Date Filter
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_at')
                            ->label('Requested At')
                            ->placeholder('Select Date...'),
                    ])
                    ->query(fn(Builder $query, $data) => $query->when($data['created_at'] ?? null, fn($q, $value) => $q->whereDate('created_at', $value))),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-hand-thumb-up')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Are you sure you want to approve?')
                    ->visible(fn($record) => Auth::user()->role === 'SuperAdmin' && $record->status === 'pending')
                    ->action(function ($record) {
                        $superadmin = Auth::user();
                        $admin      = $record->admin;

                        if ($superadmin->balance < $record->amount) {
                            \Filament\Notifications\Notification::make()
                                ->title('Insufficient Balance')
                                ->body('You do not have enough balance to approve this request.')
                                ->danger()
                                ->send();
                            return;
                        }

                        DB::transaction(function () use ($superadmin, $admin, $record) {
                            $superadmin->decrement('balance', $record->amount);
                            $admin->increment('balance', $record->amount);

                            $record->update([
                                'status'      => 'approved',
                                'approved_by' => $superadmin->id,
                            ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Balance Request Approved')
                            ->body('You have successfully approved the balance request.')
                            ->success()
                            ->send();
                    }),

                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-hand-thumb-down')
                    ->visible(fn($record) => Auth::user()->role === 'SuperAdmin' && $record->status === 'pending')
                    ->action(fn($record) => $record->update(['status' => 'rejected']))
                    ->requiresConfirmation()
                    ->modalHeading('Are you sure you want to reject?')
                    ->color('danger'),

                Tables\Actions\EditAction::make()
                    ->label('Update Balance'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBalanceRequests::route('/'),
            'create' => Pages\CreateBalanceRequest::route('/create'),
        ];
    }
}
