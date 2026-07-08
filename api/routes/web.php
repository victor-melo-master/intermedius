<?php

/**
 * Web routes.
 * Single route for the welcome page (GET /).
 */

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
