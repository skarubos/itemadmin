<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'name_kana',
        'member_code',
        'phone_number',
        'sales',
        'latest_trade',
        'depo_status',
        'sub_leader',
        'sub_number',
        'sub_now',
        'priority',
        'permission',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Tradingsリレーションの追加
    public function tradings() {
        return $this->hasMany(Trading::class, 'member_code', 'member_code');
    }

    // 認証に使用するIDとして member_code を返すように変更
    public function getAuthIdentifierName()
    {
        return 'member_code';
    }

    // permission カラムが 1 の場合に管理者とする
    public function isAdmin()
    {
        return $this->permission == 1;
    }

}