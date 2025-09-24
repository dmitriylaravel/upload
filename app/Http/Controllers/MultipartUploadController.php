<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class MultipartUploadController extends Controller
{
    public function index()
    {
        return view('multipart-upload');
    }

    /**
     * Initialize a multipart upload
     */
    public function initiateUpload(Request $request)
    {
        $request->validate([
            'filename' => 'required|string',
            'filesize' => 'required|integer',
            'filetype' => 'required|string',
        ]);

        try {
            $disk = Storage::disk('public');
            $config = config('filesystems.disks.public');

            // Check if we're using S3/R2 or local storage
            if ($config['driver'] !== 's3') {
                throw new \Exception('Multipart upload requires S3/R2 storage. Current driver: ' . $config['driver']);
            }

            // Create S3 client directly with config
            $s3Client = new S3Client([
                'version' => 'latest',
                'region' => $config['region'] ?? 'auto',
                'endpoint' => $config['endpoint'] ?? null,
                'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
                'credentials' => [
                    'key' => $config['key'] ?? null,
                    'secret' => $config['secret'] ?? null,
                ],
            ]);

            $bucket = $config['bucket'] ?? null;

            if (!$bucket) {
                throw new \Exception('S3 bucket not configured');
            }

            $key = 'multipart-uploads/' . time() . '_' . Str::slug(pathinfo($request->filename, PATHINFO_FILENAME)) . '.' . pathinfo($request->filename, PATHINFO_EXTENSION);

            // Initiate multipart upload
            $result = $s3Client->createMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $key,
                'ContentType' => $request->filetype,
            ]);

            $uploadId = $result['UploadId'];

            Log::info("Multipart upload initiated", [
                'upload_id' => $uploadId,
                'key' => $key,
                'filename' => $request->filename,
                'size' => $request->filesize
            ]);

            return response()->json([
                'success' => true,
                'upload_id' => $uploadId,
                'key' => $key,
                'chunk_size' => 5 * 1024 * 1024, // 5MB chunks
                'total_chunks' => ceil($request->filesize / (5 * 1024 * 1024))
            ]);

        } catch (AwsException $e) {
            Log::error('Multipart upload initiation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Failed to initiate upload: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Upload a single chunk
     */
    public function uploadChunk(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'key' => 'required|string',
            'chunk_number' => 'required|integer|min:1',
            'chunk' => 'required|file',
        ]);

        try {
            $disk = Storage::disk('public');
            $adapter = $disk->getAdapter();
            $s3Client = $adapter->getClient();
            $bucket = $adapter->getBucket();

            // Upload part
            $result = $s3Client->uploadPart([
                'Bucket' => $bucket,
                'Key' => $request->key,
                'PartNumber' => $request->chunk_number,
                'UploadId' => $request->upload_id,
                'Body' => fopen($request->file('chunk')->getRealPath(), 'r'),
            ]);

            $etag = $result['ETag'];

            Log::info("Chunk uploaded", [
                'upload_id' => $request->upload_id,
                'chunk_number' => $request->chunk_number,
                'etag' => $etag
            ]);

            return response()->json([
                'success' => true,
                'chunk_number' => $request->chunk_number,
                'etag' => $etag
            ]);

        } catch (AwsException $e) {
            Log::error('Chunk upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Chunk upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete the multipart upload
     */
    public function completeUpload(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'key' => 'required|string',
            'parts' => 'required|array',
            'parts.*.part_number' => 'required|integer',
            'parts.*.etag' => 'required|string',
        ]);

        try {
            $disk = Storage::disk('public');
            $adapter = $disk->getAdapter();
            $s3Client = $adapter->getClient();
            $bucket = $adapter->getBucket();

            // Prepare parts for completion
            $parts = collect($request->parts)->map(function ($part) {
                return [
                    'ETag' => $part['etag'],
                    'PartNumber' => $part['part_number']
                ];
            })->sortBy('PartNumber')->values()->toArray();

            // Complete multipart upload
            $result = $s3Client->completeMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $request->key,
                'UploadId' => $request->upload_id,
                'MultipartUpload' => [
                    'Parts' => $parts
                ]
            ]);

            $fileUrl = $result['Location'];
            $size = $disk->size($request->key);

            Log::info("Multipart upload completed", [
                'upload_id' => $request->upload_id,
                'key' => $request->key,
                'url' => $fileUrl,
                'size' => $size
            ]);

            return response()->json([
                'success' => true,
                'url' => $fileUrl,
                'key' => $request->key,
                'size' => $size ? number_format($size / 1024 / 1024, 2) . ' MB' : 'Unknown'
            ]);

        } catch (AwsException $e) {
            Log::error('Multipart upload completion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Upload completion failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Abort multipart upload
     */
    public function abortUpload(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|string',
            'key' => 'required|string',
        ]);

        try {
            $disk = Storage::disk('public');
            $adapter = $disk->getAdapter();
            $s3Client = $adapter->getClient();
            $bucket = $adapter->getBucket();

            $s3Client->abortMultipartUpload([
                'Bucket' => $bucket,
                'Key' => $request->key,
                'UploadId' => $request->upload_id,
            ]);

            Log::info("Multipart upload aborted", [
                'upload_id' => $request->upload_id,
                'key' => $request->key
            ]);

            return response()->json(['success' => true]);

        } catch (AwsException $e) {
            Log::error('Multipart upload abort failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Abort failed: ' . $e->getMessage()
            ], 500);
        }
    }
}