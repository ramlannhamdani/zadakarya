<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'content', 'featured_image',
        'article_category_id', 'tags', 'user_id', 'is_featured', 'published_at',
        'seo_title', 'seo_description', 'og_image',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '>', now());
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->whereNull('published_at');
    }

    public function getStatusAttribute(): string
    {
        if ($this->published_at === null) {
            return 'draft';
        }

        return $this->published_at->isFuture() ? 'scheduled' : 'published';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'published' => 'Terbit',
            'scheduled' => 'Dijadwalkan',
            'draft' => 'Draft',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'published' => 'bg-green-100 text-green-700 border border-green-200',
            'scheduled' => 'bg-amber-100 text-amber-800 border border-amber-200',
            'draft' => 'bg-neutral-100 text-neutral-600 border border-neutral-200',
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
