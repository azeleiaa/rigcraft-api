<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// NOTE: health check /up disediakan framework via bootstrap/app.php
// (withRouting health: '/up') — jangan definisikan ulang di sini
// agar tidak duplikat dan bebas middleware web.
