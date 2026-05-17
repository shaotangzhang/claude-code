<?php

declare(strict_types=1);

namespace Acme\CmsAdmin\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    use HasUlid;

    protected $table = 'acme_cms_menus';

    protected $fillable = ['key', 'label', 'locale'];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('position');
    }
}
