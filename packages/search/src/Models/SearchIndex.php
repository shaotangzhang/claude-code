<?php

declare(strict_types=1);

namespace Acme\Search\Models;

use Illuminate\Database\Eloquent\Model;

class SearchIndex extends Model
{
    public $timestamps = false;

    protected $table      = 'acme_search_index';
    protected $primaryKey = 'product_id';
    public $incrementing  = false;
    protected $keyType    = 'string';

    protected $fillable = [
        'product_id', 'locale', 'title', 'brand', 'category',
        'searchable_text', 'min_price_cents', 'max_price_cents',
        'attrs_json', 'indexed_at',
    ];

    protected function casts(): array
    {
        return [
            'attrs_json'      => 'array',
            'min_price_cents' => 'integer',
            'max_price_cents' => 'integer',
            'indexed_at'      => 'datetime',
        ];
    }
}
