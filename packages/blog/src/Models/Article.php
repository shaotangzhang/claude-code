<?php

declare(strict_types=1);

namespace Acme\Blog\Models;

use Acme\Auth\Models\User;
use Acme\Support\Concerns\HasUlid;
use Acme\Support\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasUlid, Sluggable, SoftDeletes;

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED  = 'archived';

    protected $table = 'acme_blog_articles';

    public string $slugSource = 'title';

    protected $fillable = [
        'author_id', 'category_id', 'slug', 'title', 'excerpt', 'body',
        'locale', 'status', 'published_at', 'meta_json',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'meta_json'    => 'array',
            'view_count'   => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'acme_blog_article_tag', 'article_id', 'tag_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_PUBLISHED)
            ->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }
}
