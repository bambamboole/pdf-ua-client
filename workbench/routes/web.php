<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\TemplateBuilderController;

Route::get('/', [TemplateBuilderController::class, 'index']);
Route::get('/examples', [TemplateBuilderController::class, 'examples']);
Route::post('/html', [TemplateBuilderController::class, 'html']);
Route::post('/pdf', [TemplateBuilderController::class, 'pdf']);
Route::post('/schema', [TemplateBuilderController::class, 'schema']);
