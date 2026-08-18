<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class Admin extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'admin';

    protected $fillable = ['name', 'email', 'password', 'is_super_admin'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'is_super_admin' => 'boolean',
    ];

    /**
     * فعلاً هر رکورد admin اجازه‌ی ورود به پنل را دارد؛ کنترل دقیق‌تر
     * دسترسی به هر بخش (بند ۲۳ سند: Super Admin/Server Admin/Support
     * Admin) از طریق Policy های Filament روی هر Resource انجام می‌شود،
     * نه این‌جا.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
