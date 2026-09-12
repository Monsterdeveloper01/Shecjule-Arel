<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FocusSession extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'task_id',
        'type',
        'duration_minutes',
        'started_at',
        'ended_at',
        'completed',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'completed' => 'boolean',
            'duration_minutes' => 'integer',
        ];
    }

    /**
     * The task this focus session was dedicated to.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
