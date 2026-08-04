<?php

namespace Modules\AgenticHub\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgenticAuditLog extends Model
{
    use HasFactory;

    protected $table = 'agentic_audit_logs';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'agent_code',
        'tool_name',
        'parameters',
        'status_code',
        'latency_ms',
        'error_message',
        'ip_address',
    ];

    protected $casts = [
        'parameters' => 'array',
        'status_code' => 'integer',
        'latency_ms' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Helper to log tool execution
     */
    public static function logExecution($userId, $agentCode, $toolName, $parameters, $statusCode, $latencyMs, $errorMessage = null)
    {
        try {
            static::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'agent_code' => $agentCode,
                'tool_name' => $toolName,
                'parameters' => $parameters,
                'status_code' => $statusCode,
                'latency_ms' => $latencyMs,
                'error_message' => $errorMessage,
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            // Fail safe logging
        }
    }
}
