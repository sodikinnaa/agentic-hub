<?php

namespace Modules\AgenticHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AgenticHub\Models\AgenticProduct;
use Modules\AgenticHub\Models\AgenticAiAgent;
use Modules\AgenticHub\Models\AgenticAuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AgenticHubController extends Controller
{
    /**
     * Display AI Management & Product Catalog Dashboard
     */
    public function index(Request $request)
    {
        $userId = auth()->id();
        
        // Ensure Default Preset AI Agents Exist for User
        $this->ensureDefaultAgentsExist($userId);

        $query = AgenticProduct::where('user_id', $userId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($request->filled('stock_status')) {
            $query->where('stock_status', $request->stock_status);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Calculate Stat Cards
        $totalProducts = AgenticProduct::where('user_id', $userId)->count();
        $totalPromo = AgenticProduct::where('user_id', $userId)->whereNotNull('promo_price')->where('promo_price', '>', 0)->count();
        $outOfStock = AgenticProduct::where('user_id', $userId)->where('stock_status', 'out_of_stock')->count();

        // Fetch AI Agents
        $aiAgents = AgenticAiAgent::where('user_id', $userId)->orderBy('role_level', 'asc')->get();

        // Single Global Provider Config for User (Fase 3)
        $firstAgent = $aiAgents->first();
        $globalProvider = [
            'openai_base_url' => $firstAgent->openai_base_url ?? '',
            'provider_api_key' => $firstAgent->provider_api_key ?? '',
        ];

        // Fetched Models list cached for User
        $fetchedModels = Cache::get("agentic_hub_fetched_models_{$userId}", []);

        // Fetch Audit Logs (Fase 7)
        $auditLogs = AgenticAuditLog::where('user_id', $userId)->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        // Fetch WA Hub Webhook Integration Status (Fase 8)
        $waWebhookConfig = \Modules\ChatCenter\Models\ChatWebhookConfig::where('user_id', $userId)->first();
        $targetAgent = $aiAgents->first();

        if ($targetAgent && empty($targetAgent->plain_api_key)) {
            $plainKey = 'agentic_sk_' . \Illuminate\Support\Str::random(32);
            $targetAgent->update([
                'plain_api_key' => $plainKey,
                'api_key_hash' => hash('sha256', $plainKey),
            ]);
        }

        $receiverUrl = $targetAgent && !empty($targetAgent->plain_api_key) 
            ? url('/api/v1/agentic-hub/webhook/receiver?key=' . $targetAgent->plain_api_key) 
            : url('/api/v1/agentic-hub/webhook/receiver');
            
        $isConnectedToWaHub = $waWebhookConfig && $waWebhookConfig->is_active && str_contains($waWebhookConfig->target_url, 'webhook/receiver');

        $activeTab = $request->get('tab', 'products');

        return view('module-agentic-hub::products', compact(
            'products', 'totalProducts', 'totalPromo', 'outOfStock', 'aiAgents', 
            'globalProvider', 'fetchedModels', 'auditLogs', 'activeTab',
            'waWebhookConfig', 'receiverUrl', 'isConnectedToWaHub'
        ));
    }

    /**
     * Store new product into database
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'product_link' => 'nullable|url',
            'checkout_link' => 'nullable|url',
            'stock_status' => 'required|in:in_stock,out_of_stock,pre_order',
        ]);

        AgenticProduct::create([
            'id' => (string) Str::uuid(),
            'user_id' => auth()->id(),
            'sku' => strtoupper(trim($request->sku)),
            'name' => trim($request->name),
            'category' => $request->category ? trim($request->category) : 'Umum',
            'price' => $request->price,
            'promo_price' => $request->promo_price,
            'description' => $request->description,
            'product_link' => $request->product_link,
            'checkout_link' => $request->checkout_link,
            'stock_status' => $request->stock_status,
            'is_active' => true,
        ]);

        \Illuminate\Support\Facades\Cache::forget("agentic_catalog_text_" . auth()->id());

        return redirect()->route('modules.agentic-hub.index', ['tab' => 'products'])->with('success', 'Produk berhasil ditambahkan ke Agentic Hub!');
    }

    /**
     * Update product details
     */
    public function update(Request $request, $id)
    {
        $product = AgenticProduct::where('user_id', auth()->id())->where('id', $id)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50',
            'category' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'promo_price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'product_link' => 'nullable|url',
            'checkout_link' => 'nullable|url',
            'stock_status' => 'required|in:in_stock,out_of_stock,pre_order',
        ]);

        $product->update([
            'sku' => strtoupper(trim($request->sku)),
            'name' => trim($request->name),
            'category' => $request->category ? trim($request->category) : 'Umum',
            'price' => $request->price,
            'promo_price' => $request->promo_price,
            'description' => $request->description,
            'product_link' => $request->product_link,
            'checkout_link' => $request->checkout_link,
            'stock_status' => $request->stock_status,
        ]);

        return redirect()->route('modules.agentic-hub.index', ['tab' => 'products'])->with('success', 'Data produk berhasil diperbarui!');
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        $product = AgenticProduct::where('user_id', auth()->id())->where('id', $id)->firstOrFail();
        $product->delete();

        return redirect()->route('modules.agentic-hub.index', ['tab' => 'products'])->with('success', 'Produk berhasil dihapus dari Agentic Hub!');
    }

    /**
     * Save Global AI Model Provider Connection (Fase 3)
     */
    /**
     * Save Global AI Model Provider Connection (Fase 3)
     */
    public function updateGlobalProvider(Request $request)
    {
        $request->validate([
            'openai_base_url' => 'required|url',
            'provider_api_key' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $baseUrl = rtrim(trim($request->openai_base_url), '/');
        
        $existingAgent = AgenticAiAgent::where('user_id', $userId)->first();
        $apiKey = $request->filled('provider_api_key') 
            ? trim($request->provider_api_key) 
            : ($existingAgent ? $existingAgent->provider_api_key : null);

        // Apply Global Provider settings across all agents for this tenant
        AgenticAiAgent::where('user_id', $userId)->update([
            'openai_base_url' => $baseUrl,
            'provider_api_key' => $apiKey,
        ]);

        return redirect()->route('modules.agentic-hub.index', ['tab' => 'provider'])->with('success', 'Koneksi Single Global AI Model Provider berhasil disimpan!');
    }

    /**
     * Update AI Agent Role, Scopes, Model Selection & System Prompt (Fase 4)
     */
    public function updateAgent(Request $request, $id)
    {
        $agent = AgenticAiAgent::where('user_id', auth()->id())->where('id', $id)->firstOrFail();

        $request->validate([
            'agent_name' => 'required|string|max:255',
            'role_level' => 'required|string|in:level_1,level_2,level_3,level_4',
            'model_name' => 'nullable|string|max:100',
            'temperature' => 'required|numeric|min:0|max:2',
            'max_tokens' => 'required|integer|min:1|max:32000',
            'scopes' => 'nullable|array',
            'assigned_account' => 'nullable|string|max:100',
            'assigned_category' => 'nullable|string|max:100',
            'system_prompt' => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $agent->update([
            'agent_name' => trim($request->agent_name),
            'role_level' => $request->role_level,
            'model_name' => $request->filled('model_name') ? trim($request->model_name) : null,
            'temperature' => $request->temperature,
            'max_tokens' => $request->max_tokens,
            'scopes' => array_values($request->scopes ?? []),
            'assigned_account' => $request->assigned_account,
            'assigned_category' => $request->assigned_category,
            'system_prompt' => $request->system_prompt,
            'is_active' => (bool) $request->is_active,
        ]);

        return redirect()->route('modules.agentic-hub.index', ['tab' => 'roles'])->with('success', "Peran & Pilihan Model AI untuk '{$agent->agent_name}' berhasil disimpan!");
    }

    /**
     * Regenerate API Key for AI Agent
     */
    public function regenerateAgentApiKey($id)
    {
        $agent = AgenticAiAgent::where('user_id', auth()->id())->where('id', $id)->firstOrFail();
        $keys = AgenticAiAgent::generateApiKey();

        $agent->update([
            'plain_api_key' => $keys['plain'],
            'api_key_hash' => $keys['hash'],
        ]);

        return redirect()->route('modules.agentic-hub.index', ['tab' => 'roles'])->with('success', "API Key baru untuk AI Agent '{$agent->agent_name}' berhasil di-regenerate!");
    }

    /**
     * Fetch Live Models List from OpenAI-Compatible Endpoint (GET /models)
     */
    public function fetchModels(Request $request)
    {
        $request->validate([
            'openai_base_url' => 'required|url',
            'provider_api_key' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $baseUrl = rtrim(trim($request->openai_base_url), '/');
        
        $existingAgent = AgenticAiAgent::where('user_id', $userId)->first();
        $apiKey = $request->filled('provider_api_key') 
            ? trim($request->provider_api_key) 
            : ($existingAgent ? $existingAgent->provider_api_key : null);

        $url = $baseUrl . '/models';

        try {
            $http = \Illuminate\Support\Facades\Http::timeout(10);
            if ($apiKey) {
                $http->withToken($apiKey);
            }

            $response = $http->get($url);

            if ($response->successful()) {
                $data = $response->json();
                $models = [];

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $m) {
                        if (isset($m['id'])) {
                            $models[] = $m['id'];
                        }
                    }
                } elseif (isset($data['models']) && is_array($data['models'])) {
                    foreach ($data['models'] as $m) {
                        if (isset($m['name'])) {
                            $models[] = str_replace('models/', '', $m['name']);
                        } elseif (is_string($m)) {
                            $models[] = $m;
                        }
                    }
                }

                sort($models);
                $rawUniqueModels = array_values(array_unique($models));

                // Filter out non-text processing models (Image, Video, Audio, TTS, Embeddings)
                $uniqueModels = array_values(array_filter($rawUniqueModels, function ($modelId) {
                    $lower = strtolower($modelId);
                    $excluded = [
                        'image', 'video', 'dall-e', 'dalle', 'tts', 'whisper', 
                        'audio', 'embedding', 'embed', 'diffusion', 'flux', 
                        'stable-diffusion', 'imagen', 'sora', 'runway', 'veo'
                    ];
                    foreach ($excluded as $kw) {
                        if (str_contains($lower, $kw)) {
                            return false;
                        }
                    }
                    return true;
                }));

                // If all models were filtered, fallback to raw list so dropdown isn't empty
                if (empty($uniqueModels)) {
                    $uniqueModels = $rawUniqueModels;
                }

                // Save fetched text processing models list in cache for user to use in Phase 4 dropdowns
                Cache::put("agentic_hub_fetched_models_{$userId}", $uniqueModels, 86400);

                // Automatically update global provider credentials without overriding key if empty
                AgenticAiAgent::where('user_id', $userId)->update([
                    'openai_base_url' => $baseUrl,
                    'provider_api_key' => $apiKey,
                ]);

                return response()->json([
                    'success' => true,
                    'count' => count($uniqueModels),
                    'models' => $uniqueModels,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar model dari provider (' . $response->status() . '): ' . $response->body(),
            ], $response->status());
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan koneksi ke endpoint (' . $url . '): ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test Provider API Key & Live Chat Completion (POST /chat/completions)
     */
    public function testProviderChat(Request $request)
    {
        $request->validate([
            'openai_base_url' => 'required|url',
            'provider_api_key' => 'nullable|string',
            'model_name' => 'nullable|string',
            'prompt' => 'nullable|string',
        ]);

        $userId = auth()->id();
        $baseUrl = rtrim(trim($request->openai_base_url), '/');
        
        $existingAgent = AgenticAiAgent::where('user_id', $userId)->first();
        $apiKey = $request->filled('provider_api_key') 
            ? trim($request->provider_api_key) 
            : ($existingAgent ? $existingAgent->provider_api_key : null);

        $model = $request->filled('model_name') ? trim($request->model_name) : 'gpt-4o-mini';
        $prompt = $request->filled('prompt') ? trim($request->prompt) : 'Halo! Jawab ringkas dalam 1 kalimat bahwa kamu dari Agentic Hub siap membantu.';

        $url = $baseUrl . '/chat/completions';
        $startTime = microtime(true);

        // Fetch real database products for this user to ensure 100% database-backed accuracy without hallucination
        $dbProducts = AgenticProduct::where('user_id', $userId)->where('is_active', true)->get();

        if ($dbProducts->count() > 0) {
            $catalogText = "DATA KATALOG PRODUK REAL DI DATABASE TOKO:\n";
            foreach ($dbProducts as $p) {
                $promoText = ($p->promo_price && $p->promo_price < $p->price) ? " (Harga Promo: Rp " . number_format($p->promo_price, 0, ',', '.') . ")" : "";
                $catalogText .= "- SKU: {$p->sku} | Nama: {$p->name} | Kategori: {$p->category} | Harga Resmi: Rp " . number_format($p->price, 0, ',', '.') . "{$promoText} | Status Stok: {$p->stock_status} | Link Checkout: {$p->checkout_link}\n";
            }
        } else {
            $catalogText = "DATA KATALOG PRODUK DI DATABASE TOKO SAAT INI: KOSONG (0 PRODUK TERDAFTAR ATUA BELUM DIINPUT DI FASE 2).\n";
        }

        $systemMessage = "Kamu adalah Asisten AI Resmi Toko (Agentic Hub CS).\n" 
            . "DATA REAL DATABASE TOKO SANGAT PENTING DIBACA:\n"
            . $catalogText . "\n"
            . "ATURAN STRICT UTAMA:\n"
            . "1. JIKA DATABASE KOSONG / TIDAK ADA PRODUK YANG SESUAI, KAMU WAJIB MENJAWAB SECARA TRANSPARAN & JUJUR BAHWA STOK KOSONG ATAU BELUM TERSEDIA DI DATABASE TOKO SAAT INI.\n"
            . "2. DILARANG KERAS HALUSINASI, MENJELASKAN DETAIL WARNA/UKURAN YANG TIDAK ADA DI DATABASE, ATAU MENGARANG HARGA/PROMO SANGKAAN!\n"
            . "3. Jika produk tersedia di database di atas, sebutkan nama produk, harga resmi & promo, status stok, serta berikan Link Checkout Direct resminya.\n"
            . "4. ATURAN FORMAT CHAT WHATSAPP:\n"
            . "   - Gunakan format tebal WhatsApp *teks tebal* (SATU tanda bintang '*'), DILARANG DUA BINTANG '**'!\n"
            . "   - DILARANG MENGGUNAKAN LINK MARKDOWN '[Teks](https://...)'. SELALU TULISKAN ALAMAT URL LANGSUNG (Contoh: Link checkout: https://...).\n"
            . "   - Buat format pesan yang ramah, rapi, dan nyaman dibaca di layar chat WhatsApp HP.";

        try {
            $http = \Illuminate\Support\Facades\Http::timeout(15);
            if ($apiKey) {
                $http->withToken($apiKey);
            }

            $response = $http->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemMessage],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens' => 350,
            ]);

            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $data = $response->json();
                $reply = $data['choices'][0]['message']['content'] ?? 'Koneksi Sukses! (Tanpa balasan teks)';

                return response()->json([
                    'success' => true,
                    'status' => 200,
                    'reply' => $reply,
                    'model_used' => $model,
                    'db_count' => $dbProducts->count(),
                    'latency_ms' => $latencyMs,
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => $response->status(),
                'message' => 'Gagal memanggil AI chat completion (' . $response->status() . '): ' . $response->body(),
                'latency_ms' => $latencyMs,
            ], $response->status());
        } catch (\Throwable $e) {
            $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Kesalahan koneksi ke endpoint (' . $url . '): ' . $e->getMessage(),
                'latency_ms' => $latencyMs,
            ], 500);
        }
    }

    /**
     * Execute Interactive AI Tool Playground Simulator (Fase 6)
     */
    public function executePlayground(Request $request)
    {
        $request->validate([
            'agent_id' => 'required|string',
            'tool' => 'required|string',
            'parameters' => 'nullable|array',
        ]);

        $agent = AgenticAiAgent::where('user_id', auth()->id())->where('id', $request->agent_id)->firstOrFail();

        $apiReq = Request::create('/api/v1/agentic-hub/tools/execute', 'POST', [
            'tool' => $request->tool,
            'parameters' => $request->parameters ?? [],
        ]);

        $apiReq->headers->set('Authorization', 'Bearer ' . ($agent->plain_api_key ?? 'invalid_token'));

        $apiRes = app(AgenticToolApiController::class)->execute($apiReq);

        return response()->json([
            'status' => $apiRes->getStatusCode(),
            'agent_name' => $agent->agent_name,
            'agent_code' => $agent->agent_code,
            'data' => json_decode($apiRes->getContent(), true),
        ]);
    }

    /**
     * Ensure Default Preset AI Agents Exist for User
     */
    private function ensureDefaultAgentsExist($userId)
    {
        $existingCount = AgenticAiAgent::where('user_id', $userId)->count();
        if ($existingCount > 0) {
            return;
        }

        $presets = [
            [
                'agent_code' => 'ai_public_support',
                'agent_name' => 'Public CS & FAQ Support AI (Level 1)',
                'role_level' => 'level_1',
                'openai_base_url' => null,
                'model_name' => null,
                'temperature' => 0.70,
                'max_tokens' => 1000,
                'scopes' => ['products:read', 'checkout:read', 'faq:read'],
                'assigned_account' => 'all_accounts',
                'assigned_category' => 'all_categories',
                'system_prompt' => "Kamu adalah Asisten Layanan Pelanggan (Customer Service AI) yang ramah, sopan, dan profesional.\nTugas utamamu adalah membantu calon pembeli menemukan informasi produk, spesifikasi, harga resmi, stok, dan memberikan link checkout resmi.\n\nATURAN UTAMA:\n1. Gunakan data katalog produk resmi yang terlampir untuk memberikan harga, deskripsi, stok, dan link checkout secara presisi.\n2. DILARANG MERETUR HARGA ATAU INFORMASI PRODUK TANPA CEK DATABASE!\n3. Selalu bersikap ramah, gunakan emoticon yang hangat (😊, 🙏, 📦), dan berikan rekomendasi produk yang membantu pembeli.\n4. DILARANG PROSES ATAU MEMINTA PENYIMPANAN DATA PRIBADI PELANGGAN.",
            ],
            [
                'agent_code' => 'ai_sales_closer',
                'agent_name' => 'Sales Closing & Order Generator AI (Level 2)',
                'role_level' => 'level_2',
                'openai_base_url' => null,
                'model_name' => null,
                'temperature' => 0.70,
                'max_tokens' => 1000,
                'scopes' => ['products:read', 'checkout:read'],
                'assigned_account' => 'all_accounts',
                'assigned_category' => 'all_categories',
                'system_prompt' => "Kamu adalah Spesialis Pemasaran & Penjualan (Marketing & Sales AI) yang proaktif, ramah, dan persuasif.\nTugas utamamu adalah mempromosikan produk terbaik, memberikan penawaran promo, merespon pertanyaan calon pembeli, dan memberikan link checkout direct resmi.\n\nATURAN UTAMA:\n1. Rekomendasikan opsi produk sesuai kebutuhan calon pembeli berdasarkan data katalog resmi.\n2. Jelaskan keunggulan produk dan harga promo aktif untuk menarik minat pembeli.\n3. Berikan link checkout direct resmi agar calon pembeli dapat langsung melakukan transaksi.\n4. Gunakan format tebal WhatsApp *teks tebal* (SATU tanda bintang) dan berikan raw URL link checkout langsung.\n5. DILARANG PROSES ATAU MEMINTA PENYIMPANAN DATA PRIBADI PELANGGAN.",
            ],
            [
                'agent_code' => 'ai_inventory_manager',
                'agent_name' => 'Operational Stock & Pricing Manager AI (Level 3)',
                'role_level' => 'level_3',
                'openai_base_url' => null,
                'model_name' => null,
                'temperature' => 0.70,
                'max_tokens' => 1000,
                'scopes' => ['products:read', 'products:update_stock', 'products:write'],
                'assigned_account' => 'internal_only',
                'assigned_category' => 'all_categories',
                'system_prompt' => "Kamu adalah Manajer Operasional Stok & Promo (Marketing & Stock Assistant AI).\nTugas utamamu adalah memantau ketersediaan stok barang dan memperbarui status stok serta harga promo secara otomatis.",
            ],
            [
                'agent_code' => 'ai_super_copilot',
                'agent_name' => 'Internal Super Copilot Master AI (Level 4)',
                'role_level' => 'level_4',
                'openai_base_url' => null,
                'model_name' => null,
                'temperature' => 0.70,
                'max_tokens' => 2000,
                'scopes' => ['*:*'],
                'assigned_account' => 'all_accounts',
                'assigned_category' => 'all_categories',
                'system_prompt' => "Kamu adalah Super Copilot Master AI internal dengan otoritas penuh (Full Admin Access) untuk membantu operasional CS dan Marketing Toko.",
            ],
        ];

        foreach ($presets as $preset) {
            $keys = AgenticAiAgent::generateApiKey();
            AgenticAiAgent::create([
                'id' => (string) Str::uuid(),
                'user_id' => $userId,
                'agent_code' => $preset['agent_code'],
                'agent_name' => $preset['agent_name'],
                'role_level' => $preset['role_level'],
                'openai_base_url' => $preset['openai_base_url'],
                'model_name' => $preset['model_name'],
                'temperature' => $preset['temperature'],
                'max_tokens' => $preset['max_tokens'],
                'scopes' => $preset['scopes'],
                'assigned_account' => $preset['assigned_account'],
                'assigned_category' => $preset['assigned_category'],
                'system_prompt' => $preset['system_prompt'],
                'plain_api_key' => $keys['plain'],
                'api_key_hash' => $keys['hash'],
                'is_active' => true,
            ]);
        }
    }
}
