<?php

namespace App\Models;

use App\Enums\TicketCategory;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Ticket extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'subject',
        'body',
        'status',
        'category',
        'category_source',
        'explanation',
        'confidence',
        'note',
    ];

    protected $casts = [
        'status' => TicketStatus::class,
        'category' => TicketCategory::class,
        'confidence' => 'decimal:2',
    ];
}
