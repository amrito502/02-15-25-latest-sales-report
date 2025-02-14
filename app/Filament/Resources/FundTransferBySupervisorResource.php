<?php
namespace App\Filament\Resources;

use App\Models\Team;
use App\Models\User;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\TeamAdmin;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Forms\Components\Group;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Models\FundTransferBySupervisor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Components\Actions\Action;
use App\Filament\Resources\FundTransferBySupervisorResource\Pages;

class FundTransferBySupervisorResource extends Resource
{
    protected static ?string $model = FundTransferBySupervisor::class;

    protected static ?string $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Payment By Supervisor';
    protected static ?string $modelLabel      = 'Payment Transfer';
    protected static ?string $navigationGroup = 'Funds Administration';
    protected static ?int $navigationSort     = 4;
    public bool $isLoadingAdmins              = false;

    public static function canAccess(): bool
    {
        return Auth::user()->role === 'SuperVisor';
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Payment Transaction Information')
                            ->description('Select the team and corresponding admin, and input the transfer amount.')
                            ->schema([
                                Select::make('team_id')
                                    ->label('Select Team')
                                    ->options(function () {
                                        return Team::all()->pluck('name', 'id');
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(fn($state, $set) => $set('admin_id', null)),

                                Select::make('admin_id')
                                    ->label('Select Admin')
                                    ->options(function ($get) {
                                        $teamId = $get('team_id');

                                        return TeamAdmin::where('team_id', $teamId)
                                            ->with('admin')
                                            ->get()
                                            ->pluck('admin.name', 'admin.id');
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
                            ]),
                    ]),
                Actions::make([
                    Action::make('transferMoneyBySupervisor')
                        ->label('Payment Transfer By Supervisor')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($livewire) {
                            $data = $livewire->form->getState();
                            static::transferMoneyBySupervisor($data);
                        }),
                ])->columnSpanFull(),
            ]);
    }

    public static function transferMoneyBySupervisor($data)
    {
        DB::transaction(function () use ($data) {

            $supervisor_id = Auth::id();
            $supervisor    = User::where('id', $supervisor_id)->where('role', 'Supervisor')->first();

            if (! $supervisor) {
                throw new \Exception('Supervisor not found.');
            }


            $admin_ids = $data['admin_id'];
            if (empty($admin_ids) || ! is_array($admin_ids)) {
                throw new \Exception('No valid admins selected.');
            }


            $total_amount = $data['amount'] * count($admin_ids);


            if ($supervisor->balance < $total_amount) {
                throw new \Exception('Insufficient funds available for this transfer.');
            }


            // $amount = $data['amount'];

            // $adminBalances = array_fill_keys($admin_ids, $amount);

            // print_r($adminBalances);

            User::whereIn('id', $admin_ids)
                ->where('role', 'Admin')
                ->increment('balance', $data['amount']);

            foreach ($admin_ids as $admin_id) {
                $admin = User::where('id', $admin_id)->where('role', 'Admin')->first();

                if ($admin) {
                    // $admin->increment('balance', $data['amount']);
                    FundTransferBySupervisor::create([
                        'admin_id'      => $admin->id,
                        'supervisor_id' => $supervisor->id,
                        'amount'        => $data['amount'],
                    ]);
                } else {
                    Log::warning('Admin with ID ' . $admin . ' not found or invalid role.');
                }
            }

            $supervisor->decrement('balance', $total_amount);

            Notification::make()
                ->title('Money Sent Successfully')
                ->success()
                ->send();
        }, 5);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->query(
                FundTransferBySupervisor::query()
                    ->when(auth()->user()->role === 'admin', fn($query) => $query->where('admin_id', auth()->id()))
            )
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->numeric()
                    ->searchable()
                    ->toggleable(true)
                    ->sortable(),
                TextColumn::make('supervisor.name')
                    ->label('Transfer By')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('admin.name')
                    ->label('Transfer To')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('amount')
                    ->summarize([
                        Sum::make()->money('BDT')
                            ->label('Total Transaction Money : ')
                    ])->label('Individual Amount')->sortable()->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->searchable()
                    ->sortable()
                    ->toggleable(true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make(),]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListFundTransferBySupervisors::route('/'),
            'create' => Pages\CreateFundTransferBySupervisor::route('/create'),
            'edit'   => Pages\EditFundTransferBySupervisor::route('/{record}/edit'),
        ];
    }
}
