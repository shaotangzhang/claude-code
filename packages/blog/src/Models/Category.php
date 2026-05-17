<?php

declare(strict_types=1);

namespace Acme\Blog\Models;

use Acme\Support\Concerns\HasUlid;
use Acme\Support\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUlid, Sluggable;

    protected $table = 'acme_blog_categories';

    public string $slugSource = 'name';

    protected $fillable = ['parent_id', 'slug', 'name', 'description', 'position', 'locale'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }
}
