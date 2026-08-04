<?php

namespace Modules\AgenticHub\Services;

use Modules\AgenticHub\Models\AgenticAiAgent;
use Illuminate\Http\Request;

class AgenticScopeGatekeeper
{
    /**
     * Tool to required scope mapping
     */
    protected static array $toolScopeMap = [
        'search_products' => 'products:read',
        'get_product_details' => 'products:read',
        'get_product_checkout_link' => 'checkout:read',
        'update_product_stock' => 'products:write',
        'update_product_price' => 'products:write',
        'delete_product' => 'products:delete',
    ];

    /**
     * Authenticate AI Agent & Check Scope Authority
     */
    public static function checkAccess(Request $request, string $toolName): array
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return [
                'allowed' => false,
                'status' => 401,
                'response' => [
                    'success' => false,
                    'error_code' => 'UNAUTHORIZED',
                    'message' => 'Missing or invalid Authorization Bearer token header.'
                ]
            ];
        }

        $plainToken = trim(substr($authHeader, 7));
        $tokenHash = hash('sha256', $plainToken);

        $agent = AgenticAiAgent::where('api_key_hash', $tokenHash)->first();

        // Fallback for plain token matching during development
        if (!$agent) {
            $agent = AgenticAiAgent::where('plain_api_key', $plainToken)->first();
        }

        if (!$agent || !$agent->is_active) {
            return [
                'allowed' => false,
                'status' => 401,
                'response' => [
                    'success' => false,
                    'error_code' => 'INVALID_AI_AGENT_KEY',
                    'message' => 'API Key AI Agent tidak valid atau telah dinonaktifkan.'
                ]
            ];
        }

        $requiredScope = static::$toolScopeMap[$toolName] ?? '*:*';
        $agentScopes = $agent->scopes ?? [];

        // Check if agent has wildcard '*:*' or exact required scope
        $hasPermission = in_array('*:*', $agentScopes) || in_array($requiredScope, $agentScopes);

        if (!$hasPermission) {
            return [
                'allowed' => false,
                'status' => 403,
                'response' => [
                    'success' => false,
                    'error_code' => 'AI_SCOPE_FORBIDDEN',
                    'required_scope' => $requiredScope,
                    'ai_agent_code' => $agent->agent_code,
                    'ai_agent_name' => $agent->agent_name,
                    'message' => "Kecerdasan Buatan '{$agent->agent_name}' ({$agent->agent_code}) tidak memiliki otoritas scope '{$requiredScope}' untuk mengeksekusi tool '{$toolName}'."
                ]
            ];
        }

        return [
            'allowed' => true,
            'agent' => $agent,
        ];
    }
}
