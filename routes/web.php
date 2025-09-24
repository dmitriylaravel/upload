<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoveFilesTestController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/movefiles-test', [MoveFilesTestController::class, 'index'])->name('movefiles-test');
