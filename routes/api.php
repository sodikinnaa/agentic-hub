<?php

use Illuminate\Support\Facades\Route;
use Modules\AgenticHub\Http\Controllers\AgenticToolApiController;

/*
|--------------------------------------------------------------------------
| AgenticHub Single Gateway REST API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1/agentic-hub')->group(function () {
    // Single Gateway Endpoint for AI Tool Calling Execution
    Route::post('/tools/execute', [AgenticToolApiController::class, 'execute'])->name('api.v1.agentic-hub.tools.execute');

    // End-to-End AI Agent Conversation API Endpoint (Includes AI Reply + DB Lookup)
    Route::post('/chat', [AgenticToolApiController::class, 'chat'])->name('api.v1.agentic-hub.chat');

    // Fonnte-Compatible Webhook Receiver Endpoint for Agentic Hub (Fase 8 Integration)
    Route::post('/webhook/receiver', [AgenticToolApiController::class, 'webhookReceiver'])->name('api.v1.agentic-hub.webhook.receiver');
    Route::post('/fonnte-webhook', [AgenticToolApiController::class, 'webhookReceiver'])->name('api.v1.agentic-hub.fonnte-webhook');
});
