<?php

declare(strict_types=1);

namespace Acme\Blog\Models;

use Acme\Auth\Models\User;
use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasUlid;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_SPAM     = 'spam';
    public const STATUS_TRASH    = 'trash';

    protected $table = 'acme_blog_comments';

    protected $fillable = [
        'article_id', 'parent_id', 'author_user_id',
        'author_name', 'author_email', 'body', 'status', 'ip', 'user_agent',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }
}
