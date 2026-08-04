<?php

use Illuminate\Support\Facades\Route;
use Modules\AgenticHub\Http\Controllers\AgenticHubController;

/*
|--------------------------------------------------------------------------
| AgenticHub Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth', 'whatsapp.onboarded', 'feature:agentic-hub'])->group(function () {
    // Official UI entry points & Product Management
    Route::get('/agentic-hub', [AgenticHubController::class, 'index']);
    Route::get('/agentic-hub/ai-management', [AgenticHubController::class, 'index'])->name('modules.agentic-hub.index');
    Route::get('/my-features/agentic-hub', [AgenticHubController::class, 'index'])->name('my-features.agentic-hub');

    // Product Catalog CRUD Routes (Fase 2)
    Route::post('/agentic-hub/products', [AgenticHubController::class, 'store'])->name('modules.agentic-hub.products.store');
    Route::put('/agentic-hub/products/{id}', [AgenticHubController::class, 'update'])->name('modules.agentic-hub.products.update');
    Route::delete('/agentic-hub/products/{id}', [AgenticHubController::class, 'destroy'])->name('modules.agentic-hub.products.destroy');

    // Single Global AI Model Provider Connection & Testing (Fase 3)
    Route::put('/agentic-hub/provider', [AgenticHubController::class, 'updateGlobalProvider'])->name('modules.agentic-hub.provider.update');
    Route::post('/agentic-hub/fetch-models', [AgenticHubController::class, 'fetchModels'])->name('modules.agentic-hub.fetch-models');
    Route::post('/agentic-hub/provider/test-chat', [AgenticHubController::class, 'testProviderChat'])->name('modules.agentic-hub.provider.test-chat');

    // AI Roles, Scopes & Model Selection Routes (Fase 4)
    Route::put('/agentic-hub/agents/{id}', [AgenticHubController::class, 'updateAgent'])->name('modules.agentic-hub.agents.update');
    Route::post('/agentic-hub/agents/{id}/regenerate-key', [AgenticHubController::class, 'regenerateAgentApiKey'])->name('modules.agentic-hub.agents.regenerate-key');

    // Interactive Tool Playground Route (Fase 6)
    Route::post('/agentic-hub/playground/execute', [AgenticHubController::class, 'executePlayground'])->name('modules.agentic-hub.playground.execute');
});

// Public API & Webhook Endpoints for Agentic Hub (No Session Auth / No CSRF Required)
Route::prefix('api/v1/agentic-hub')->group(function () {
    Route::post('/tools/execute', [\Modules\AgenticHub\Http\Controllers\AgenticToolApiController::class, 'execute'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.v1.agentic-hub.tools.execute');

    Route::post('/chat', [\Modules\AgenticHub\Http\Controllers\AgenticToolApiController::class, 'chat'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.v1.agentic-hub.chat');

    Route::post('/webhook/receiver', [\Modules\AgenticHub\Http\Controllers\AgenticToolApiController::class, 'webhookReceiver'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.v1.agentic-hub.webhook.receiver');

    Route::post('/fonnte-webhook', [\Modules\AgenticHub\Http\Controllers\AgenticToolApiController::class, 'webhookReceiver'])
        ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
        ->name('api.v1.agentic-hub.fonnte-webhook');
});
