<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncidentFollowUp extends Model
{
    protected $fillable = [
        'incident_id',
        'owner',
        'label',
        'status',
    ];

    public function incident()
    {
        return $this->belongsTo(Incident::class);
    }
}
