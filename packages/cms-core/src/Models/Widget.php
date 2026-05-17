<?php

declare(strict_types=1);

namespace Acme\CmsCore\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Widget extends Model
{
    use HasUlid;

    protected $table = 'acme_cms_widgets';

    protected $fillable = ['key', 'type', 'data_json', 'scopes_json'];

    protected function casts(): array
    {
        return ['data_json' => 'array', 'scopes_json' => 'array'];
    }
}
