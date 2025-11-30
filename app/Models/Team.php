<?php

// app/Models/Team.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Team extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'color', 'owner_id' ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function team()
    {
        // primary / current team for this user
        return $this->belongsTo(Team::class);
    }

    public function teams()
    {
        // all teams the user belongs to (requires team_user pivot)
        return $this->belongsToMany(Team::class)
            ->withPivot('role')
            ->withTimestamps();
    }
}
