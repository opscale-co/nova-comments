<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Workbench\Database\Factories\UserFactory;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    /** @var array<int, string> */
    protected $hidden = ['password', 'remember_token'];

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
