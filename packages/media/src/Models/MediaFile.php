<?php

declare(strict_types=1);

namespace Acme\Media\Models;

use Acme\Support\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaFile extends Model
{
    use HasUlid;

    protected $table = 'acme_media_files';

    protected $fillable = ['disk', 'path', 'mime', 'size', 'alt', 'meta_json', 'uploaded_by'];

    protected function casts(): array
    {
        return ['meta_json' => 'array', 'size' => 'integer'];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
