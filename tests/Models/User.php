<?php

namespace TestMonitor\Searchable\Test\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use TestMonitor\Searchable\Searchable;

class User extends Model
{
    use HasFactory, Searchable;

    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
