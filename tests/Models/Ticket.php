<?php

namespace TestMonitor\Searchable\Test\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use TestMonitor\Searchable\Searchable;

class Ticket extends Model
{
    use HasFactory, Searchable;

    protected $table = 'tickets';

    public $timestamps = false;

    public $guarded = [];

    public static function booted()
    {
        static::creating(function(Ticket $ticket) {
            $ticket->code = (int) (Ticket::latest('code')->first()?->getAttributes()['code']) + 1;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function code(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => "T-{$attributes['code']}"
        );
    }
}
