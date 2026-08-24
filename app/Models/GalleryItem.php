<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GalleryItem extends Model
{
    protected $fillable = ['image_path', 'thumb_path', 'uploaded_by'];
}
