<?php
namespace Modules\Media\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Media\Helpers\FileHelper;
use Modules\Media\Models\MediaFile;
use Intervention\Image\Facades\Image;
// Use safe checks for these models in usage method
use Modules\Tour\Models\Tour;
use Modules\Location\Models\Location;
use Modules\News\Models\News;
use Modules\Page\Models\Page;

class MediaController extends Controller
{
    public function getLists(Request $request)
    {
        try {
            $query = MediaFile::query();

            // Search
            if ($request->has('s') && $request->s) {
                $query->where('file_name', 'LIKE', '%' . $request->s . '%');
            }

            // Type Filter
            if ($request->has('file_type') && $request->file_type !== 'all') {
                $type = $request->file_type;
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

            // Folder Filter
            if ($request->has('folder_id')) {
                // If folder_id is "0" or null/empty, we can treat it as root if logic requires, 
                // but usually folder_id=0 or null means root.
                // If the frontend sends specific folder ID:
                if ($request->folder_id) {
                    $query->where('folder_id', $request->folder_id);
                } else {
                    // Decide if we want to show ONLY root files or all files when no folder is selected?
                    // Usually "All Media" shows everything. 
                    // If frontend sends folder_id=null specifically for "Root", we might filter where folder_id is null.
                    // For now, if folder_id is provided but empty/null, we might ignore to show all, or filter for root.
                    // Let's assume frontend ignores folder_id param for "All".
                }
            }

            $files = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 30));

            return response()->json([
                'data' => $files->map(function ($file) {
                    return $this->formatFileResponse($file);
                }),
                'total' => $files->total(),
                'totalPage' => $files->lastPage(),
                'current_page' => $files->currentPage(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $files = $request->file('file') ?? $request->file('files');
            
            if (!$files) {
                return response()->json(['error' => 'No file uploaded'], 400);
            }
            
            if (!is_array($files)) {
                $files = [$files];
            }
            
            $uploaded = [];
            
            foreach ($files as $file) {
                $uploaded[] = $this->handleUpload($file, $request->folder_id);
            }
            
            return response()->json([
                'success' => true,
                'data' => count($uploaded) === 1 ? $uploaded[0] : $uploaded,
                'message' => count($uploaded) . ' file(s) uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function replace(Request $request, $id)
    {
        try {
            $file = MediaFile::findOrFail($id);
            $newFile = $request->file('file');

            if (!$newFile) {
                return response()->json(['error' => 'No file provided'], 400);
            }

            // Delete old physical files
            $this->deletePhysicalFiles($file);

            // Upload new file content logic
            $folder = 'uploads';
            $mimeType = $newFile->getMimeType();
            
            if (str_starts_with($mimeType, 'image/')) {
                $folder = 'uploads/images';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $folder = 'uploads/videos';
            } else {
                $folder = 'uploads/documents';
            }

            // Keep clean filename structure
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $newFile->getClientOriginalName());
            
            // Store file - returns relative path "uploads/images/..."
            $path = $newFile->storeAs($folder, $filename, 'public');

            // Handle Image Resizing
            $width = null;
            $height = null;

            if (str_starts_with($mimeType, 'image/')) {
                try {
                    $imageSize = getimagesize($newFile->getRealPath());
                    $width = $imageSize[0] ?? null;
                    $height = $imageSize[1] ?? null;
                    $this->generateThumbnails($newFile, $folder, $filename);
                } catch (\Exception $e) {
                    // Log error but continue
                }
            }

            // Update record
            $file->file_name = $newFile->getClientOriginalName();
            $file->file_path = $path; // Relative path only
            $file->file_type = $mimeType;
            $file->file_extension = $newFile->getClientOriginalExtension();
            $file->file_size = $newFile->getSize();
            // Only update dimensions if new file is image, else null them or keep (if replaced image with doc? unlikely)
            if ($width) {
                $file->file_width = $width;
                $file->file_height = $height;
            }
            $file->save();

            return response()->json([
                'success' => true,
                'data' => $this->formatFileResponse($file),
                'message' => 'File replaced successfully'
            ]);

        } catch (\Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 500);
        }
    }

    public function getUsage($id)
    {
        // Simple usage scanner
        $usage = [];

        // Check Tours
        if (class_exists(\Modules\Tour\Models\Tour::class)) {
            $tours = \Modules\Tour\Models\Tour::where('image_id', $id)
                ->orWhere('banner_image_id', $id)
                ->orWhere('gallery', 'LIKE', '%"' . $id . '"%')
                ->select('id', 'title')
                ->get();
            foreach ($tours as $tour) {
                $usage[] = ['type' => 'Tour', 'name' => $tour->title, 'id' => $tour->id];
            }
        }

        // Check Locations
        if (class_exists(\Modules\Location\Models\Location::class)) {
            $locs = \Modules\Location\Models\Location::where('image_id', $id)
                ->orWhere('banner_image_id', $id)
                ->select('id', 'name')
                ->get();
            foreach ($locs as $loc) {
                $usage[] = ['type' => 'Destination', 'name' => $loc->name, 'id' => $loc->id];
            }
        }
        
        // Check News/Blog
        if (class_exists(\Modules\News\Models\News::class)) {
            $news = \Modules\News\Models\News::where('image_id', $id)
                ->select('id', 'title')
                ->get();
            foreach ($news as $n) {
                $usage[] = ['type' => 'Article', 'name' => $n->title, 'id' => $n->id];
            }
        }

        return response()->json(['data' => $usage]);
    }

    private function handleUpload($file, $folderId = null)
    {
        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
        
        $folder = 'uploads';
        $mimeType = $file->getMimeType();
        
        if (str_starts_with($mimeType, 'image/')) {
            $folder = 'uploads/images';
            // Organize by date to avoid huge directories
            $folder .= '/' . date('Y/m/d');
        } elseif (str_starts_with($mimeType, 'video/')) {
            $folder = 'uploads/videos/' . date('Y/m/d');
        } elseif (str_starts_with($mimeType, 'audio/')) {
            $folder = 'uploads/audio/' . date('Y/m/d');
        } else {
            $folder = 'uploads/documents/' . date('Y/m/d');
        }
        
        // Store
        $path = $file->storeAs($folder, $filename, 'public');
        
        $width = null;
        $height = null;

        if (str_starts_with($mimeType, 'image/')) {
            try {
                // Get dimensions
                $imageSize = getimagesize($file->getRealPath());
                $width = $imageSize[0] ?? null;
                $height = $imageSize[1] ?? null;

                // Generate thumbnails
                $this->generateThumbnails($file, $folder, $filename);
            } catch (\Exception $e) {
                // Log error
            }
        }

        $mediaFile = new MediaFile();
        $mediaFile->file_name = $file->getClientOriginalName();
        $mediaFile->file_path = $path; // Relative Path
        $mediaFile->file_type = $mimeType;
        $mediaFile->file_extension = $file->getClientOriginalExtension();
        $mediaFile->file_size = $file->getSize();
        $mediaFile->file_width = $width;
        $mediaFile->file_height = $height;
        if ($folderId) {
            $mediaFile->folder_id = $folderId;
        }
        $mediaFile->create_user = auth()->id();
        $mediaFile->save();

        return $this->formatFileResponse($mediaFile);
    }

    private function generateThumbnails($file, $folder, $filename)
    {
        $path = Storage::disk('public')->path($folder);
        if (!file_exists($path)) {
            mkdir($path, 0775, true);
        }

        $sizes = [
            '150' => 150,
            '600' => 600,
            '1024' => 1024
        ];

        // Original file is already saved by storeAs
        // $originalPath = $path . '/' . $filename;

        foreach ($sizes as $suffix => $size) {
            try {
                $img = Image::make($file->getRealPath());
                
                // Resize constraint: aspect ratio, prevent upsizing
                $img->resize($size, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $newFilename = pathinfo($filename, PATHINFO_FILENAME) . '_' . $suffix . '.' . pathinfo($filename, PATHINFO_EXTENSION);
                $img->save($path . '/' . $newFilename, 80);
            } catch (\Exception $e) {
                // Continue if one resize fails
            }
        }
    }

    private function deletePhysicalFiles($mediaFile)
    {
        if (!$mediaFile->file_path) return;
        
        $disk = Storage::disk('public');
        
        // Delete original
        if ($disk->exists($mediaFile->file_path)) {
            $disk->delete($mediaFile->file_path);
        }
        
        // Delete thumbnails if image
        if (str_starts_with($mediaFile->file_type, 'image/')) {
            $sizes = ['150', '600', '1024'];
            $pathInfo = pathinfo($mediaFile->file_path);
            $dirname = $pathInfo['dirname']; // e.g. uploads/images/2026/01/09
            $filename = $pathInfo['filename']; // e.g. myfile
            $extension = $pathInfo['extension']; // e.g. jpg

            foreach ($sizes as $size) {
                $thumbPath = $dirname . '/' . $filename . '_' . $size . '.' . $extension;
                if ($disk->exists($thumbPath)) {
                    $disk->delete($thumbPath);
                }
            }
        }
    }

    private function formatFileResponse($file)
    {
        $baseUrl = rtrim(config('app.url'), '/') . '/storage/';
        $filePath = $file->file_path;
        
        // Generate URLs for variants
        $sizes = [];
        if (str_starts_with($file->file_type, 'image/')) {
            $pathInfo = pathinfo($filePath);
            $dirname = $pathInfo['dirname'];
            $filename = $pathInfo['filename'];
            $extension = $pathInfo['extension'];
            
            // Assume files exist if it's an image
            $sizes['default'] = $baseUrl . $filePath;
            $sizes['150'] = $baseUrl . $dirname . '/' . $filename . '_150.' . $extension;
            $sizes['600'] = $baseUrl . $dirname . '/' . $filename . '_600.' . $extension;
            $sizes['1024'] = $baseUrl . $dirname . '/' . $filename . '_1024.' . $extension;
        } else {
            $sizes['default'] = $baseUrl . $filePath;
        }

        return [
            'id' => $file->id,
            'file_name' => $file->file_name,
            'file_path' => $filePath, // RELATIVE PATH
            'file_url' => $baseUrl . $filePath, // Full URL
            'file_type' => $file->file_type,
            'file_extension' => $file->file_extension,
            'file_size' => $file->file_size,
            'width' => $file->file_width,
            'height' => $file->file_height,
            'alt_text' => $file->alt_text,
            'title' => $file->title,
            'description' => $file->description,
            'folder_id' => $file->folder_id,
            'created_at' => $file->created_at,
            'sizes' => $sizes
        ];
    }
}
