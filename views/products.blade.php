<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agentic Hub — AI Product & Tool Calling Engine</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen">
    <!-- Navbar / Header -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 p-0.5 flex items-center justify-center shadow-lg shadow-emerald-900/40">
                    <div class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center">
                        <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    </div>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white tracking-tight flex items-center gap-2">
                        Agentic Hub
                        <span class="text-[10px] uppercase tracking-wider font-extrabold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">AI Engine</span>
                    </h1>
                    <p class="text-xs text-slate-400">Pusat Produk, Harga & Tool Calling API Model AI</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('my-features.index') }}" class="px-3.5 py-1.5 rounded-lg border border-slate-700 bg-slate-800 text-xs font-semibold text-slate-300 hover:text-white hover:bg-slate-700 transition">
                    ← Kembali ke My Features
                </a>
            </div>
        </div>
    </header>

    <!-- Content Wrapper -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ 
        currentTab: '{{ $activeTab }}', 
        openAddModal: false, 
        editModalData: null, 
        editAgentModalData: null,
        playgroundAgent: '{{ $aiAgents->first()->id ?? '' }}',
        playgroundTool: 'search_products',
        playgroundQuery: 'kaos',
        playgroundSku: '',
        playgroundCategory: '',
        playgroundResult: null,
        playgroundLoading: false,
        uiAgentChatAgent: '{{ $aiAgents->first()->id ?? '' }}',
        uiAgentChatPrompt: 'Halo CS, apakah ada stok kaos polos promo?',
        uiAgentChatLoading: false,
        uiAgentChatResult: null,
        chatMessages: [
            {
                sender: 'ai',
                text: (function(agents, currentId) {
                    let ag = agents.find(a => a.id === currentId);
                    if (!ag) return 'Halo Kak! Selamat datang di toko kami. 👋 Ada yang bisa saya bantu?';
                    if (ag.role_level === 'level_1') return 'Halo Kak! Selamat datang di Customer Service AI (Level 1). 👋 Saya siap membantu cek stok, harga resmi, promo, dan link order toko kami.';
                    if (ag.role_level === 'level_2') return 'Halo Kak! Saya Spesialis Penjualan (Sales Closing AI Level 2). 🛍️ Mau rekomendasi produk terbaik atau promo hari ini? Nanti langsung saya berikan link checkout resminya!';
                    if (ag.role_level === 'level_3') return 'Halo Admin Operasional! Saya Manajer Stok & Harga AI (Level 3). 📦 Siap membantu memantau ketersediaan barang & status promo katalog toko.';
                    if (ag.role_level === 'level_4') return 'Halo Master! Saya Internal Super Copilot AI (Level 4) dengan otoritas penuh ke seluruh database & tools toko. Ada instruksi apa hari ini? 👑';
                    return 'Halo Kak! Ada yang bisa saya bantu tentang produk toko hari ini?';
                })({{ json_encode($aiAgents) }}, '{{ $aiAgents->first()->id ?? '' }}'),
                time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
            }
        ],
        chatInput: '',
        chatLoading: false,
        getAgentGreeting(agentId) {
            let agentObj = {{ json_encode($aiAgents) }}.find(a => a.id === agentId);
            if (!agentObj) return 'Halo Kak! Selamat datang di toko kami. 👋 Ada yang bisa saya bantu?';
            switch (agentObj.role_level) {
                case 'level_1':
                    return 'Halo Kak! Selamat datang di Customer Service AI (Level 1). 👋 Saya siap membantu cek stok, harga resmi, promo, dan link order toko kami.';
                case 'level_2':
                    return 'Halo Kak! Saya Spesialis Penjualan (Sales Closing AI Level 2). 🛍️ Mau rekomendasi produk terbaik atau promo hari ini? Nanti langsung saya berikan link checkout resminya!';
                case 'level_3':
                    return 'Halo Admin Operasional! Saya Manajer Stok & Harga AI (Level 3). 📦 Siap membantu memantau ketersediaan barang & status promo katalog toko.';
                case 'level_4':
                    return 'Halo Master! Saya Internal Super Copilot AI (Level 4) dengan otoritas penuh ke seluruh database & tools toko. Ada instruksi apa hari ini? 👑';
                default:
                    return 'Halo Kak! Ada yang bisa saya bantu tentang produk toko hari ini?';
            }
        },
        updateAgentGreeting() {
            let textStr = this.getAgentGreeting(this.uiAgentChatAgent);
            this.chatMessages = [
                {
                    sender: 'ai',
                    text: textStr,
                    time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                }
            ];
        },
        sendChatMsg() {
            if (!this.chatInput.trim()) return;
            let txt = this.chatInput.trim();
            let timeNow = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});

            this.chatMessages.push({
                sender: 'user',
                text: txt,
                time: timeNow
            });

            this.chatInput = '';
            this.chatLoading = true;

            this.$nextTick(() => {
                let box = document.getElementById('waChatThreadBox');
                if (box) box.scrollTop = box.scrollHeight;
            });

            let agentObj = {{ json_encode($aiAgents) }}.find(a => a.id === this.uiAgentChatAgent);
            let modelToUse = (agentObj && agentObj.model_name) ? agentObj.model_name : (this.fetchedModels.length > 0 ? this.fetchedModels[0] : 'gpt-4o-mini');

            fetch('/api/v1/agentic-hub/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + (agentObj ? (agentObj.plain_api_key || '') : '')
                },
                body: JSON.stringify({
                    message: txt,
                    model_name: modelToUse
                })
            })
            .then(res => res.json())
            .then(data => {
                this.chatLoading = false;
                let aiTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                if (data.success) {
                    this.chatMessages.push({
                        sender: 'ai',
                        text: data.reply,
                        time: aiTime,
                        latency_ms: data.latency_ms,
                        db_count: data.db_products_count
                    });
                } else {
                    this.chatMessages.push({
                        sender: 'ai_error',
                        text: '🔴 Error (' + (data.status || 500) + '): ' + (data.message || 'Gagal memanggil AI completion'),
                        time: aiTime
                    });
                }
                this.$nextTick(() => {
                    let box = document.getElementById('waChatThreadBox');
                    if (box) box.scrollTop = box.scrollHeight;
                });
            })
            .catch(err => {
                this.chatLoading = false;
                this.chatMessages.push({
                    sender: 'ai_error',
                    text: '🔴 Error Koneksi: ' + err.message,
                    time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                });
                this.$nextTick(() => {
                    let box = document.getElementById('waChatThreadBox');
                    if (box) box.scrollTop = box.scrollHeight;
                });
            });
        },
        clearChat() {
            this.chatMessages = [
                {
                    sender: 'ai',
                    text: 'Halo Kak! Percakapan telah di-reset. Ada yang bisa saya bantu?',
                    time: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                }
            ];
        },
        providerBaseUrl: '{{ $globalProvider['openai_base_url'] ?? '' }}',
        providerApiKey: '{{ $globalProvider['provider_api_key'] ?? '' }}',
        fetchedModels: {{ json_encode($fetchedModels) }},
        fetchLoading: false,
        fetchMessage: '',
        testChatModel: '{{ $fetchedModels[0] ?? '' }}',
        testChatPrompt: 'Halo! Jawab ringkas dalam 1 kalimat bahwa kamu dari Agentic Hub siap membantu.',
        testChatLoading: false,
        testChatResult: null,
        apiSelectedTool: 'search_products',
        doFetchModels() {
            if (!this.providerBaseUrl) {
                alert('Silakan isi OpenAI Base URL terlebih dahulu!');
                return;
            }
            this.fetchLoading = true;
            this.fetchMessage = 'Mengecek endpoint & fetching models... ⏳';
            fetch('/agentic-hub/fetch-models', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    openai_base_url: this.providerBaseUrl,
                    provider_api_key: this.providerApiKey
                })
            })
            .then(res => res.json())
            .then(data => {
                this.fetchLoading = false;
                if (data.success) {
                    this.fetchedModels = data.models;
                    this.fetchMessage = 'Berhasil mengambil ' + data.count + ' model dari provider! 🟢 Pilih model di bawah ini untuk di-test atau dialokasikan di Fase 4.';
                    if (data.models.length > 0) {
                        this.testChatModel = data.models[0];
                    }
                } else {
                    this.fetchMessage = 'Error: ' + data.message;
                }
            })
            .catch(err => {
                this.fetchLoading = false;
                this.fetchMessage = 'Koneksi gagal: ' + err.message;
            });
        },
        selectAndTestModel(m) {
            this.testChatModel = m;
            this.doTestChat();
        },
        doTestChat() {
            if (!this.providerBaseUrl) {
                alert('Silakan isi OpenAI Base URL terlebih dahulu!');
                return;
            }
            let modelToUse = this.testChatModel || (this.fetchedModels.length > 0 ? this.fetchedModels[0] : 'gpt-4o-mini');
            this.testChatLoading = true;
            this.testChatResult = null;
            fetch('/agentic-hub/provider/test-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    openai_base_url: this.providerBaseUrl,
                    provider_api_key: this.providerApiKey,
                    model_name: modelToUse,
                    prompt: this.testChatPrompt
                })
            })
            .then(res => res.json())
            .then(data => {
                this.testChatLoading = false;
                this.testChatResult = data;
            })
            .catch(err => {
                this.testChatLoading = false;
                this.testChatResult = { success: false, message: err.message };
            });
        },
        doUiAgentChat() {
            if (!this.providerBaseUrl) {
                alert('Silakan set OpenAI Base URL di Tab 3 terlebih dahulu!');
                return;
            }
            this.uiAgentChatLoading = true;
            this.uiAgentChatResult = null;
            let agentObj = {{ json_encode($aiAgents) }}.find(a => a.id === this.uiAgentChatAgent);
            let modelToUse = (agentObj && agentObj.model_name) ? agentObj.model_name : (this.fetchedModels.length > 0 ? this.fetchedModels[0] : 'gpt-4o-mini');
            fetch('/agentic-hub/provider/test-chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    openai_base_url: this.providerBaseUrl,
                    provider_api_key: this.providerApiKey,
                    model_name: modelToUse,
                    prompt: this.uiAgentChatPrompt
                })
            })
            .then(res => res.json())
            .then(data => {
                this.uiAgentChatLoading = false;
                this.uiAgentChatResult = data;
            })
            .catch(err => {
                this.uiAgentChatLoading = false;
                this.uiAgentChatResult = { success: false, message: err.message };
            });
        }
    }">
        <!-- Sub-Header Tabs -->
        <div class="flex flex-wrap items-center justify-between border-b border-slate-800 pb-4 mb-8 gap-4">
            <nav class="flex flex-wrap items-center gap-2">
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'database']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'database' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>🗄️ Fase 1: Database Setup</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'products']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'products' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>📦 Fase 2: Katalog Produk</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'provider']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'provider' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>⚙️ Fase 3: AI Model Provider</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'roles']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'roles' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>🛡️ Fase 4: AI Roles & Scopes</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'api']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'api' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>🔌 Fase 5: Tool REST API</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'playground']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'playground' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>🧪 Fase 6: UI Testing Console</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'logs']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'logs' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>📋 Fase 7: Audit Logs</span>
                </a>
                <a href="{{ route('modules.agentic-hub.index', ['tab' => 'whatsapp']) }}" class="px-3 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 transition {{ $activeTab === 'whatsapp' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-slate-900/60 text-slate-400 hover:text-white border border-slate-800' }}">
                    <span>📱 Fase 8: Integrasi WhatsApp</span>
                </a>
            </nav>

            @if($activeTab === 'products')
                <button @click="openAddModal = true" class="px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold text-xs shadow-lg shadow-emerald-900/30 hover:brightness-110 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Tambah Produk Baru
                </button>
            @endif
        </div>

        <!-- Success Alert -->
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl bg-emerald-950/60 border border-emerald-500/30 text-emerald-300 text-xs font-semibold flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- TAB 1: DATABASE & ARCHITECTURE CONSOLE (FASE 1) -->
        @if($activeTab === 'database')
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-950 border border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            🗄️ Database Setup & Multi-Tenant Architecture Console (Fase 1)
                        </h2>
                        <p class="text-xs text-slate-400">
                            Status ketersediaan skema tabel database, UUID index, dan ketersediaan fitur independen di akun kamu.
                        </p>
                    </div>
                    <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Feature Active: agentic-hub
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tabel Database Produk</span>
                        <div class="text-base font-extrabold font-mono text-emerald-400">agentic_products</div>
                        <p class="text-xs text-slate-400">Tabel katalog produk, SKU, harga resmi, promo, dan link checkout direct.</p>
                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Table Status: Ready 🟢</span>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tabel AI Agents & Credentials</span>
                        <div class="text-base font-extrabold font-mono text-emerald-400">agentic_ai_agents</div>
                        <p class="text-xs text-slate-400">Tabel penyimpan API Key SHA-256, Scopes Otoritas, & Config OpenAI-Compatible Engine.</p>
                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Table Status: Ready 🟢</span>
                    </div>

                    <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Tabel Audit Logs & Latency</span>
                        <div class="text-base font-extrabold font-mono text-emerald-400">agentic_audit_logs</div>
                        <p class="text-xs text-slate-400">Tabel pencatat riwayat panggilan API, status HTTP, dan latensi milidetik.</p>
                        <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Table Status: Ready 🟢</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 2: KATALOG PRODUK (FASE 2) -->
        @if($activeTab === 'products')
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
                <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 shadow-md">
                    <p class="text-xs font-semibold text-slate-400 mb-1">Total Produk Aktif</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-2xl font-extrabold text-white">{{ $totalProducts }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">DB Ready</span>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 shadow-md">
                    <p class="text-xs font-semibold text-slate-400 mb-1">Produk Harga Promo</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-2xl font-extrabold text-amber-400">{{ $totalPromo }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-500/10 text-amber-400 border border-amber-500/20">Active Promo</span>
                    </div>
                </div>

                <div class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 shadow-md">
                    <p class="text-xs font-semibold text-slate-400 mb-1">Out of Stock</p>
                    <div class="flex items-baseline justify-between">
                        <h3 class="text-2xl font-extrabold text-rose-400">{{ $outOfStock }}</h3>
                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-rose-500/10 text-rose-400 border border-rose-500/20">Stok Habis</span>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 mb-6">
                <form method="GET" action="{{ route('modules.agentic-hub.index') }}" class="flex flex-wrap items-center gap-4">
                    <input type="hidden" name="tab" value="products">
                    <div class="flex-1 min-w-[240px]">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama produk, SKU, atau kategori..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500">
                    </div>
                    <div class="w-48">
                        <select name="stock_status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-xs text-slate-300 focus:outline-none focus:border-emerald-500">
                            <option value="">Semua Status Stok</option>
                            <option value="in_stock" {{ request('stock_status') == 'in_stock' ? 'selected' : '' }}>In Stock 🟢</option>
                            <option value="out_of_stock" {{ request('stock_status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock 🔴</option>
                            <option value="pre_order" {{ request('stock_status') == 'pre_order' ? 'selected' : '' }}>Pre-Order 🟡</option>
                        </select>
                    </div>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-200 text-xs font-semibold hover:bg-slate-700 transition">
                        Filter
                    </button>
                </form>
            </div>

            <!-- Table Catalogue -->
            <div class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-800">
                            <tr>
                                <th class="px-6 py-4">SKU / Produk</th>
                                <th class="px-6 py-4">Kategori</th>
                                <th class="px-6 py-4">Harga Resmi</th>
                                <th class="px-6 py-4">Link Checkout Direct (1-Click Copy)</th>
                                <th class="px-6 py-4">Status Stok</th>
                                <th class="px-6 py-4 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60 text-slate-300">
                            @forelse($products as $product)
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-white text-sm">{{ $product->name }}</div>
                                        <div class="text-[11px] font-mono text-emerald-400 mt-0.5">SKU: {{ $product->sku }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2.5 py-1 rounded-md bg-slate-800 border border-slate-700 text-[11px] text-slate-300 font-medium">
                                            {{ $product->category ?? 'Umum' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($product->promo_price && $product->promo_price < $product->price)
                                            <div class="text-emerald-400 font-extrabold text-sm">Rp {{ number_format($product->promo_price, 0, ',', '.') }}</div>
                                            <div class="text-[10px] text-slate-500 line-through">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                                        @else
                                            <div class="text-white font-bold text-sm">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($product->checkout_link)
                                            <div class="flex items-center gap-2">
                                                <input type="text" readonly value="{{ $product->checkout_link }}" class="bg-slate-950 border border-slate-800 rounded-lg px-2.5 py-1 text-[11px] text-slate-400 w-48 font-mono truncate">
                                                <button onclick="navigator.clipboard.writeText('{{ $product->checkout_link }}'); alert('Link Checkout Berhasil Disalin! 📋');" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20 transition text-[11px] font-semibold flex items-center gap-1">
                                                    <span>📋 Salin</span>
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[11px] text-slate-500 italic">Belum ada link checkout</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($product->stock_status === 'in_stock')
                                            <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-semibold text-[10px]">🟢 In Stock</span>
                                        @elseif($product->stock_status === 'out_of_stock')
                                            <span class="px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 font-semibold text-[10px]">🔴 Out of Stock</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-semibold text-[10px]">🟡 Pre-Order</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button @click="editModalData = {{ json_encode($product) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 text-[11px] font-semibold transition">
                                                Edit
                                            </button>
                                            <form method="POST" action="{{ route('modules.agentic-hub.products.destroy', $product->id) }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus produk ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-rose-950/60 border border-rose-500/30 text-rose-400 hover:bg-rose-900/60 text-[11px] font-semibold transition">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        Belum ada data produk di Agentic Hub. Klik tombol **Tambah Produk Baru** di atas untuk memulai! 🚀
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($products->hasPages())
                    <div class="px-6 py-4 bg-slate-950/40 border-t border-slate-800 flex justify-between items-center">
                        {{ $products->links() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- TAB 3: SINGLE GLOBAL AI MODEL PROVIDER CONSOLE & LIVE CHAT TEST (FASE 3) -->
        @if($activeTab === 'provider')
            <div class="space-y-6">
                <!-- Header Banner Fase 3 -->
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 flex flex-wrap items-center justify-between gap-4 shadow-xl">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            ⚙️ Single Global AI Model Provider Connection (Fase 3)
                        </h2>
                        <p class="text-xs text-slate-400">
                            Alur 3 langkah mudah: Input Endpoint & Key vendor ➔ Tarik (Fetch) daftar model ➔ Uji coba (Test) respon chat AI.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs">
                        <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold">
                            1 Global Provider Active
                        </span>
                        <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 border border-slate-700 font-bold" x-text="fetchedModels.length + ' Model Tersedia'">
                        </span>
                    </div>
                </div>

                <!-- STEP 1 & STEP 2 CONTAINER -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- LANGKAH 1: CREDENTIALS INPUT FORM -->
                    <div class="lg:col-span-6 p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">1</span>
                                <span>Input Endpoint & API Key Vendor AI</span>
                            </h3>
                            <span class="text-[10px] text-slate-500">OpenAI-Compatible</span>
                        </div>

                        <form method="POST" action="{{ route('modules.agentic-hub.provider.update') }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">OpenAI-Compatible Base URL *</label>
                                <input type="url" name="openai_base_url" x-model="providerBaseUrl" required placeholder="https://api.openai.com/v1" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                                <span class="text-[10px] text-slate-500 mt-1 block">Contoh: https://api.openai.com/v1, https://api.deepseek.com/v1, https://api.groq.com/openai/v1</span>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-xs font-semibold text-slate-400">Provider API Key</label>
                                    <template x-if="providerApiKey">
                                        <span class="text-[10px] text-emerald-400 font-mono font-bold flex items-center gap-1">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Tersimpan 🟢
                                        </span>
                                    </template>
                                    <template x-if="!providerApiKey">
                                        <span class="text-[10px] text-amber-400 font-mono italic">Kosong 🟡</span>
                                    </template>
                                </div>
                                <input type="password" name="provider_api_key" x-model="providerApiKey" placeholder="sk-proj-... (Kosongkan jika tidak diubah)" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                                <span class="text-[10px] text-slate-500 mt-1 block">API Key rahasia vendor AI. Tersimpan aman & terenkripsi di database.</span>
                            </div>

                            <button type="submit" class="w-full py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition shadow-md flex items-center justify-center gap-2">
                                💾 Simpan Koneksi Global Provider
                            </button>
                        </form>
                    </div>

                    <!-- LANGKAH 2: FETCH MODELS & INTERACTIVE MODEL PILLS -->
                    <div class="lg:col-span-6 p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-md">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-xs font-bold text-teal-400 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-teal-500/10 border border-teal-500/20 text-teal-400 flex items-center justify-center text-xs">2</span>
                                <span>Fetch & Pilih Model Terdeteksi (`GET /models`)</span>
                            </h3>
                            <button type="button" @click="doFetchModels()" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs transition flex items-center gap-1.5">
                                <span x-show="!fetchLoading">🔄 Fetch Models</span>
                                <span x-show="fetchLoading">Fetching... ⏳</span>
                            </button>
                        </div>

                        <p class="text-xs text-slate-400">
                            Klik tombol <strong>Fetch Models</strong> untuk menarik daftar model aktif dari endpoint vendor kamu, lalu klik salah satu model di bawah untuk langsung mengujinya:
                        </p>

                        <!-- Notification Status Fetching -->
                        <template x-if="fetchMessage">
                            <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-slate-300" x-text="fetchMessage"></div>
                        </template>

                        <!-- Interactive Clickable Fetched Model Pills -->
                        <div class="space-y-2">
                            <span class="text-[11px] font-semibold text-slate-400 block">Klik Model di Bawah untuk Memilih & Tes Instan:</span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto p-1">
                                <template x-for="m in fetchedModels" :key="m">
                                    <button type="button" @click="selectAndTestModel(m)" class="p-2.5 rounded-xl border text-left font-mono text-xs transition flex items-center justify-between gap-2" :class="testChatModel === m ? 'bg-teal-950/80 border-teal-500 text-teal-300 ring-2 ring-teal-500/30' : 'bg-slate-950 border-slate-800 text-slate-300 hover:border-slate-700 hover:bg-slate-900'">
                                        <div class="flex items-center gap-2 truncate">
                                            <span class="w-2 h-2 rounded-full flex-shrink-0" :class="testChatModel === m ? 'bg-teal-400 ring-2 ring-teal-400/50' : 'bg-slate-600'"></span>
                                            <span class="font-bold truncate" x-text="m"></span>
                                        </div>
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-bold transition flex-shrink-0" :class="testChatModel === m ? 'bg-teal-500/20 text-teal-300 border border-teal-500/30' : 'bg-slate-800 text-slate-400'">
                                            <span x-text="testChatModel === m ? '🟢 Selected' : '⚡ Test'"></span>
                                        </span>
                                    </button>
                                </template>
                                <template x-if="fetchedModels.length === 0">
                                    <div class="col-span-2 p-4 rounded-xl bg-slate-950 border border-slate-800 text-center text-xs text-amber-400 italic">
                                        Belum ada model di-fetch. Klik tombol "🔄 Fetch Models" di atas untuk mengambil daftar model vendor kamu! 💡
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- LANGKAH 3: LIVE TEST KEY & CHAT RESPONSE SIMULATOR CONSOLE -->
                <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-5 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
                        <div>
                            <h3 class="text-xs font-bold text-emerald-400 uppercase tracking-wider flex items-center gap-2">
                                <span class="w-6 h-6 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">3</span>
                                <span>⚡ Uji Coba Chat AI & Verifikasi API Key Live (`POST /chat/completions`)</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">
                                Kirim pesan simulasi ke endpoint provider untuk memverifikasi keabsahan API Key dan respon balasan teks AI.
                            </p>
                        </div>

                        <!-- Target Selected Model Indicator Badge -->
                        <div class="flex items-center gap-2 p-2 rounded-xl bg-slate-950 border border-slate-800">
                            <span class="text-[11px] text-slate-400 font-semibold">Model Target Testing:</span>
                            <template x-if="testChatModel">
                                <span class="px-2.5 py-0.5 rounded-lg bg-teal-500/10 border border-teal-500/30 text-teal-300 font-mono font-bold text-xs" x-text="testChatModel"></span>
                            </template>
                            <template x-if="!testChatModel">
                                <span class="px-2.5 py-0.5 rounded-lg bg-amber-500/10 border border-amber-500/30 text-amber-300 font-mono text-xs italic">Pilih model di Langkah 2</span>
                            </template>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Form Testing Controls -->
                        <div class="lg:col-span-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Model yang Di-Test *</label>
                                <template x-if="fetchedModels.length > 0">
                                    <select x-model="testChatModel" class="w-full bg-slate-950 border border-teal-500/40 rounded-xl px-3.5 py-2 text-xs text-teal-300 font-mono font-bold focus:border-teal-500 focus:outline-none">
                                        <template x-for="m in fetchedModels" :key="m">
                                            <option :value="m" x-text="m" :selected="m === testChatModel"></option>
                                        </template>
                                    </select>
                                </template>
                                <template x-if="fetchedModels.length === 0">
                                    <input type="text" x-model="testChatModel" placeholder="Contoh: wk-gpt-5.4, gemini-3.5-flash-low..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                                </template>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Pesan Uji Coba Chat Prompt *</label>
                                <textarea x-model="testChatPrompt" rows="2" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none"></textarea>
                            </div>

                            <!-- Preset Quick Prompts -->
                            <div>
                                <span class="text-[10px] text-slate-500 font-semibold uppercase tracking-wider block mb-1.5">Preset Prompt 1-Click:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <button type="button" @click="testChatPrompt = 'Halo! Berikan 1 respon singkat ramah bahwa kamu dari Agentic Hub siap membantu.'" class="px-2 py-1 rounded-lg bg-slate-950 border border-slate-800 text-[10px] text-slate-300 hover:border-emerald-500/50 hover:text-white transition">
                                        💬 Sapaan Ramah
                                    </button>
                                    <button type="button" @click="testChatPrompt = 'Apakah kamu bisa memberikan rekomendasi produk kaos polos?'" class="px-2 py-1 rounded-lg bg-slate-950 border border-slate-800 text-[10px] text-slate-300 hover:border-emerald-500/50 hover:text-white transition">
                                        🛍️ Tanya Produk
                                    </button>
                                    <button type="button" @click="testChatPrompt = 'Ping tes kecepatan latensi AI.'" class="px-2 py-1 rounded-lg bg-slate-950 border border-slate-800 text-[10px] text-slate-300 hover:border-emerald-500/50 hover:text-white transition">
                                        ⚡ Tes Latensi
                                    </button>
                                </div>
                            </div>

                            <button type="button" @click="doTestChat()" class="w-full py-2.5 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-500 hover:brightness-110 text-white font-bold text-xs transition shadow-lg flex items-center justify-center gap-2">
                                <span x-show="!testChatLoading">⚡ Jalankan Uji Coba Chat AI Sekarang</span>
                                <span x-show="testChatLoading">Memanggil AI Chat completion... ⏳</span>
                            </button>
                        </div>

                        <!-- Console Results Output -->
                        <div class="lg:col-span-7 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-400">Live AI Chat Output Console:</span>
                                <template x-if="testChatResult">
                                    <div class="flex items-center gap-2 font-mono text-xs">
                                        <span class="px-2.5 py-0.5 rounded-full font-bold" :class="testChatResult.success ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'" x-text="testChatResult.success ? 'Status: 200 OK (Key & Model Valid 🟢)' : 'Status: ' + testChatResult.status + ' Error 🔴'"></span>
                                        <template x-if="testChatResult.latency_ms">
                                            <span class="text-slate-400 font-semibold" x-text="testChatResult.latency_ms + ' ms'"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 min-h-[190px] flex flex-col justify-between">
                                <template x-if="testChatLoading">
                                    <div class="text-slate-500 animate-pulse text-xs font-mono">Menghubungkan ke Base URL & mengeksekusi model chat completion... ⏳</div>
                                </template>

                                <template x-if="!testChatLoading && !testChatResult">
                                    <div class="text-slate-600 italic text-xs">Pilih model di Langkah 2 dan klik "⚡ Jalankan Uji Coba Chat AI" untuk melihat respon live di sini.</div>
                                </template>

                                <template x-if="!testChatLoading && testChatResult">
                                    <div class="space-y-3 w-full">
                                        <div class="flex justify-between items-center text-[11px] font-mono text-slate-400 border-b border-slate-800/80 pb-2">
                                            <span>Model Teruji: <strong class="text-teal-300" x-text="testChatResult.model_used || testChatModel"></strong></span>
                                            <span>Database Sync: <strong class="text-emerald-400" x-text="(testChatResult.db_count || 0) + ' Produk Real (Verified)'"></strong></span>
                                        </div>

                                        <template x-if="testChatResult.success">
                                            <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs font-mono text-emerald-300 leading-relaxed whitespace-pre-line" x-text="testChatResult.reply">
                                            </div>
                                        </template>

                                        <template x-if="!testChatResult.success">
                                            <div class="p-3 rounded-lg bg-rose-950/40 border border-rose-500/30 text-xs font-mono text-rose-300 leading-relaxed whitespace-pre-line" x-text="testChatResult.message">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 4: AI ROLES & MODEL SELECTION DROPDOWN (FASE 4) -->
        @if($activeTab === 'roles')
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-950 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            🛡️ AI Roles, Security Scopes & Model Selection Dropdown (Fase 4)
                        </h2>
                        <p class="text-xs text-slate-400">
                            Pilih model hasil <i>fetch</i> Provider (Fase 3) untuk masing-masing AI Agent dan atur granular scopes `resource:action`.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6">
                    @foreach($aiAgents as $agent)
                        <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4">
                            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-800 pb-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-white">{{ $agent->agent_name }}</h3>
                                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full {{ $agent->role_level === 'level_4' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' }}">
                                            {{ strtoupper($agent->role_level) }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] font-mono text-slate-400 mt-0.5">Agent Code: {{ $agent->agent_code }}</p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <span class="text-[10px] text-slate-400 block uppercase font-bold tracking-wider">Model AI Aktif:</span>
                                        @if($agent->model_name)
                                            <span class="font-mono text-xs text-emerald-400 font-extrabold">{{ $agent->model_name }}</span>
                                        @else
                                            <span class="text-[11px] text-amber-400 italic font-semibold">Belum Dikonfigurasi ⚠️</span>
                                        @endif
                                    </div>

                                    <button @click="editAgentModalData = {{ json_encode($agent) }}" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-xs font-bold text-white transition flex items-center gap-1.5 shadow-md">
                                        <span>⚙️ Pilih Model & Edit Role</span>
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Box 1: Granted Scopes -->
                                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Granted Scopes (`resource:action`)</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach(($agent->scopes ?? []) as $scope)
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold {{ $scope === '*:*' ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : (str_contains($scope, 'write') || str_contains($scope, 'delete') ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30') }}">
                                                {{ $scope }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Box 2: API Key Agentic Hub -->
                                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-2">
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Agentic Hub Bearer Token</span>
                                    @if($agent->plain_api_key)
                                        <div class="flex items-center gap-2">
                                            <input type="text" readonly value="{{ $agent->plain_api_key }}" class="bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1 text-[11px] font-mono text-emerald-400 flex-1 truncate">
                                            <button onclick="navigator.clipboard.writeText('{{ $agent->plain_api_key }}'); alert('API Key AI Agent Berhasil Disalin! 📋');" class="px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20 text-[11px] font-semibold">
                                                Salin
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-[11px] text-slate-500 italic">API Key terenkripsi SHA-256</span>
                                    @endif
                                    <form method="POST" action="{{ route('modules.agentic-hub.agents.regenerate-key', $agent->id) }}" onsubmit="return confirm('Regenerate API Key akan membatalkan token lama. Lanjutkan?');">
                                        @csrf
                                        <button type="submit" class="text-[10px] text-slate-400 hover:text-white underline mt-1">
                                            ↻ Regenerate API Key
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Master System Prompt Box -->
                            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block mb-2">Master Persona System Prompt</span>
                                <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-xs font-mono text-slate-300 whitespace-pre-line max-h-32 overflow-y-auto">
                                    {{ $agent->system_prompt ?? 'Belum ada system prompt' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- TAB 5: REST API TOOL CALLING ENGINE DOCUMENTATION (FASE 5) -->
        @if($activeTab === 'api')
            <div class="space-y-6">
                <!-- Header Gateway Endpoint -->
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-slate-900 to-slate-950 border border-slate-800 flex flex-wrap items-center justify-between gap-4 shadow-xl">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            🔌 REST API Engine & End-to-End AI Chat Documentation (Fase 5)
                        </h2>
                        <p class="text-xs text-slate-400">
                            Pilih jenis API Endpoint sesuai kebutuhan integrasi aplikasi kamu: Chat AI Instan (Include Balasan AI + DB Lookup) atau Direct Raw Tool Calling.
                        </p>
                    </div>
                    <div class="flex items-center gap-2 font-mono text-xs font-bold">
                        <span class="px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            2 API Endpoints Active
                        </span>
                    </div>
                </div>

                <!-- ENDPOINT 1: END-TO-END AI AGENT CHAT API (INCLUDES AI REPLY + DB LOOKUP) -->
                <div class="p-6 rounded-2xl bg-slate-900/70 border border-emerald-500/30 space-y-4 shadow-xl">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md bg-emerald-600 text-white font-mono font-extrabold text-xs">POST</span>
                            <span class="font-mono text-emerald-400 font-extrabold text-sm">/api/v1/agentic-hub/chat</span>
                        </div>
                        <span class="text-xs font-bold px-3 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            🤖 End-to-End AI Chat + DB Lookup (Sudah Termasuk Respon Balasan Teks AI)
                        </span>
                    </div>

                    <p class="text-xs text-slate-300">
                        Endpoint ini menerima pesan pembeli, secara otomatis membaca katalog produk di database MySQL toko kamu, memproses persona AI Agent & aturan format WhatsApp, lalu mengembalikan <strong>Respon Balasan Teks AI Lengkap (`reply`)</strong> dalam 1 panggilan API tunggal!
                    </p>

                    <!-- 1-Click Copy cURL Box Chat API -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">📋 cURL Command Chat AI (Direct 1-Click Copy)</span>
                            <button onclick="
                                const curlChat = document.getElementById('curlChatBox').innerText;
                                navigator.clipboard.writeText(curlChat);
                                alert('Perintah cURL End-to-End AI Chat Berhasil Disalin! 📋');
                            " class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-md">
                                <span>📋 Salin cURL Chat AI</span>
                            </button>
                        </div>

                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-300 overflow-x-auto leading-relaxed" id="curlChatBox">
                            curl -X POST https://member.wakdondin.my.id/api/v1/agentic-hub/chat \<br/>
                            &nbsp;&nbsp;-H "Authorization: Bearer KEY_AKSES_ROLE_AGENT" \<br/>
                            &nbsp;&nbsp;-H "Content-Type: application/json" \<br/>
                            &nbsp;&nbsp;-d '{<br/>
                            &nbsp;&nbsp;&nbsp;&nbsp;"message": "Halo CS, apakah ada stok kaos polos promo?"<br/>
                            &nbsp;&nbsp;}'
                        </div>

                        <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-xs text-slate-300 flex items-center gap-2">
                            <span class="text-amber-400 font-bold text-sm">💡</span>
                            <div>
                                <strong class="text-white">Petunjuk Key Akses Role:</strong> Ganti <code class="text-emerald-400 font-mono font-bold">KEY_AKSES_ROLE_AGENT</code> di atas dengan API Key milik AI Agent Role kamu (Bisa disalin di <strong>Tab 4: AI Roles & Scopes</strong>).
                            </div>
                        </div>
                    </div>

                    <!-- JSON Response Spec Chat API -->
                    <div class="space-y-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">🟢 Contoh JSON Respon Output Balasan AI (200 OK)</span>
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-emerald-300 overflow-x-auto leading-relaxed">
                            <pre>{
  "success": true,
  "status": 200,
  "agent": {
    "id": "{{ $aiAgents->first()->id ?? 'uuid' }}",
    "name": "{{ $aiAgents->first()->agent_name ?? 'Public CS Support AI' }}",
    "code": "{{ $aiAgents->first()->agent_code ?? 'ai_public_support' }}",
    "role_level": "{{ $aiAgents->first()->role_level ?? 'level_1' }}"
  },
  "reply": "Halo Kak! Mohon maaf sekali, saat ini produk *kaos polos* belum tersedia di toko kami.\n\nSaat ini produk yang ready:\n- *RDP Linux dan Windows* (Harga Promo Rp 5.000)\nLink Checkout: https://lynk.id/itretceh/nk8m7zwxr5lg",
  "db_products_count": 1,
  "model_used": "wk-gpt-5.4",
  "latency_ms": 1250.45
}</pre>
                        </div>
                    </div>
                </div>

                <!-- ENDPOINT 2: DIRECT TOOL CALLING API GATEWAY -->
                <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 rounded-md bg-teal-600 text-white font-mono font-extrabold text-xs">POST</span>
                            <span class="font-mono text-teal-400 font-extrabold text-sm">/api/v1/agentic-hub/tools/execute</span>
                        </div>
                        <span class="text-xs font-bold px-3 py-0.5 rounded-full bg-teal-500/10 text-teal-400 border border-teal-500/20">
                            🛠️ Direct Raw Tool Calling API (Custom N8N / Flowise Workflows)
                        </span>
                    </div>

                <!-- 1-CLICK COPY cURL BOX -->
                <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-3 shadow-lg">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">📋 Contoh Perintah cURL Lengkap (Direct 1-Click Copy)</span>
                        <button onclick="
                            const curlTxt = document.getElementById('curlCodeBox').innerText;
                            navigator.clipboard.writeText(curlTxt);
                            alert('Perintah cURL Berhasil Disalin ke Clipboard! 📋');
                        " class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition flex items-center gap-1.5 shadow-md">
                            <span>📋 Salin Perintah cURL</span>
                        </button>
                    </div>

                    <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-300 overflow-x-auto leading-relaxed" id="curlCodeBox">
                        curl -X POST https://member.wakdondin.my.id/api/v1/agentic-hub/tools/execute \<br/>
                        &nbsp;&nbsp;-H "Authorization: Bearer KEY_AKSES_ROLE_AGENT" \<br/>
                        &nbsp;&nbsp;-H "Content-Type: application/json" \<br/>
                        &nbsp;&nbsp;-d '{<br/>
                        &nbsp;&nbsp;&nbsp;&nbsp;"tool": "search_products",<br/>
                        &nbsp;&nbsp;&nbsp;&nbsp;"parameters": {<br/>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"query": "rdp",<br/>
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"category": "Teknologi"<br/>
                        &nbsp;&nbsp;&nbsp;&nbsp;}<br/>
                        &nbsp;&nbsp;}'
                    </div>
                </div>

                <!-- INTERACTIVE TOOL SCHEMAS SPECIFICATION -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Tool Selection Navigation -->
                    <div class="lg:col-span-4 p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4">
                        <span class="text-xs font-bold text-white border-b border-slate-800 pb-3 block">🛠️ Tiga (3) Tool Functions Utama:</span>
                        
                        <div class="space-y-2">
                            <button type="button" @click="apiSelectedTool = 'search_products'" class="w-full p-3 rounded-xl border text-left transition font-mono text-xs flex items-center justify-between" :class="apiSelectedTool === 'search_products' ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300 font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'">
                                <span>1. search_products</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Katalog</span>
                            </button>

                            <button type="button" @click="apiSelectedTool = 'get_product_details'" class="w-full p-3 rounded-xl border text-left transition font-mono text-xs flex items-center justify-between" :class="apiSelectedTool === 'get_product_details' ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300 font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'">
                                <span>2. get_product_details</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-teal-500/10 text-teal-400 border border-teal-500/20">Detail SKU</span>
                            </button>

                            <button type="button" @click="apiSelectedTool = 'get_product_checkout_link'" class="w-full p-3 rounded-xl border text-left transition font-mono text-xs flex items-center justify-between" :class="apiSelectedTool === 'get_product_checkout_link' ? 'bg-emerald-950/80 border-emerald-500 text-emerald-300 font-bold' : 'bg-slate-950 border-slate-800 text-slate-400 hover:text-slate-200'">
                                <span>3. get_product_checkout_link</span>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-amber-500/10 text-amber-400 border border-amber-500/20">Checkout Link</span>
                            </button>
                        </div>
                    </div>

                    <!-- JSON Schema & Specification Console -->
                    <div class="lg:col-span-8 p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4">
                        <template x-if="apiSelectedTool === 'search_products'">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                                    <h4 class="text-sm font-bold text-emerald-400 font-mono">search_products(query, category)</h4>
                                    <span class="text-[10px] text-slate-400 font-mono">Scope: products:read</span>
                                </div>
                                <p class="text-xs text-slate-300">
                                    Mencari katalog produk toko, menampilkan SKU, harga normal, harga promo, status stok, dan link checkout direct.
                                </p>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-emerald-300">
                                    <strong>Payload Request Body:</strong>
                                    <pre class="mt-1 text-[11px] text-slate-300">
{
  "tool": "search_products",
  "parameters": {
    "query": "kaos polos",
    "category": "Pakaian"
  }
}</pre>
                                </div>
                            </div>
                        </template>

                        <template x-if="apiSelectedTool === 'get_product_details'">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                                    <h4 class="text-sm font-bold text-teal-400 font-mono">get_product_details(sku)</h4>
                                    <span class="text-[10px] text-slate-400 font-mono">Scope: products:read</span>
                                </div>
                                <p class="text-xs text-slate-300">
                                    Mengambil rincian detail produk pasti berdasarkan kode SKU resmi.
                                </p>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-teal-300">
                                    <strong>Payload Request Body:</strong>
                                    <pre class="mt-1 text-[11px] text-slate-300">
{
  "tool": "get_product_details",
  "parameters": {
    "sku": "KAOS-BLK-L"
  }
}</pre>
                                </div>
                            </div>
                        </template>

                        <template x-if="apiSelectedTool === 'get_product_checkout_link'">
                            <div class="space-y-3">
                                <div class="flex justify-between items-center border-b border-slate-800 pb-2">
                                    <h4 class="text-sm font-bold text-amber-400 font-mono">get_product_checkout_link(sku, query)</h4>
                                    <span class="text-[10px] text-slate-400 font-mono">Scope: checkout:read</span>
                                </div>
                                <p class="text-xs text-slate-300">
                                    Mengambil link checkout direct resmi untuk langsung dikirimkan ke pembeli.
                                </p>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-amber-300">
                                    <strong>Payload Request Body:</strong>
                                    <pre class="mt-1 text-[11px] text-slate-300">
{
  "tool": "get_product_checkout_link",
  "parameters": {
    "sku": "KAOS-BLK-L",
    "query": "link checkout"
  }
}</pre>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- RESPONSE SCHEMAS & STATUS CODES -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- HTTP 200 OK Response -->
                    <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">Status 200 OK (Eksekusi Sukses)</span>
                            <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-mono text-[10px] font-bold">200 OK</span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-emerald-300 overflow-x-auto">
                            <pre>{
  "success": true,
  "status": 200,
  "tool": "search_products",
  "data": {
    "total": 1,
    "products": [
      {
        "sku": "RDP-WIN-01",
        "name": "RDP Linux dan Windows",
        "price": 30000,
        "promo_price": 5000,
        "stock_status": "in_stock",
        "checkout_link": "https://lynk.id/itretceh/..."
      }
    ]
  },
  "latency_ms": 12.4
}</pre>
                        </div>
                    </div>

                    <!-- HTTP 403 Forbidden Response -->
                    <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-2">
                            <span class="text-xs font-bold text-rose-400 uppercase tracking-wider block">Status 403 Forbidden (Proteksi Gatekeeper)</span>
                            <span class="px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20 font-mono text-[10px] font-bold">403 Forbidden</span>
                        </div>
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-[11px] text-rose-300 overflow-x-auto">
                            <pre>{
  "success": false,
  "status": 403,
  "message": "AI Agent 'ai_public_support' tidak memiliki scope 'products:write' untuk mengeksekusi tool 'update_product_price'."
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 6: INTERACTIVE UI TESTING CONSOLE SUITE (FASE 6) -->
        @if($activeTab === 'playground')
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-950 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            🧪 Interactive UI Testing Console & Simulator Suite (Fase 6)
                        </h2>
                        <p class="text-xs text-slate-400">
                            Uji coba komprehensif dari Dashboard UI untuk memverifikasi Tool Calling REST API, proteksi Gatekeeper 403, dan respon AI Chat secara live.
                        </p>
                    </div>
                    <span class="text-xs font-mono font-bold px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Full UI Testing Suite Active
                    </span>
                </div>

                <!-- TEST SUITE 1: TOOL CALLING & GATEKEEPER 403 TESTER -->
                <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
                    <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-3 flex items-center justify-between">
                        <span>1. 🛠️ Tool Calling & Gatekeeper HTTP 403 Tester</span>
                        <span class="text-[10px] font-mono text-slate-400">Pintu Utama REST API Simulator</span>
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Form Input Simulator -->
                        <div class="lg:col-span-5 space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Pilih AI Agent (Bearer Token) *</label>
                                <select x-model="playgroundAgent" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    @foreach($aiAgents as $ag)
                                        <option value="{{ $ag->id }}">{{ $ag->agent_name }} ({{ $ag->agent_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Pilih Tool Function *</label>
                                <select x-model="playgroundTool" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    <option value="search_products">search_products(query, category)</option>
                                    <option value="get_product_details">get_product_details(sku)</option>
                                    <option value="get_product_checkout_link">get_product_checkout_link(sku, query)</option>
                                    <option value="update_product_price">update_product_price(sku, price) — Test 403 Gatekeeper</option>
                                </select>
                            </div>

                            <!-- Parameter Input Dynamic -->
                            <template x-if="playgroundTool === 'search_products'">
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-400 mb-1">Query Pencarian</label>
                                        <input type="text" x-model="playgroundQuery" placeholder="kaos, promo, hitam..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-400 mb-1">Kategori (Opsional)</label>
                                        <input type="text" x-model="playgroundCategory" placeholder="Pakaian" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    </div>
                                </div>
                            </template>

                            <template x-if="playgroundTool === 'get_product_details' || playgroundTool === 'get_product_checkout_link' || playgroundTool === 'update_product_price'">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">SKU Produk *</label>
                                    <input type="text" x-model="playgroundSku" placeholder="misal: TEST-KAOS-01" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                                </div>
                            </template>

                            <button @click="
                                playgroundLoading = true;
                                let params = {};
                                if (playgroundTool === 'search_products') {
                                    params = { query: playgroundQuery, category: playgroundCategory };
                                } else if (playgroundTool === 'get_product_details' || playgroundTool === 'get_product_checkout_link') {
                                    params = { sku: playgroundSku, query: playgroundQuery };
                                } else if (playgroundTool === 'update_product_price') {
                                    params = { sku: playgroundSku, price: 999000 };
                                }
                                fetch('/agentic-hub/playground/execute', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({
                                        agent_id: playgroundAgent,
                                        tool: playgroundTool,
                                        parameters: params
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    playgroundResult = data;
                                    playgroundLoading = false;
                                })
                                .catch(err => {
                                    playgroundResult = { error: err.message };
                                    playgroundLoading = false;
                                });
                            " class="w-full py-2.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-500 text-white font-bold text-xs shadow-lg hover:brightness-110 transition flex items-center justify-center gap-2">
                                <span x-show="!playgroundLoading">🚀 Eksekusi UI Tool Test</span>
                                <span x-show="playgroundLoading">Memproses Execution...</span>
                            </button>
                        </div>

                        <!-- Visualisasi JSON Response Output -->
                        <div class="lg:col-span-7 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-slate-400">Output JSON Respon:</span>
                                <template x-if="playgroundResult">
                                    <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-full" :class="playgroundResult.status === 200 ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'" x-text="'HTTP Status: ' + playgroundResult.status"></span>
                                </template>
                            </div>

                            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 font-mono text-xs text-slate-300 min-h-[220px] max-h-[350px] overflow-y-auto">
                                <template x-if="playgroundLoading">
                                    <div class="text-slate-500 animate-pulse">Sedang mengeksekusi Pintu Utama REST API... ⏳</div>
                                </template>
                                <template x-if="!playgroundLoading && !playgroundResult">
                                    <div class="text-slate-600 italic">Hasil eksekusi JSON respon akan tampil di sini setelah tombol diklik.</div>
                                </template>
                                <template x-if="!playgroundLoading && playgroundResult">
                                    <pre x-text="JSON.stringify(playgroundResult, null, 2)" class="whitespace-pre-wrap"></pre>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TEST SUITE 2: INTERACTIVE WHATSAPP CHAT SIMULATOR -->
                <div class="p-6 rounded-2xl bg-slate-900/70 border border-slate-800 space-y-4 shadow-xl">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div>
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span>💬 Interactive WhatsApp Chat Simulator (Level 1-4 AI Agent Test)</span>
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 text-[10px] font-mono font-bold">WhatsApp UI Active 🟢</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Uji coba simulasi antarmuka percakapan WhatsApp HP pelanggan secara real-time.</p>
                        </div>
                        <button type="button" @click="clearChat()" class="px-3 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold transition">
                            🗑️ Reset Chat
                        </button>
                    </div>

                    <!-- WHATSAPP PHONE MOCKUP CONTAINER -->
                    <div class="max-w-3xl mx-auto rounded-2xl border border-emerald-500/30 overflow-hidden shadow-2xl bg-[#0b141a]">
                        <!-- WhatsApp Header Bar -->
                        <div class="bg-[#202c33] px-4 py-3 border-b border-slate-800 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="relative">
                                    <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center text-white font-bold text-sm shadow">
                                        🤖
                                    </div>
                                    <span class="w-3 h-3 rounded-full bg-emerald-400 border-2 border-[#202c33] absolute bottom-0 right-0"></span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="text-xs font-bold text-white">Agentic CS AI Support</h4>
                                        <span class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Official Shop AI</span>
                                    </div>
                                    <span class="text-[10px] text-emerald-400 font-mono flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Online • CS AI Agentic Hub
                                    </span>
                                </div>
                            </div>

                            <!-- Selector AI Agent Dropdown in Header -->
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] text-slate-400 font-semibold">Pilih Agent:</span>
                                <select x-model="uiAgentChatAgent" @change="updateAgentGreeting()" class="bg-slate-900 border border-emerald-500/40 rounded-xl px-3 py-1 text-xs text-emerald-300 font-bold focus:outline-none">
                                    @foreach($aiAgents as $ag)
                                        <option value="{{ $ag->id }}">{{ $ag->agent_name }} ({{ strtoupper($ag->role_level) }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- WhatsApp Thread Messages Box -->
                        <div class="p-4 h-[380px] overflow-y-auto space-y-3 bg-[#0b141a] bg-opacity-95" id="waChatThreadBox" style="background-image: radial-gradient(#1f2c34 1px, transparent 1px); background-size: 16px 16px;">
                            <div class="text-center my-2">
                                <span class="px-3 py-1 rounded-lg bg-[#182229] border border-slate-800 text-[10px] font-mono text-slate-400">
                                    🔒 Pesan terenkripsi & terhubung langsung ke Database Toko MySQL
                                </span>
                            </div>

                            <template x-for="(msg, index) in chatMessages" :key="index">
                                <div>
                                    <!-- User Bubble (Outbound - Green Right) -->
                                    <template x-if="msg.sender === 'user'">
                                        <div class="flex justify-end mb-2">
                                            <div class="max-w-[80%] rounded-2xl rounded-tr-none bg-[#005c4b] p-3 text-xs text-slate-100 shadow-md">
                                                <div class="whitespace-pre-line leading-relaxed" x-text="msg.text"></div>
                                                <div class="flex items-center justify-end gap-1 text-[9px] text-emerald-200 mt-1 font-mono">
                                                    <span x-text="msg.time"></span>
                                                    <span class="text-emerald-300 font-bold">✓✓</span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- AI Bubble (Inbound - Dark Left) -->
                                    <template x-if="msg.sender === 'ai'">
                                        <div class="flex justify-start mb-2">
                                            <div class="max-w-[85%] rounded-2xl rounded-tl-none bg-[#202c33] border border-slate-800 p-3 text-xs text-slate-200 shadow-md">
                                                <div class="whitespace-pre-line leading-relaxed text-emerald-300" x-text="msg.text"></div>
                                                <div class="flex items-center justify-between border-t border-slate-800/80 pt-1.5 mt-2 text-[9px] font-mono text-slate-400 gap-4">
                                                    <span class="text-emerald-400 font-bold" x-text="'🟢 DB Sync (' + (msg.db_count || 0) + ' Produk)'"></span>
                                                    <div class="flex items-center gap-2">
                                                        <template x-if="msg.latency_ms">
                                                            <span x-text="msg.latency_ms + ' ms'"></span>
                                                        </template>
                                                        <span x-text="msg.time"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- AI Error Bubble -->
                                    <template x-if="msg.sender === 'ai_error'">
                                        <div class="flex justify-start mb-2">
                                            <div class="max-w-[85%] rounded-2xl rounded-tl-none bg-rose-950/60 border border-rose-500/30 p-3 text-xs text-rose-300 shadow-md">
                                                <div class="whitespace-pre-line leading-relaxed" x-text="msg.text"></div>
                                                <div class="text-right text-[9px] font-mono text-rose-400 mt-1" x-text="msg.time"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- Loading Typing Indicator -->
                            <template x-if="chatLoading">
                                <div class="flex justify-start mb-2">
                                    <div class="rounded-2xl rounded-tl-none bg-[#202c33] border border-slate-800 px-4 py-2.5 text-xs text-emerald-400 font-mono flex items-center gap-2 shadow">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                        <span>AI Agent sedang membaca database toko & menulis balasan... 💬</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- WhatsApp Quick Presets & Input Bar -->
                        <div class="bg-[#202c33] p-3 border-t border-slate-800 space-y-2">
                            <!-- Preset Buttons -->
                            <div class="flex flex-wrap gap-1.5">
                                <button type="button" @click="chatInput = 'Halo CS, apakah ada stok kaos polos promo?'" class="px-2.5 py-1 rounded-full bg-[#111b21] border border-slate-700 text-[10px] text-slate-300 hover:border-emerald-500 hover:text-white transition">
                                    💬 Tanya Kaos Polos
                                </button>
                                <button type="button" @click="chatInput = 'Apakah ada RDP promo ready hari ini?'" class="px-2.5 py-1 rounded-full bg-[#111b21] border border-slate-700 text-[10px] text-slate-300 hover:border-emerald-500 hover:text-white transition">
                                    🖥️ Tanya Promo RDP
                                </button>
                                <button type="button" @click="chatInput = 'Minta link checkout direct untuk RDP promo.'" class="px-2.5 py-1 rounded-full bg-[#111b21] border border-slate-700 text-[10px] text-slate-300 hover:border-emerald-500 hover:text-white transition">
                                    💳 Minta Link Checkout
                                </button>
                            </div>

                            <!-- Input Toolbar -->
                            <form @submit.prevent="sendChatMsg()" class="flex items-center gap-2">
                                <input type="text" x-model="chatInput" placeholder="Ketik pesan pembeli ke WhatsApp AI Agent..." class="flex-1 bg-[#111b21] border border-slate-700 rounded-full px-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 font-sans">
                                
                                <button type="submit" :disabled="chatLoading || !chatInput.trim()" class="w-9 h-9 rounded-full bg-[#00a884] hover:bg-[#008f70] text-white flex items-center justify-center transition shadow-lg disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-4 h-4 transform rotate-90" fill="currentColor" viewBox="0 0 20 20"><path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- TAB 7: AUDIT LOGS CONSOLE & ANALYTICS (FASE 7) -->
        @if($activeTab === 'logs')
            <div class="space-y-6">
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-950 border border-slate-800 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white mb-1 flex items-center gap-2">
                            📋 Audit Logs & API Usage Analytics (Fase 7)
                        </h2>
                        <p class="text-xs text-slate-400">
                            Catatan riwayat real-time setiap kali AI Model mengeksekusi Tool Calling API di Agentic Hub.
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden shadow-xl">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-950/80 text-slate-400 uppercase tracking-wider text-[10px] border-b border-slate-800">
                                <tr>
                                    <th class="px-6 py-4">Waktu</th>
                                    <th class="px-6 py-4">AI Agent</th>
                                    <th class="px-6 py-4">Nama Tool</th>
                                    <th class="px-6 py-4">Parameter Input</th>
                                    <th class="px-6 py-4">HTTP Status</th>
                                    <th class="px-6 py-4 text-right">Latensi (ms)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60 text-slate-300">
                                @forelse($auditLogs as $log)
                                    <tr class="hover:bg-slate-800/40 transition">
                                        <td class="px-6 py-4 font-mono text-[11px] text-slate-400">
                                            {{ $log->created_at->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-white">
                                            {{ $log->agent_code ?? 'System API' }}
                                        </td>
                                        <td class="px-6 py-4 font-mono font-bold text-emerald-400">
                                            {{ $log->tool_name }}
                                        </td>
                                        <td class="px-6 py-4 font-mono text-[10px] text-slate-400 max-w-xs truncate">
                                            {{ json_encode($log->parameters) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($log->status_code === 200)
                                                <span class="px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 font-bold text-[10px]">200 OK</span>
                                            @elseif($log->status_code === 403)
                                                <span class="px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-rose-400 font-bold text-[10px]">403 Forbidden</span>
                                            @else
                                                <span class="px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-amber-400 font-bold text-[10px]">{{ $log->status_code }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right font-mono text-slate-200">
                                            {{ number_format($log->latency_ms, 2) }} ms
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                            Belum ada catatan audit log eksekusi tool calling. Panggil API untuk melihat log real-time!
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($auditLogs->hasPages())
                        <div class="px-6 py-4 bg-slate-950/40 border-t border-slate-800 flex justify-between items-center">
                            {{ $auditLogs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- TAB 8: INTEGRASI WHATSAPP WEBHOOK (FASE 8) -->
        @if($activeTab === 'whatsapp')
            <div class="space-y-8">
                <!-- Header Banner -->
                <div class="p-6 rounded-2xl bg-gradient-to-r from-slate-900 via-emerald-950/40 to-slate-900 border border-emerald-500/30 shadow-2xl relative overflow-hidden">
                    <div class="absolute -right-6 -bottom-6 w-32 h-32 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 font-extrabold text-[10px] tracking-wider uppercase border border-emerald-500/30">
                                    Fase 8 Integration Live
                                </span>
                                <span class="text-xs text-slate-400 font-mono">Fonnte Webhook Receiver Standard</span>
                            </div>
                            <h2 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                                <span>📱 Integrasi Webhook WhatsApp & External Platforms</span>
                            </h2>
                            <p class="text-xs text-slate-400 mt-1 max-w-2xl">
                                Hubungkan <strong>WA Hub (`wa-hub`)</strong> maupun platform pihak ketiga (Fonnte Dashboard, n8n, Make.com, Typebot, Script PHP Kustom) langsung ke <strong>Agentic Hub AI Receiver</strong> untuk menjawab chat pembeli berbasis katalog produk real toko Anda.
                            </p>
                        </div>

                        <!-- Integration Connection Status Badge & Action -->
                        <div class="flex items-center gap-3 bg-slate-950/80 p-3 rounded-xl border border-slate-800">
                            @if($isConnectedToWaHub)
                                <div class="flex items-center gap-2 text-xs font-bold text-emerald-400">
                                    <span class="relative flex h-3 w-3">
                                      <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                      <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                                    </span>
                                    <span>Terhubung ke WA Hub</span>
                                </div>
                            @else
                                <div class="flex items-center gap-2 text-xs font-bold text-amber-400">
                                    <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                                    <span>Belum Terhubung ke WA Hub</span>
                                </div>
                                <form action="{{ route('modules.wa-hub.settings.connect-agentic-hub') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-lg shadow-md transition flex items-center gap-1.5 whitespace-nowrap">
                                        <i class="fa-solid fa-bolt"></i> 1-Click Connect
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Main Setup Cards Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- CARD 1: WEBHOOK RECEIVER ENDPOINT DETAILS -->
                    <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-emerald-500/20 text-emerald-400">🔗</span>
                                <span>URL Endpoint Webhook Receiver</span>
                            </h3>
                            <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-slate-400 font-mono">POST Request</span>
                        </div>

                        <p class="text-xs text-slate-400">
                            Gunakan Webhook URL resmi berikut untuk ditembakkan dari halaman setelan Webhook WA Hub (`/wa-hub/settings`) maupun gateway Fonnte eksternal:
                        </p>

                        <!-- Copyable URL Box -->
                        <div class="space-y-2">
                            <label class="block text-[11px] font-semibold text-slate-300">Fonnte Webhook Receiver Target URL:</label>
                            <div class="flex items-center gap-2 bg-slate-950 p-3 rounded-xl border border-slate-800 font-mono text-xs text-emerald-300">
                                <span class="flex-1 truncate select-all">{{ $receiverUrl }}</span>
                                <button type="button" @click="copyToClipboard('{{ $receiverUrl }}')"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg transition flex items-center gap-1 text-[11px]">
                                    <i class="fa-regular fa-copy"></i> <span x-text="copiedKey ? 'Tersalin!' : 'Salin URL'"></span>
                                </button>
                            </div>
                        </div>

                        <!-- Support Badge Icons -->
                        <div class="pt-2 border-t border-slate-800/80">
                            <span class="text-[11px] text-slate-400 block mb-2 font-medium">Platform & Compatibility Teruji:</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 text-[11px] font-medium flex items-center gap-1.5">
                                    <i class="fa-brands fa-whatsapp text-emerald-400"></i> WhatsApp Hub (WA-Hub)
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 text-[11px] font-medium flex items-center gap-1.5">
                                    <i class="fa-solid fa-plug text-cyan-400"></i> Standar Fonnte Webhook
                                </span>
                                <span class="px-2.5 py-1 rounded-lg bg-slate-950 border border-slate-800 text-slate-300 text-[11px] font-medium flex items-center gap-1.5">
                                    <i class="fa-solid fa-diagram-project text-amber-400"></i> n8n / Flowise / Make
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- CARD 2: ACTIVE AI AGENT CONFIGURATION -->
                    <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                                <span class="p-1.5 rounded-lg bg-cyan-500/20 text-cyan-400">🤖</span>
                                <span>AI Agent Petugas CS & Marketing</span>
                            </h3>
                            <a href="{{ route('modules.agentic-hub.index', ['tab' => 'roles']) }}" class="text-[11px] text-emerald-400 hover:underline">
                                Edit Prompt & Model di Tab 4 →
                            </a>
                        </div>

                        @php $activeAgent = $aiAgents->first(); @endphp
                        @if($activeAgent)
                            <div class="p-4 bg-slate-950 rounded-xl border border-slate-800 space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                        <h4 class="text-xs font-bold text-white">{{ $activeAgent->agent_name }}</h4>
                                        <span class="px-2 py-0.5 rounded bg-slate-800 text-[10px] text-slate-400 font-mono">{{ $activeAgent->agent_code }}</span>
                                    </div>
                                    <span class="px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 font-bold text-[10px] border border-emerald-500/20">
                                        Model: {{ $activeAgent->model_name ?: 'gpt-4o-mini' }}
                                    </span>
                                </div>

                                <p class="text-[11px] text-slate-400 line-clamp-2 italic">
                                    "{{ Str::limit($activeAgent->system_prompt, 120) }}"
                                </p>

                                <div class="pt-2 border-t border-slate-900 flex items-center justify-between text-[10px] text-slate-500">
                                    <span>Katalog Produk Terhubung: <strong class="text-emerald-400 font-mono">{{ $totalProducts }} Produk</strong></span>
                                    <span>Max Tokens: {{ $activeAgent->max_tokens }}</span>
                                </div>
                            </div>
                        @else
                            <div class="p-4 bg-rose-500/10 border border-rose-500/20 rounded-xl text-rose-300 text-xs">
                                Belum ada AI Agent aktif. Harap buat minimal 1 AI Agent di Tab 4!
                            </div>
                        @endif
                    </div>
                </div>

                <!-- CARD 3: FONNTE WEBHOOK SPECIFICATION & PAYLOAD EXAMPLES -->
                <div class="p-6 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-xl space-y-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                        <span class="p-1.5 rounded-lg bg-purple-500/20 text-purple-400">📄</span>
                        <span>Struktur Payload Fonnte Webhook Inbound & Synchronous Response</span>
                    </h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 text-xs font-mono">
                        <div>
                            <span class="text-slate-400 font-bold block mb-1">Inbound POST Request Body (Fonnte Standard):</span>
                            <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-slate-300 text-[11px] overflow-x-auto">
{
  "device": "628999999999",
  "sender": "6281234567890",
  "message": "Kaos hitam L promo berapa min?",
  "name": "Budi Santoso",
  "url": null,
  "timestamp": 1785506400,
  "inboxid": "7a3ffb2e-1c9d-4e86-9a11..."
}</pre>
                        </div>
                        <div>
                            <span class="text-emerald-400 font-bold block mb-1">Synchronous JSON Response Body (HTTP 200):</span>
                            <pre class="bg-slate-950 p-4 rounded-xl border border-slate-800 text-emerald-300 text-[11px] overflow-x-auto">
{
  "status": true,
  "reply": "Halo Kak Budi! Kaos Oversize Hitam L harganya promo Rp 99.000 (Harga normal Rp 125.000) 😊. Order via: https://wakdondin.com/checkout/kaos-hitam",
  "message": "Halo Kak Budi! Kaos Oversize Hitam L...",
  "latency_ms": 412.5
}</pre>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- MODAL EDIT AI AGENT ROLES, SCOPES & MODEL SELECTION DROPDOWN (FASE 4) -->
        <div x-show="editAgentModalData" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-2xl p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto" @click.away="editAgentModalData = null">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit AI Agent Role, Model & Scopes (Fase 4)</h3>
                    <button @click="editAgentModalData = null" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <template x-if="editAgentModalData">
                    <form method="POST" :action="'/agentic-hub/agents/' + editAgentModalData.id" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- Section: Identitas & Level -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Nama AI Agent *</label>
                                <input type="text" name="agent_name" required :value="editAgentModalData.agent_name" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Level Otoritas AI *</label>
                                <select name="role_level" required :value="editAgentModalData.role_level" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    <option value="level_1">Level 1 (Answer & Link Only)</option>
                                    <option value="level_2">Level 2 (Sales & Lead Creator)</option>
                                    <option value="level_3">Level 3 (Operational Manager)</option>
                                    <option value="level_4">Level 4 (Super Copilot)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section: Model Selection Dropdown (Derived from Live Fetched Models in Phase 3) -->
                        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800/90 space-y-3">
                            <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider block">🤖 Dropdown Pilihan Model AI (Dari Hasil Fetch Provider)</span>

                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Pilih Model AI *</label>
                                    <template x-if="fetchedModels.length > 0">
                                        <select name="model_name" :value="editAgentModalData.model_name" class="w-full bg-slate-900 border border-emerald-500/50 rounded-xl px-3 py-2 text-xs text-emerald-300 font-mono focus:border-emerald-500 focus:outline-none">
                                            <option value="">-- Pilih Model AI --</option>
                                            <template x-for="m in fetchedModels" :key="m">
                                                <option :value="m" x-text="m" :selected="m === editAgentModalData.model_name"></option>
                                            </template>
                                        </select>
                                    </template>

                                    <template x-if="fetchedModels.length === 0">
                                        <div>
                                            <input type="text" name="model_name" :value="editAgentModalData.model_name" placeholder="Isi manual / Lakukan Fetch Models di Tab 3" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none">
                                            <span class="text-[10px] text-amber-400 mt-1 block">💡 Belum ada model di-fetch. Buka Tab 3 dan klik "Fetch Models" agar pilihan model otomatis muncul!</span>
                                        </div>
                                    </template>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-400 mb-1">Temperature * (0.00 - 2.00)</label>
                                    <input type="number" step="0.05" min="0.00" max="2.00" name="temperature" required :value="editAgentModalData.temperature || 0.70" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                </div>
                            </div>

                            <div class="p-3 rounded-lg bg-slate-900 border border-slate-800 text-[11px] text-slate-400 space-y-1">
                                <span class="font-bold text-emerald-400 block mb-1">💡 Panduan Rentang Parameter Temperature (0.00 s/d 2.00):</span>
                                <p>• <strong class="text-amber-400">0.00 - 0.30:</strong> Sangat Presisi, Faktual & Konsisten (Cocok untuk Stok & Faktur)</p>
                                <p>• <strong class="text-emerald-400">0.70 (Default):</strong> Seimbang (Rekomendasi Utama CS & Sales Closing)</p>
                                <p>• <strong class="text-rose-400">1.00 - 2.00:</strong> Sangat Kreatif (Berisiko Halusinasi Teks)</p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Max Tokens *</label>
                                <input type="number" name="max_tokens" required :value="editAgentModalData.max_tokens || 1000" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <!-- Scopes Selection Checkboxes -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-2">Granted Scopes (`resource:action` Matrix - Fase 4)</label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-xl bg-slate-950 border border-slate-800">
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" name="scopes[]" value="products:read" :checked="(editAgentModalData.scopes || []).includes('products:read')" class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                                    <span>products:read</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" name="scopes[]" value="checkout:read" :checked="(editAgentModalData.scopes || []).includes('checkout:read')" class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                                    <span>checkout:read</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" name="scopes[]" value="faq:read" :checked="(editAgentModalData.scopes || []).includes('faq:read')" class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                                    <span>faq:read</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" name="scopes[]" value="products:write" :checked="(editAgentModalData.scopes || []).includes('products:write')" class="rounded border-slate-700 text-emerald-500 focus:ring-emerald-500 bg-slate-900">
                                    <span>products:write</span>
                                </label>
                                <label class="flex items-center gap-2 text-xs text-slate-300">
                                    <input type="checkbox" name="scopes[]" value="*:*" :checked="(editAgentModalData.scopes || []).includes('*:*')" class="rounded border-slate-700 text-amber-500 focus:ring-amber-500 bg-slate-900">
                                    <span class="text-amber-400 font-bold">*:* (Full Access)</span>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Master Persona System Prompt</label>
                            <textarea name="system_prompt" rows="4" x-text="editAgentModalData.system_prompt" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white font-mono focus:border-emerald-500 focus:outline-none"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Assigned Account Scope</label>
                                <input type="text" name="assigned_account" :value="editAgentModalData.assigned_account" placeholder="all_accounts atau ID Akun WA" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Status Keaktifan Agent *</label>
                                <select name="is_active" required :value="editAgentModalData.is_active ? '1' : '0'" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    <option value="1">Aktif 🟢</option>
                                    <option value="0">Non-Aktif 🔴</option>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button type="button" @click="editAgentModalData = null" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">Batal</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500">Simpan Konfigurasi AI</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>

        <!-- MODAL TAMBAH PRODUK BARU -->
        <div x-show="openAddModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4" @click.away="openAddModal = false">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Tambah Produk Baru (Agentic Hub)</h3>
                    <button @click="openAddModal = false" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <form method="POST" action="{{ route('modules.agentic-hub.products.store') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">SKU Produk *</label>
                            <input type="text" name="sku" required placeholder="misal: KAOS-BLK-L" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Produk *</label>
                            <input type="text" name="name" required placeholder="Nama Produk Resmi" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Kategori</label>
                            <input type="text" name="category" placeholder="Pakaian / Elektronik" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Harga Normal (IDR) *</label>
                            <input type="number" step="0.01" name="price" required placeholder="125000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Harga Promo (Opsional)</label>
                            <input type="number" step="0.01" name="promo_price" placeholder="99000" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Link Checkout Direct (Payment Link)</label>
                        <input type="url" name="checkout_link" placeholder="https://wakdondin.com/checkout/kaos-hitam" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-400 mb-1">Link Landing Page / Detail Produk</label>
                        <input type="url" name="product_link" placeholder="https://wakdondin.com/produk/kaos-hitam" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Status Stok *</label>
                            <select name="stock_status" required class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                <option value="in_stock">In Stock 🟢</option>
                                <option value="out_of_stock">Out of Stock 🔴</option>
                                <option value="pre_order">Pre-Order 🟡</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Deskripsi Ringkas</label>
                            <textarea name="description" rows="2" placeholder="Bahan Cotton Combed 24s adem..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none"></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                        <button type="button" @click="openAddModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">Batal</button>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500">Simpan Produk</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- MODAL EDIT PRODUK -->
        <div x-show="editModalData" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl w-full max-w-xl p-6 shadow-2xl space-y-4" @click.away="editModalData = null">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-base font-bold text-white">Edit Produk</h3>
                    <button @click="editModalData = null" class="text-slate-400 hover:text-white text-lg">✕</button>
                </div>

                <template x-if="editModalData">
                    <form method="POST" :action="'/agentic-hub/products/' + editModalData.id" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">SKU Produk *</label>
                                <input type="text" name="sku" required :value="editModalData.sku" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Nama Produk *</label>
                                <input type="text" name="name" required :value="editModalData.name" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Kategori</label>
                                <input type="text" name="category" :value="editModalData.category" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Harga Normal (IDR) *</label>
                                <input type="number" step="0.01" name="price" required :value="editModalData.price" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Harga Promo</label>
                                <input type="number" step="0.01" name="promo_price" :value="editModalData.promo_price" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Link Checkout Direct (Payment Link)</label>
                            <input type="url" name="checkout_link" :value="editModalData.checkout_link" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-400 mb-1">Link Landing Page / Detail Produk</label>
                            <input type="url" name="product_link" :value="editModalData.product_link" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Status Stok *</label>
                                <select name="stock_status" required :value="editModalData.stock_status" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                    <option value="in_stock" :selected="editModalData.stock_status === 'in_stock'">In Stock 🟢</option>
                                    <option value="out_of_stock" :selected="editModalData.stock_status === 'out_of_stock'">Out of Stock 🔴</option>
                                    <option value="pre_order" :selected="editModalData.stock_status === 'pre_order'">Pre-Order 🟡</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-400 mb-1">Deskripsi Ringkas</label>
                                <textarea name="description" rows="2" x-text="editModalData.description" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:border-emerald-500 focus:outline-none"></textarea>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                            <button type="button" @click="editModalData = null" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold hover:bg-slate-700">Batal</button>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-500">Update Produk</button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </main>
</body>
</html>
