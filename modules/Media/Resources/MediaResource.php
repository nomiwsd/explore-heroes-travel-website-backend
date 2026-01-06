<?php


namespace Modules\Media\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

use App\Resources\BaseJsonResource;

class MediaResource extends BaseJsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'file_name' => $this->file_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'file_type' => $this->file_type,
            'file_extension' => $this->file_extension,
            'file_width' => $this->file_width,
            'file_height' => $this->file_height,
            'mime_type' => $this->file_type,
            'folder_id' => $this->folder_id ?? 0,
            'alt_text' => $this->alt_text,
            'title' => $this->title,
            'description' => $this->description,
            'author_id' => $this->author_id ?? $this->create_user,
            'created_at' => $this->created_at ? display_datetime($this->created_at) : '',
            'updated_at' => $this->updated_at ? display_datetime($this->updated_at) : '',
            // URL sizes for different resolutions
            'thumb_size' => $this->getViewUrl('thumb'),
            'medium_size' => $this->getViewUrl('medium'),
            'full_size' => $this->getViewUrl('full'),
            // Also provide sizes object for frontend compatibility
            'sizes' => [
                'default' => $this->getViewUrl('full'),
                '150' => $this->getViewUrl('thumb'),
                '600' => $this->getViewUrl('medium'),
                '1024' => $this->getViewUrl('full'),
            ],
        ];
    }
}
