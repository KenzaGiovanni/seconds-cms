<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One outbound API call (to Xendit/KiriminAja) or inbound webhook receipt,
 * for debugging. See App\Support\ApiLogger for how these get written.
 */
class ApiLog extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'provider', 'direction', 'method', 'endpoint',
        'request_payload', 'response_payload', 'status_code', 'successful',
        'duration_ms', 'error_message', 'loggable_type', 'loggable_id',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'successful' => 'boolean',
        'created_at' => 'datetime',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
