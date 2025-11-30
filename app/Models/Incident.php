<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Incident extends Model
{

    protected $fillable = [
        'key','title','severity','status','system',
        'started_at','last_updated_at','impacted_regions',
        'impacted_users','owner','summary','tags',
    ];


    protected $casts = [
        'started_at'      => 'datetime',
        'last_updated_at' => 'datetime',
        'impacted_regions'=> 'array',
        'tags'            => 'array',
    ];



    public function events()
    {
        return $this->hasMany(IncidentEvent::class)
            ->orderBy('occurred_at')
            ->orderBy('id');
    }

    public function followUps()
    {
        return $this->hasMany(IncidentFollowUp::class);
    }
}
