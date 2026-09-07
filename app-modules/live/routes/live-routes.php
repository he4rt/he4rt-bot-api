<?php

declare(strict_types=1);

use He4rt\Live\IngestAuthController;
use He4rt\Live\IngestWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('live/ingest/auth', IngestAuthController::class)->name('live.ingest-auth');
Route::post('live/ingest/webhook', IngestWebhookController::class)->name('live.ingest-webhook');
