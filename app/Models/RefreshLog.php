<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefreshLog extends Model
{
    use HasFactory;

    protected $table = 'refresh_logs';

    protected $fillable = ['method', 'caption', 'status', 'error_message'];
}
