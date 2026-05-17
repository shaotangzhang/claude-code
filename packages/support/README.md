# acme/support

> 共享工具：traits、casts、响应 helper。每个 `acme/*` 包都会用到。

## 当前 Traits
| Trait | 用途 |
| --- | --- |
| `Acme\Support\Concerns\HasUlid` | 把 `id` 主键改为 ULID 字符串，creating 时自动填充 |
| `Acme\Support\Concerns\Sluggable` | 在 saving 时根据 `$slugSource` 自动生成 `slug` |
| `Acme\Support\Concerns\HasTranslations` | 简易翻译字段：把 JSON 列翻译到当前 locale，带 fallback |
