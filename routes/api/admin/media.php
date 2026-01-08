<?php

/**
 * ADMIN MEDIA MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Media\Controllers\MediaController;
use Modules\Media\Controllers\FolderController;

// =====================================================
// MEDIA FILE MANAGEMENT
// =====================================================
Route::prefix('module/media')->middleware('auth:sanctum')->group(function () {
    
    // Get all media files (POST for filtering compatibility)
    Route::post('/getLists', [MediaController::class, 'getLists']);
    
    // Upload media file
    Route::post('/upload', [MediaController::class, 'store']);
    
    // Replace media file
    Route::post('/replace/{id}', [MediaController::class, 'replace']);
    
    // Get usage
    Route::get('/usage/{id}', [MediaController::class, 'getUsage']);

    // Update metadata (Frontend compat)
    Route::post('/{id}/update', function (Request $request, $id) {
        $file = \Modules\Media\Models\MediaFile::findOrFail($id);
        $file->alt_text = $request->input('alt_text', $file->alt_text);
        $file->title = $request->input('title', $file->title);
        $file->description = $request->input('description', $file->description);
        $file->save();
        return response()->json(['success' => true, 'message' => 'Metadata updated']);
    });
    
    // Delete media file
    Route::post('/removeFiles', function (Request $request) {
        $ids = $request->input('file_ids', []);
        if (empty($ids)) return response()->json(['error' => 'No files'], 400);
        
        $files = \Modules\Media\Models\MediaFile::whereIn('id', $ids)->get();
        foreach ($files as $file) {
            // Delete physical file (helper in controller would be better, but inline valid for now)
            if ($file->file_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($file->file_path)) {
                // Also delete thumbs
                \Illuminate\Support\Facades\Storage::disk('public')->delete($file->file_path);
            }
        }
        \Modules\Media\Models\MediaFile::whereIn('id', $ids)->delete();
        return response()->json(['success' => true, 'message' => 'Deleted']);
    });
});

// =====================================================
// MEDIA FOLDER MANAGEMENT
// =====================================================
Route::prefix('media/folder')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [FolderController::class, 'index']);
    Route::post('/store', [FolderController::class, 'store']);
    Route::post('/delete', [FolderController::class, 'delete']);
    
    // Move files to folder
    Route::post('/', function (Request $request) {
        $fileIds = $request->input('file_ids', []);
        $folderId = $request->input('folder_id');
        if (!empty($fileIds)) {
            \Modules\Media\Models\MediaFile::whereIn('id', $fileIds)->update(['folder_id' => $folderId]);
        }
        return response()->json(['success' => true]);
    });
});
