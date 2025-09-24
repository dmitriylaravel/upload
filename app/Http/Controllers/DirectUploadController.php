<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class DirectUploadController extends Controller
{
    public function index()
    {
        return view('direct-upload');
    }

    public function uploadStandard(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB limit for standard
        ]);

        try {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();

            // Standard method - copy file to R2
            $path = $file->store('standard-direct', 'r2');

            $size = Storage::disk('r2')->size($path);

            Log::info("Standard upload: {$filename} - Size: " . number_format($size / 1024 / 1024, 2) . " MB - Method: COPY");

            return response()->json([
                'success' => true,
                'message' => 'Standard upload completed (COPY method)',
                'path' => $path,
                'size' => number_format($size / 1024 / 1024, 2) . ' MB',
                'method' => 'Standard Laravel store() - copies file'
            ]);

        } catch (\Exception $e) {
            Log::error("Standard upload failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function uploadMoveFiles(Request $request)
    {
        // No size limit validation - let's see how large we can go
        $request->validate([
            'file' => 'required|file',
        ]);

        try {
            $file = $request->file('file');
            $filename = time() . '_move_' . $file->getClientOriginalName();

            // Direct stream upload to R2 (simulates moveFiles() behavior)
            $fileContents = file_get_contents($file->getRealPath());
            $path = 'move-direct/' . $filename;

            // Direct upload to R2 without temporary storage
            Storage::disk('r2')->put($path, $fileContents);

            $size = Storage::disk('r2')->size($path);

            Log::info("MoveFiles upload: {$filename} - Size: " . number_format($size / 1024 / 1024, 2) . " MB - Method: MOVE");

            return response()->json([
                'success' => true,
                'message' => 'MoveFiles upload completed (MOVE method)',
                'path' => $path,
                'size' => number_format($size / 1024 / 1024, 2) . ' MB',
                'method' => 'Direct move operation - no copying'
            ]);

        } catch (\Exception $e) {
            Log::error("MoveFiles upload failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}