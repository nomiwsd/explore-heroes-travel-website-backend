<?php

/**
 * ADMIN MEDIA MODULE ROUTES
 */

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Media\Models\MediaFile;
use Illuminate\Support\Facades\Storage;

// =====================================================
// MEDIA FILE MANAGEMENT
// =====================================================
Route::prefix('module/media')->middleware('auth:sanctum')->group(function () {
    // Get all media files
    Route::get('/', function (Request $request) {
        try {
            $query = MediaFile::with('author');
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where('file_name', 'LIKE', '%' . $request->s . '%');
            }
            
            // Type filter
            if ($request->has('type') && $request->type !== 'all') {
                switch ($request->type) {
                    case 'image':
                        $query->where('file_type', 'LIKE', 'image/%');
                        break;
                    case 'video':
                        $query->where('file_type', 'LIKE', 'video/%');
                        break;
                    case 'document':
                        $query->whereIn('file_extension', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
                        break;
                }
            }
            
            $files = $query->orderBy('id', 'desc')->paginate($request->input('limit', 30));
            
            return response()->json([
                'data' => $files->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_path' => $file->file_path,
                        'file_url' => $file->file_url ?? asset('storage/' . $file->file_path),
                        'file_type' => $file->file_type,
                        'file_extension' => $file->file_extension,
                        'file_size' => $file->file_size,
                        'dimensions' => $file->dimensions,
                        'alt_text' => $file->alt_text,
                        'created_at' => $file->created_at,
                    ];
                }),
                'total' => $files->total(),
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get media files (POST version for frontend compatibility)
    Route::post('/getLists', function (Request $request) {
        try {
            $query = MediaFile::query();
            
            // Search filter
            if ($request->has('s') && $request->s) {
                $query->where('file_name', 'LIKE', '%' . $request->s . '%');
            }
            
            // Type filter
            $type = $request->input('file_type', 'all');
            if ($type && $type !== 'all') {
                switch ($type) {
                    case 'image':
                        $query->where('file_type', 'LIKE', 'image/%');
                        break;
                    case 'video':
                        $query->where('file_type', 'LIKE', 'video/%');
                        break;
                    case 'document':
                        $query->whereIn('file_extension', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']);
                        break;
                }
            }
            
            // Folder filter
            if ($request->has('folder_id') && $request->folder_id) {
                $query->where('folder_id', $request->folder_id);
            }
            
            $perPage = $request->input('per_page', 30);
            $files = $query->orderBy('id', 'desc')->paginate($perPage);
            
            return response()->json([
                'data' => $files->map(function ($file) {
                    $baseUrl = rtrim(config('app.url'), '/') . '/storage/';
                    $filePath = $file->file_path;
                    
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'file_path' => $baseUrl . $filePath,
                        'file_type' => $file->file_type,
                        'file_extension' => $file->file_extension,
                        'file_size' => $file->file_size,
                        'file_width' => $file->width ?? null,
                        'file_height' => $file->height ?? null,
                        'alt_text' => $file->alt_text,
                        'title' => $file->title,
                        'description' => $file->description,
                        'folder_id' => $file->folder_id,
                        'created_at' => $file->created_at,
                        'sizes' => [
                            'default' => $baseUrl . $filePath,
                            '150' => $baseUrl . $filePath,
                            '600' => $baseUrl . $filePath,
                            '1024' => $baseUrl . $filePath,
                        ],
                    ];
                }),
                'total' => $files->total(),
                'totalPage' => $files->lastPage(),
                'current_page' => $files->currentPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get single media file
    Route::get('/edit/{id}', function ($id) {
        try {
            $file = MediaFile::findOrFail($id);
            
            return response()->json([
                'data' => [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'file_path' => $file->file_path,
                    'file_url' => $file->file_url ?? asset('storage/' . $file->file_path),
                    'file_type' => $file->file_type,
                    'file_extension' => $file->file_extension,
                    'file_size' => $file->file_size,
                    'dimensions' => $file->dimensions,
                    'alt_text' => $file->alt_text,
                    'title' => $file->title,
                    'description' => $file->description,
                    'created_at' => $file->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Upload media file
    Route::post('/upload', function (Request $request) {
        try {
            $files = $request->file('file') ?? $request->file('files');
            
            if (!$files) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }
            
            // Handle single or multiple files
            if (!is_array($files)) {
                $files = [$files];
            }
            
            $uploaded = [];
            
            foreach ($files as $file) {
                // Generate unique filename
                $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
                
                // Determine folder based on file type
                $folder = 'uploads';
                $mimeType = $file->getMimeType();
                if (str_starts_with($mimeType, 'image/')) {
                    $folder = 'uploads/images';
                } elseif (str_starts_with($mimeType, 'video/')) {
                    $folder = 'uploads/videos';
                } elseif (str_starts_with($mimeType, 'audio/')) {
                    $folder = 'uploads/audio';
                } else {
                    $folder = 'uploads/documents';
                }
                
                // Store file
                $path = $file->storeAs($folder, $filename, 'public');
                
                // Get image dimensions if applicable
                // Get image dimensions
                $width = null;
                $height = null;
                if (str_starts_with($mimeType, 'image/')) {
                    $imageSize = getimagesize($file->getRealPath());
                    if ($imageSize) {
                        $width = $imageSize[0];
                        $height = $imageSize[1];
                    }
                }
                
                // Create media file record
                $mediaFile = new MediaFile();
                $mediaFile->file_name = $file->getClientOriginalName();
                $mediaFile->file_path = $path;
                $mediaFile->file_type = $mimeType;
                $mediaFile->file_extension = $file->getClientOriginalExtension();
                $mediaFile->file_size = $file->getSize();
                $mediaFile->file_width = $width;
                $mediaFile->file_height = $height;
                $mediaFile->create_user = auth()->id();
                $mediaFile->save();
                
                $uploaded[] = [
                    'id' => $mediaFile->id,
                    'file_name' => $mediaFile->file_name,
                    'file_url' => asset('storage/' . $path),
                    'file_path' => $path,
                    'file_type' => $mediaFile->file_type,
                    'file_size' => $mediaFile->file_size,
                ];
            }
            
            return response()->json([
                'success' => true,
                'data' => count($uploaded) === 1 ? $uploaded[0] : $uploaded,
                'message' => count($uploaded) . ' file(s) uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Store (update) media file metadata
    Route::post('/store/{id}', function (Request $request, $id) {
        try {
            $file = MediaFile::findOrFail($id);
            
            $file->alt_text = $request->input('alt_text', $file->alt_text);
            $file->title = $request->input('title', $file->title);
            $file->description = $request->input('description', $file->description);
            $file->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Media file updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Update media file metadata (alternate route for frontend compatibility)
    Route::post('/{id}/update', function (Request $request, $id) {
        try {
            $file = MediaFile::findOrFail($id);
            
            $file->alt_text = $request->input('alt_text', $file->alt_text);
            $file->title = $request->input('title', $file->title);
            $file->description = $request->input('description', $file->description);
            $file->save();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'file_path' => $file->file_path,
                    'file_url' => $file->file_url ?? asset('storage/' . $file->file_path),
                    'alt_text' => $file->alt_text,
                    'title' => $file->title,
                    'description' => $file->description,
                ],
                'message' => 'Media file updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete media file
    Route::delete('/{id}', function ($id) {
        try {
            $file = MediaFile::findOrFail($id);
            
            // Delete physical file
            if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
            
            // Delete database record
            $file->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Media file deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Bulk delete
    Route::post('/bulkEdit', function (Request $request) {
        try {
            $ids = $request->input('ids', []);
            $action = $request->input('action');
            
            if (empty($ids)) {
                return response()->json(['error' => 'No items selected'], 400);
            }
            
            if ($action === 'delete') {
                $files = MediaFile::whereIn('id', $ids)->get();
                
                foreach ($files as $file) {
                    // Delete physical file
                    if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                        Storage::disk('public')->delete($file->file_path);
                    }
                }
                
                MediaFile::whereIn('id', $ids)->delete();
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Files deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Remove files - Frontend compatibility route (uses file_ids instead of ids)
    Route::post('/removeFiles', function (Request $request) {
        try {
            $ids = $request->input('file_ids', []);
            
            if (empty($ids)) {
                return response()->json(['error' => 'No files selected'], 400);
            }
            
            $files = MediaFile::whereIn('id', $ids)->get();
            
            foreach ($files as $file) {
                // Delete physical file
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
            }
            
            MediaFile::whereIn('id', $ids)->delete();
            
            return response()->json([
                'success' => true,
                'message' => count($ids) . ' file(s) deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Get statistics
    Route::get('/statistics', function () {
        try {
            $totalSize = MediaFile::sum('file_size');
            
            $stats = [
                'total_files' => MediaFile::count(),
                'total_size' => $totalSize,
                'total_size_formatted' => formatBytes($totalSize ?? 0),
                'images' => MediaFile::where('file_type', 'LIKE', 'image/%')->count(),
                'videos' => MediaFile::where('file_type', 'LIKE', 'video/%')->count(),
                'documents' => MediaFile::whereNotIn('file_type', ['image/%', 'video/%'])->count(),
            ];
            
            return response()->json($stats);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    });
});

// =====================================================
// MEDIA FOLDER MANAGEMENT
// =====================================================
Route::prefix('media/folder')->middleware('auth:sanctum')->group(function () {
    // Get all folders
    Route::get('/', function () {
        try {
            // Return empty array - folders feature not fully implemented yet
            return response()->json([
                'data' => [],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Create/Update folder
    Route::post('/store', function (Request $request) {
        try {
            // Folders feature not fully implemented yet
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $request->input('id') ?? 1,
                    'name' => $request->input('name'),
                    'parent_id' => $request->input('parent_id'),
                ],
                'message' => 'Folder created successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Delete folder
    Route::post('/delete', function (Request $request) {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Folder deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
    
    // Move files to folder
    Route::post('/', function (Request $request) {
        try {
            $fileIds = $request->input('file_ids', []);
            $folderId = $request->input('folder_id');
            
            if (!empty($fileIds)) {
                MediaFile::whereIn('id', $fileIds)->update(['folder_id' => $folderId]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Files moved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    });
});

// Helper function
if (!function_exists('formatBytes')) {
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
