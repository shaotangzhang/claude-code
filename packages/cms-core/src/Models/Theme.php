<?php

declare(strict_types=1);

namespace Acme\CmsCore\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasUlid;

    protected $table = 'acme_cms_themes';

    protected $fillable = ['key', 'name', 'version', 'screenshot', 'manifest_json', 'active'];

    protected function casts(): array
    {
        return [
            'manifest_json' => 'array',
            'active'        => 'bool',
        ];
    }
}
