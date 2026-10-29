<?php

use Illuminate\Support\Facades\Route;

// No framework or version details: they only help an attacker.
Route::get('/', function () {
    return ['name' => config('app.name'), 'status' => 'ok'];
});
