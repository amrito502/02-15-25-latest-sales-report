<?php

namespace App\Models;

use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class FundTransferBySupervisor extends Model
{


    protected $fillable = [
        'amount',
        'transaction_id',
        'admin_id',
        'supervisor_id',
        'team_id',
        'user_id'
    ];

    public function isAssignedToCurrentUser(): bool
    {
        return $this->user_id === Auth::id();
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function team()
    {
        return $this->belongsToMany(Team::class, 'team_id');
    }

    public function admins()
    {
        return $this->belongsToMany(User::class, 'team_admins', 'team_id', 'admin_id');
    }



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->transaction_id = self::generateTransactionId();
        });
    }

    public static function generateTransactionId(): string
    {
        $prefix = 'TrxID';
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = '';

        for ($i = 0; $i < 10; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $prefix . $randomString;
    }
}
