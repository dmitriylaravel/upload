<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ActualMoveFilesTest;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/movefiles-test', ActualMoveFilesTest::class)->name('movefiles-test');
