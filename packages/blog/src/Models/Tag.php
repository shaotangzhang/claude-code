<?php

declare(strict_types=1);

namespace Acme\Blog\Models;

use Acme\Support\Concerns\HasUlid;
use Acme\Support\Concerns\Sluggable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasUlid, Sluggable;

    protected $table = 'acme_blog_tags';

    public string $slugSource = 'name';

    protected $fillable = ['slug', 'name', 'locale'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'acme_blog_article_tag', 'tag_id', 'article_id');
    }
}
