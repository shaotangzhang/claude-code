# acme/media

> 媒体库骨架：文件登记表 + 多态附件表。

M2 仅交付：表结构、`MediaFile` 模型、capability。**上传 / 转码 / CDN 适配 留给后续小版本**（拟挂 controller 或 Livewire 组件）。

## 表
- `acme_media_files`
- `acme_media_attachments`（多态：`attachable_type` + `attachable_id` + `role` 主键）

## 用法（后续）
```php
class Article extends Model {
    public function cover(): MorphOne {
        return $this->morphOne(MediaFile::class, 'attachable')->where('role', 'cover');
    }
}
```
