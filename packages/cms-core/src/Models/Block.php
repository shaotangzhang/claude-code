<?php

declare(strict_types=1);

namespace Acme\CmsCore\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    use HasUlid;

    public $timestamps = false;

    protected $table = 'acme_cms_blocks';

    protected $fillable = ['page_version_id', 'slot_key', 'position', 'block_type', 'data_json', 'locale'];

    protected function casts(): array
    {
        return ['data_json' => 'array', 'position' => 'integer'];
    }

    public function pageVersion(): BelongsTo
    {
        return $this->belongsTo(PageVersion::class, 'page_version_id');
    }
}
