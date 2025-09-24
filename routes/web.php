<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoveFilesTestController;
use App\Livewire\SimpleTest;
use App\Livewire\MinimalUploadTest;
use App\Livewire\SuperMinimalTest;
use App\Livewire\RealMoveFilesTest;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/super', SuperMinimalTest::class);
Route::get('/test', SimpleTest::class);
Route::get('/minimal', MinimalUploadTest::class);
Route::get('/real', RealMoveFilesTest::class);
Route::get('/movefiles-test', [MoveFilesTestController::class, 'index'])->name('movefiles-test');
