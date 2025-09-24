<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MoveFilesTestController;
use App\Http\Controllers\DirectUploadController;
use App\Http\Controllers\MultipartUploadController;
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
Route::get('/direct', [DirectUploadController::class, 'index']);
Route::post('/upload-standard', [DirectUploadController::class, 'uploadStandard']);
Route::post('/upload-movefiles', [DirectUploadController::class, 'uploadMoveFiles']);
Route::get('/multipart', [MultipartUploadController::class, 'index']);
Route::post('/multipart/initiate', [MultipartUploadController::class, 'initiateUpload']);
Route::post('/multipart/upload-chunk', [MultipartUploadController::class, 'uploadChunk']);
Route::post('/multipart/complete', [MultipartUploadController::class, 'completeUpload']);
Route::post('/multipart/abort', [MultipartUploadController::class, 'abortUpload']);
Route::get('/movefiles-test', [MoveFilesTestController::class, 'index'])->name('movefiles-test');

// Temporary route to check PHP limits
Route::get('/php-info', function() {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'max_file_uploads' => ini_get('max_file_uploads'),
    ]);
});
