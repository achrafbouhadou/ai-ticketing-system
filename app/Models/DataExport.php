<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class DataExport extends Model
{
    use HasUlids;

    protected $fillable = ['type','status','params','file_path','error','expires_at'];

    protected $casts = [
        'params'     => 'array',
        'expires_at' => 'datetime',
    ];
}
