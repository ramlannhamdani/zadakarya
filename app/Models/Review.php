<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'author_name', 'rating', 'content', 'review_date',
        'source', 'is_published', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
            'is_published' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function getInitialAttribute(): string
    {
        return mb_strtoupper(mb_substr(trim($this->author_name), 0, 1));
    }

    /** Deterministic avatar color per author, Google-style. */
    public function getAvatarClassAttribute(): string
    {
        $palette = [
            'bg-red-500', 'bg-blue-500', 'bg-green-600', 'bg-amber-500',
            'bg-purple-500', 'bg-teal-600', 'bg-rose-500', 'bg-indigo-500',
        ];

        return $palette[crc32($this->author_name) % count($palette)];
    }
}
