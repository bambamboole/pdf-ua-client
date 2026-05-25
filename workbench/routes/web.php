<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Workbench\App\Http\Controllers\TemplateBuilderController;

Route::get('/', [TemplateBuilderController::class, 'index']);
Route::post('/render', [TemplateBuilderController::class, 'render']);
