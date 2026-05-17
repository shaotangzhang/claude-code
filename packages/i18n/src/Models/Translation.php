<?php

declare(strict_types=1);

namespace Acme\I18n\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;

class Translation extends Model
{
    use HasUlid;

    protected $table = 'acme_i18n_translations';

    protected $fillable = ['translatable_type', 'translatable_id', 'field', 'locale', 'value'];
}
