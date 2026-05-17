<?php

declare(strict_types=1);

namespace Acme\CmsCore\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Layout extends Model
{
    use HasUlid;

    protected $table = 'acme_cms_layouts';

    protected $fillable = ['theme_id', 'key', 'name', 'template', 'slots_json'];

    protected function casts(): array
    {
        return ['slots_json' => 'array'];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }
}
