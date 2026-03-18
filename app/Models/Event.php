<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Event extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description', 'date', 'location', 'created_by'];

    protected function casts(): array {
        return ['date' => 'datetime'];
    }

    public function creator() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets() {
        return $this->hasMany(Ticket::class);
    }
}
