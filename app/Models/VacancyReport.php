<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VacancyReport extends Model
{
    protected $fillable = [
        'venue_id',
        'age_group',
        'status',
        'comment',
        'nickname',
        'ip_hash',
    ];

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }
}
