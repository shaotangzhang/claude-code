<?php

declare(strict_types=1);

namespace Acme\CmsCore\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PageVersion extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_cms_page_versions';

    protected $fillable = ['page_id', 'author_id', 'snapshot_json', 'note', 'created_at'];

    protected function casts(): array
    {
        return ['snapshot_json' => 'array', 'created_at' => 'datetime'];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'page_version_id')
            ->orderBy('slot_key')->orderBy('position');
    }
}
