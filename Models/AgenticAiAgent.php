<?php

namespace Modules\AgenticHub\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AgenticAiAgent extends Model
{
    use HasFactory;

    protected $table = 'agentic_ai_agents';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'agent_code',
        'agent_name',
        'role_level',
        'openai_base_url',
        'model_name',
        'provider_api_key',
        'temperature',
        'max_tokens',
        'scopes',
        'assigned_account',
        'assigned_category',
        'system_prompt',
        'api_key_hash',
        'plain_api_key',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'temperature' => 'decimal:2',
        'max_tokens' => 'integer',
        'is_active' => 'boolean',
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
     * Generate API Key for AI Agent
     */
    public static function generateApiKey(): array
    {
        $plainKey = 'agentic_sk_' . Str::random(32);
        $hash = hash('sha256', $plainKey);
        return [
            'plain' => $plainKey,
            'hash' => $hash,
        ];
    }

    /**
     * Relationship to User (Tenant)
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
