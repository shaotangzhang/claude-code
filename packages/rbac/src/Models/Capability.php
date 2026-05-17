<?php

declare(strict_types=1);

namespace Acme\Rbac\Models;

use Illuminate\Database\Eloquent\Model;

class Capability extends Model
{
    protected $table = 'acme_rbac_capabilities';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'label', 'group'];
}
