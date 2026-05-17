<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasUlid;

    protected $table = 'acme_cms_menu_items';

    protected $fillable = ['menu_id', 'parent_id', 'label', 'route', 'url', 'position', 'attrs_json'];

    protected function casts(): array
    {
        return ['attrs_json' => 'array', 'position' => 'integer'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position');
    }

    public function href(): ?string
    {
        if ($this->route) {
            try {
                return route($this->route);
            } catch (\Throwable) {
                return null;
            }
        }

        return $this->url;
    }
}
