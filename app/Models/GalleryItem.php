<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = ['image_path', 'thumb_path', 'is_public', 'uploaded_by'];

    protected $attributes = ['is_public' => true];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }
}
