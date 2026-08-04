<?php

namespace Modules\AgenticHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AgenticHub\Services\AgenticScopeGatekeeper;
use Modules\AgenticHub\Models\AgenticProduct;
use Modules\AgenticHub\Models\AgenticAuditLog;

class AgenticToolApiController extends Controller
{
    /**
     * Single Gateway Endpoint for AI Tool Calling API Execution
     * POST /api/v1/agentic-hub/tools/execute
     */
    public function execute(Request $request)
    {
        $startTime = microtime(true);

        $request->validate([
            'tool' => 'required|string',
            'parameters' => 'nullable|array',
        ]);

        $tool = trim($request->tool);
        $parameters = $request->parameters ?? [];

        // 1. Gatekeeper Scope & API Key Validation (Fase 4 Enforcement)
        $access = AgenticScopeGatekeeper::checkAccess($request, $tool);
        if (!$access['allowed']) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            $userId = isset($access['agent']) ? $access['agent']->user_id : auth()->id();
            $agentCode = isset($access['agent']) ? $access['agent']->agent_code : null;
            
            AgenticAuditLog::logExecution(
                $userId ?? 0,
                $agentCode,
                $tool,
                $parameters,
                $access['status'],
                $latencyMs,
                $access['response']['message'] ?? 'Forbidden'
            );

            return response()->json($access['response'], $access['status']);
        }

        $agent = $access['agent'];
        $userId = $agent->user_id;

        // 2. Dispatch Tool Execution
        switch ($tool) {
            case 'search_products':
                return $this->handleSearchProducts($userId, $agent->agent_code, $parameters, $startTime);

            case 'get_product_details':
                return $this->handleGetProductDetails($userId, $agent->agent_code, $parameters, $startTime);

            case 'get_product_checkout_link':
                return $this->handleGetProductCheckoutLink($userId, $agent->agent_code, $parameters, $startTime);

            default:
                $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
                AgenticAuditLog::logExecution($userId, $agent->agent_code, $tool, $parameters, 404, $latencyMs, "Tool '{$tool}' tidak ditemukan.");

                return response()->json([
                    'success' => false,
                    'error_code' => 'UNKNOWN_TOOL',
                    'message' => "Tool '{$tool}' tidak dikenali di Agentic Hub API Engine.",
                    'available_tools' => ['search_products', 'get_product_details', 'get_product_checkout_link']
                ], 404);
        }
    }

    /**
     * Tool Handler 1: search_products
     */
    protected function handleSearchProducts($userId, $agentCode, array $params, $startTime)
    {
        $query = AgenticProduct::where('user_id', $userId)->where('is_active', true);

        if (!empty($params['query'])) {
            $searchTerm = trim($params['query']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('sku', 'like', "%{$searchTerm}%")
                  ->orWhere('description', 'like', "%{$searchTerm}%");
            });
        }

        if (!empty($params['category'])) {
            $query->where('category', trim($params['category']));
        }

        $products = $query->orderBy('name', 'asc')->get()->map(function ($p) {
            return [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category,
                'price' => (float) $p->price,
                'promo_price' => $p->promo_price ? (float) $p->promo_price : null,
                'effective_price' => $p->promo_price && $p->promo_price < $p->price ? (float) $p->promo_price : (float) $p->price,
                'stock_status' => $p->stock_status,
                'short_description' => $p->description ? \Illuminate\Support\Str::limit($p->description, 100) : null,
            ];
        });

        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        AgenticAuditLog::logExecution($userId, $agentCode, 'search_products', $params, 200, $latencyMs);

        return response()->json([
            'success' => true,
            'tool' => 'search_products',
            'total_found' => count($products),
            'latency_ms' => $latencyMs,
            'products' => $products,
        ]);
    }

    /**
     * Tool Handler 2: get_product_details
     */
    protected function handleGetProductDetails($userId, $agentCode, array $params, $startTime)
    {
        if (empty($params['sku'])) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            AgenticAuditLog::logExecution($userId, $agentCode, 'get_product_details', $params, 422, $latencyMs, "Parameter 'sku' wajib diisi.");

            return response()->json([
                'success' => false,
                'error_code' => 'MISSING_PARAMETER',
                'message' => "Parameter 'sku' wajib diisi untuk tool 'get_product_details'."
            ], 422);
        }

        $sku = strtoupper(trim($params['sku']));
        $product = AgenticProduct::where('user_id', $userId)->where('sku', $sku)->where('is_active', true)->first();

        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        if (!$product) {
            AgenticAuditLog::logExecution($userId, $agentCode, 'get_product_details', $params, 404, $latencyMs, "Produk SKU '{$sku}' tidak ditemukan.");

            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => "Produk dengan SKU '{$sku}' tidak ditemukan."
            ], 404);
        }

        AgenticAuditLog::logExecution($userId, $agentCode, 'get_product_details', $params, 200, $latencyMs);

        return response()->json([
            'success' => true,
            'tool' => 'get_product_details',
            'latency_ms' => $latencyMs,
            'product' => [
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category,
                'price' => (float) $product->price,
                'promo_price' => $product->promo_price ? (float) $product->promo_price : null,
                'description' => $product->description,
                'product_link' => $product->product_link,
                'checkout_link' => $product->checkout_link,
                'stock_status' => $product->stock_status,
            ]
        ]);
    }

    /**
     * Tool Handler 3: get_product_checkout_link
     */
    protected function handleGetProductCheckoutLink($userId, $agentCode, array $params, $startTime)
    {
        if (empty($params['sku']) && empty($params['query'])) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            AgenticAuditLog::logExecution($userId, $agentCode, 'get_product_checkout_link', $params, 422, $latencyMs, "Parameter 'sku' atau 'query' wajib diisi.");

            return response()->json([
                'success' => false,
                'error_code' => 'MISSING_PARAMETER',
                'message' => "Parameter 'sku' atau 'query' wajib diisi untuk tool 'get_product_checkout_link'."
            ], 422);
        }

        $query = AgenticProduct::where('user_id', $userId)->where('is_active', true);

        if (!empty($params['sku'])) {
            $query->where('sku', strtoupper(trim($params['sku'])));
        } else {
            $searchTerm = trim($params['query']);
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $product = $query->first();
        $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

        if (!$product) {
            AgenticAuditLog::logExecution($userId, $agentCode, 'get_product_checkout_link', $params, 404, $latencyMs, 'Produk tidak ditemukan.');

            return response()->json([
                'success' => false,
                'error_code' => 'PRODUCT_NOT_FOUND',
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        AgenticAuditLog::logExecution($userId, $agentCode, 'get_product_checkout_link', $params, 200, $latencyMs);

        return response()->json([
            'success' => true,
            'tool' => 'get_product_checkout_link',
            'latency_ms' => $latencyMs,
            'sku' => $product->sku,
            'product_name' => $product->name,
            'price' => $product->promo_price && $product->promo_price < $product->price ? (float) $product->promo_price : (float) $product->price,
            'checkout_link' => $product->checkout_link ?? $product->product_link,
        ]);
    }

    /**
     * End-to-End AI Agent Conversation API Endpoint
     * POST /api/v1/agentic-hub/chat
     */
    public function chat(Request $request)
    {
        $startTime = microtime(true);

        $request->validate([
            'message' => 'required|string',
            'model_name' => 'nullable|string',
        ]);

        $authHeader = $request->header('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'Missing or invalid Authorization Bearer token header.'
            ], 401);
        }

        $plainToken = trim(substr($authHeader, 7));
        $tokenHash = hash('sha256', $plainToken);

        $agent = \Modules\AgenticHub\Models\AgenticAiAgent::where('api_key_hash', $tokenHash)->first()
            ?? \Modules\AgenticHub\Models\AgenticAiAgent::where('plain_api_key', $plainToken)->first();

        if (!$agent || !$agent->is_active) {
            return response()->json([
                'success' => false,
                'status' => 401,
                'message' => 'API Key AI Agent tidak valid atau telah dinonaktifkan.'
            ], 401);
        }

        $agentScopes = $agent->scopes ?? [];
        $hasProductAccess = in_array('*:*', $agentScopes) || in_array('products:read', $agentScopes);
        if (!$hasProductAccess) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => "AI Agent '{$agent->agent_name}' ({$agent->agent_code}) tidak memiliki otoritas scope 'products:read' untuk membaca data katalog produk toko."
            ], 403);
        }

        $userId = $agent->user_id;
        
        // Fetch global provider config for tenant
        $firstAgent = \Modules\AgenticHub\Models\AgenticAiAgent::where('user_id', $userId)->first();
        $baseUrl = $agent->openai_base_url ?: ($firstAgent ? $firstAgent->openai_base_url : null);
        $apiKey = $agent->provider_api_key ?: ($firstAgent ? $firstAgent->provider_api_key : null);

        if (!$baseUrl) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'Single Global AI Model Provider Base URL belum dikonfigurasi di Tab 3.'
            ], 400);
        }

        $baseUrl = rtrim(trim($baseUrl), '/');
        $model = $request->filled('model_name') ? trim($request->model_name) : ($agent->model_name ?: 'gpt-4o-mini');
        $prompt = trim($request->message);

        // Fetch database products for this user
        $dbProducts = AgenticProduct::where('user_id', $userId)->where('is_active', true)->get();

        if ($dbProducts->count() > 0) {
            $catalogText = "DATA KATALOG PRODUK REAL DI DATABASE TOKO:\n";
            foreach ($dbProducts as $p) {
                $promoText = ($p->promo_price && $p->promo_price < $p->price) ? " (Harga Promo: Rp " . number_format($p->promo_price, 0, ',', '.') . ")" : "";
                $descText = $p->description ? " | Deskripsi/Spesifikasi: " . \Illuminate\Support\Str::limit($p->description, 150) : "";
                $productLinkText = $p->product_link ? " | Link Produk: {$p->product_link}" : "";
                $catalogText .= "- SKU: {$p->sku} | Nama: {$p->name} | Kategori: {$p->category} | Harga Resmi: Rp " . number_format($p->price, 0, ',', '.') . "{$promoText}{$descText}{$productLinkText} | Status Stok: {$p->stock_status} | Link Checkout Direct: {$p->checkout_link}\n";
            }
        } else {
            $catalogText = "DATA KATALOG PRODUK DI DATABASE TOKO SAAT INI: KOSONG (0 PRODUK TERDAFTAR ATAU BELUM DIINPUT DI FASE 2).\n";
        }

        $systemPrompt = ($agent->system_prompt ? $agent->system_prompt . "\n\n" : "")
            . "DATA REAL DATABASE TOKO SANGAT PENTING DIBACA:\n"
            . $catalogText . "\n"
            . "ATURAN STRICT UTAMA:\n"
            . "1. JIKA DATABASE KOSONG / TIDAK ADA PRODUK YANG SESUAI, KAMU WAJIB MENJAWAB SECARA TRANSPARAN & JUJUR BAHWA STOK KOSONG ATAU BELUM TERSEDIA DI DATABASE TOKO SAAT INI.\n"
            . "2. DILARANG KERAS HALUSINASI, MENJELASKAN DETAIL WARNA/UKURAN YANG TIDAK ADA DI DATABASE, ATAU MENGARANG HARGA/PROMO SANGKAAN!\n"
            . "3. Jika produk tersedia di database di atas, sebutkan nama produk, harga resmi & promo, status stok, serta berikan Link Checkout Direct resminya.\n"
            . "4. ATURAN FORMAT CHAT WHATSAPP:\n"
            . "   - Gunakan format tebal WhatsApp *teks tebal* (SATU tanda bintang '*'), DILARANG DUA BINTANG '**'!\n"
            . "   - DILARANG MENGGUNAKAN LINK MARKDOWN '[Teks](https://...)'. SELALU TULISKAN ALAMAT URL LANGSUNG (Contoh: Link checkout: https://...).\n"
            . "   - Buat format pesan yang ramah, rapi, dan nyaman dibaca di layar chat WhatsApp HP.\n"
            . "5. FOKUS KURSOR: AI HANYA BERTUGAS LAYANAN PELANGGAN (CS), INFORMASI MARKETING & PROMO, DAN REKOMENDASI PRODUK. DILARANG PROSES ATAU MEMINTA PENYIMPANAN DATA PRIBADI PELANGGAN.";

        $url = $baseUrl . '/chat/completions';

        try {
            $http = \Illuminate\Support\Facades\Http::timeout(15);
            if ($apiKey) {
                $http->withToken($apiKey);
            }

            $response = $http->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $agent->temperature ?? 0.70,
                'max_tokens' => $agent->max_tokens ?? 1000,
            ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'Koneksi Sukses! (Tanpa balasan teks)';

                AgenticAuditLog::logExecution(
                    $userId,
                    $agent->agent_code,
                    'chat_completion',
                    ['message' => $prompt, 'model' => $model],
                    200,
                    $latencyMs,
                    'AI Agent Chat Completion Responded Successfully'
                );

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'agent' => [
                        'id' => $agent->id,
                        'name' => $agent->agent_name,
                        'code' => $agent->agent_code,
                        'role_level' => $agent->role_level,
                    ],
                    'reply' => $reply,
                    'db_products_count' => $dbProducts->count(),
                    'model_used' => $model,
                    'latency_ms' => $latencyMs,
                ]);
            }

            AgenticAuditLog::logExecution($userId, $agent->agent_code, 'chat_completion', ['message' => $prompt], $response->status(), $latencyMs, $response->body());

            return response()->json([
                'success' => false,
                'status' => $response->status(),
                'message' => 'Gagal memanggil AI chat completion (' . $response->status() . '): ' . $response->body(),
                'latency_ms' => $latencyMs,
            ], $response->status());

        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            AgenticAuditLog::logExecution($userId, $agent->agent_code, 'chat_completion', ['message' => $prompt], 500, $latencyMs, $e->getMessage());

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Terjadi kesalahan koneksi ke endpoint (' . $url . '): ' . $e->getMessage(),
                'latency_ms' => $latencyMs,
            ], 500);
        }
    }

    /**
     * Fonnte-Compatible Webhook Receiver Endpoint for Agentic Hub
     * POST /api/v1/agentic-hub/webhook/receiver
     */
    public function webhookReceiver(Request $request)
    {
        $startTime = microtime(true);

        // 1. Extract Message Body from Fonnte standard keys (message, text, body)
        $messageText = $request->input('message') ?? ($request->input('text') ?? ($request->input('body') ?? null));
        if (empty($messageText)) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter "message" atau "text" wajib diisi.',
                'reply' => 'Parameter message tidak ditemukan.'
            ], 400);
        }

        // 2. Extract API Key (query string ?key=, ?api_key=, body key, or Bearer header)
        $plainToken = $request->query('key') 
            ?? ($request->query('api_key') 
            ?? ($request->input('key') 
            ?? ($request->input('api_key') ?? null)));

        if (empty($plainToken)) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
                $plainToken = trim(substr($authHeader, 7));
            }
        }

        $agent = null;
        if (!empty($plainToken)) {
            $tokenHash = hash('sha256', $plainToken);
            $agent = \Modules\AgenticHub\Models\AgenticAiAgent::where('api_key_hash', $tokenHash)->first()
                ?? \Modules\AgenticHub\Models\AgenticAiAgent::where('plain_api_key', $plainToken)->first();
        }

        // Fallback to logged-in user agent if available
        if (!$agent && auth()->check()) {
            $agent = \Modules\AgenticHub\Models\AgenticAiAgent::where('user_id', auth()->id())->where('is_active', true)->first();
        }

        if (!$agent || !$agent->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'API Key AI Agent tidak valid. Gunakan URL format: https://.../webhook/receiver?key=agentic_sk_...',
                'reply' => 'Autentikasi AI Agent gagal.'
            ], 401);
        }

        // Fast Handler for Webhook Test Ping Events
        if ($request->input('event') === 'test.ping' || $request->header('X-WA-HUB-Event') === 'test.ping' || str_contains($messageText, 'Tes pengiriman webhook')) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            return response()->json([
                'status' => true,
                'reply' => 'Koneksi Webhook Agentic Hub AI Berhasil (Test Ping OK)! Server AI siap menerima pesan WhatsApp.',
                'message' => 'Koneksi Webhook Agentic Hub AI Berhasil (Test Ping OK)! Server AI siap menerima pesan WhatsApp.',
                'agent_code' => $agent->agent_code,
                'latency_ms' => $latencyMs,
            ]);
        }

        // 3. Delegate to Chat Completion Engine
        $userId = $agent->user_id;

        // Fetch global provider config
        $firstAgent = \Modules\AgenticHub\Models\AgenticAiAgent::where('user_id', $userId)->first();
        $baseUrl = $agent->openai_base_url ?: ($firstAgent ? $firstAgent->openai_base_url : null);
        $apiKey = $agent->provider_api_key ?: ($firstAgent ? $firstAgent->provider_api_key : null);

        if (!$baseUrl) {
            return response()->json([
                'status' => false,
                'message' => 'Single Global AI Model Provider Base URL belum dikonfigurasi di Tab 3 Agentic Hub.',
                'reply' => 'AI Provider belum dikonfigurasi.'
            ], 400);
        }

        $baseUrl = rtrim(trim($baseUrl), '/');
        $model = $agent->model_name ?: 'gpt-4o-mini';
        $prompt = trim($messageText);

        // Fetch database products with 5-minute caching for ultra-fast performance
        $catalogText = \Illuminate\Support\Facades\Cache::remember("agentic_catalog_text_{$userId}", 300, function () use ($userId) {
            $dbProducts = AgenticProduct::where('user_id', $userId)->where('is_active', true)->get();
            if ($dbProducts->count() > 0) {
                $text = "DATA KATALOG PRODUK REAL DI DATABASE TOKO:\n";
                foreach ($dbProducts as $p) {
                    $promoText = ($p->promo_price && $p->promo_price < $p->price) ? " (Harga Promo: Rp " . number_format($p->promo_price, 0, ',', '.') . ")" : "";
                    $descText = $p->description ? " | Deskripsi: " . \Illuminate\Support\Str::limit($p->description, 120) : "";
                    $productLinkText = $p->product_link ? " | Link Produk: {$p->product_link}" : "";
                    $text .= "- SKU: {$p->sku} | Nama: {$p->name} | Harga: Rp " . number_format($p->price, 0, ',', '.') . "{$promoText}{$descText}{$productLinkText} | Stok: {$p->stock_status} | Link Checkout: {$p->checkout_link}\n";
                }
                return $text;
            }
            return "DATA KATALOG PRODUK DI DATABASE TOKO SAAT INI: KOSONG (0 PRODUK TERDAFTAR).\n";
        });

        $systemPrompt = ($agent->system_prompt ? $agent->system_prompt . "\n\n" : "")
            . "DATA REAL DATABASE TOKO SANGAT PENTING DIBACA:\n"
            . $catalogText . "\n"
            . "ATURAN STRICT UTAMA:\n"
            . "1. JIKA DATABASE KOSONG / TIDAK ADA PRODUK YANG SESUAI, KAMU WAJIB MENJAWAB SECARA TRANSPARAN & JUJUR BAHWA STOK KOSONG ATAU BELUM TERSEDIA DI DATABASE TOKO SAAT INI.\n"
            . "2. DILARANG KERAS HALUSINASI, MENJELASKAN DETAIL WARNA/UKURAN YANG TIDAK ADA DI DATABASE, ATAU MENGARANG HARGA/PROMO SANGKAAN!\n"
            . "3. Jika produk tersedia di database di atas, sebutkan nama produk, harga resmi & promo, status stok, serta berikan Link Checkout Direct resminya.\n"
            . "4. ATURAN FORMAT CHAT WHATSAPP:\n"
            . "   - Gunakan format tebal WhatsApp *teks tebal* (SATU tanda bintang '*'), DILARANG DUA BINTANG '**'!\n"
            . "   - DILARANG MENGGUNAKAN LINK MARKDOWN '[Teks](https://...)'. SELALU TULISKAN ALAMAT URL LANGSUNG (Contoh: Link checkout: https://...).\n"
            . "   - Buat format pesan singkat, padat, ramah, dan nyaman dibaca di layar chat WhatsApp HP.\n"
            . "5. FOKUS KURSOR: AI HANYA BERTUGAS LAYANAN PELANGGAN (CS), INFORMASI MARKETING & PROMO, DAN REKOMENDASI PRODUK. DILARANG PROSES ATAU MEMINTA PENYIMPANAN DATA PRIBADI PELANGGAN.";

        $url = $baseUrl . '/chat/completions';

        try {
            $http = \Illuminate\Support\Facades\Http::timeout(6);
            if ($apiKey) {
                $http->withToken($apiKey);
            }

            $response = $http->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => $agent->temperature ?? 0.70,
                'max_tokens' => min($agent->max_tokens ?? 400, 450), // Optimized token limit for 2x faster LLM response
            ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'Halo! Terima kasih sudah menghubungi kami.';

                AgenticAuditLog::logExecution(
                    $userId,
                    $agent->agent_code,
                    'fonnte_webhook_receiver',
                    ['sender' => $request->input('sender'), 'message' => $prompt],
                    200,
                    $latencyMs,
                    'Fonnte Webhook Auto-Reply Dispatched'
                );

                // 100% Official Fonnte Webhook Response Format
                return response()->json([
                    'status' => true,
                    'reply' => $reply,
                    'message' => $reply,
                    'agent_code' => $agent->agent_code,
                    'latency_ms' => $latencyMs,
                ]);
            }

            $fallbackMsg = 'Halo Kak! Terima kasih sudah menghubungi toko kami. Pesan Kakak sedang diproses.';
            AgenticAuditLog::logExecution($userId, $agent->agent_code, 'fonnte_webhook_receiver', ['message' => $prompt], $response->status(), $latencyMs, $response->body());

            return response()->json([
                'status' => true,
                'reply' => $fallbackMsg,
                'message' => $fallbackMsg,
            ]);

        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            AgenticAuditLog::logExecution($userId, $agent->agent_code, 'fonnte_webhook_receiver', ['message' => $prompt], 500, $latencyMs, $e->getMessage());

            return response()->json([
                'status' => true,
                'reply' => 'Halo Kak! Terima kasih sudah menghubungi toko kami.',
                'message' => 'Halo Kak! Terima kasih sudah menghubungi toko kami.',
            ]);
        }
    }
}
