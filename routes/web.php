<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Health check untuk Render/Railway (jangan dihapus)
Route::get('/up', function () {
    return response()->json(['status' => 'ok', 'app' => config('app.name')]);
});
