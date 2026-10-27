<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'dashboard');

require __DIR__ . '/admin.php';
require __DIR__ . '/front-end.php';
require __DIR__ . '/auth.php';
