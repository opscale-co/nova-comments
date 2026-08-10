<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Opscale\NovaComments\Http\Controllers\AttachmentController;

Route::post('attachments', [AttachmentController::class, 'store']);
Route::delete('attachments', [AttachmentController::class, 'destroy']);
