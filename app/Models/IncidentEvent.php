<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentEvent extends Model
{
    protected $fillable = [
        'incident_id',
        'occurred_at',
        'type',
        'actor',
        'label',
        'detail',
        'sort_order',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }

    
}
