# acme/search

> Catalog 全文搜索 + facet 过滤。默认 `DatabaseDriver` 用 `LIKE`，可替换为 MeiliSearch / Elasticsearch。

## 依赖
- [acme/catalog](../catalog)
- [acme/cms-core](../cms-core)（搜索框 Block）

## 架构

```
Product.saved → (listener — 0.2)  ┐
                                  ▼
                            IndexBuilder ───→ Driver.upsert
                                                    │
                                                    ▼
                                              [search index]
                                                    ▲
                                                    │
SearchController ── filters ──→ Driver.search ──────┘
```

## 数据

`acme_search_index` 一行一个 product，含：
- denormalized title / brand_slug / category_slug / searchable_text
- min_price_cents / max_price_cents（用于价格区间过滤）
- attrs_json

## Driver 抽象

`Acme\Search\Drivers\Driver`：
- `upsert(productId, document)`
- `delete(productId)`
- `search(filters, page, perPage)` → `{items, total, facets}`

替换实现：
```php
// In your host SP
$this->app->singleton(\Acme\Search\Drivers\Driver::class, \YourCo\MeiliDriver::class);
```

## 命令

```
php artisan acme:search:reindex            # 全量重建
php artisan acme:search:reindex --locale=zh-CN
```

## Block

`search.box` —— 放页面任意 slot 作搜索框。提交跳 `/search`。

## 0.2 路线
- 监听 `Product` saved/deleted 自动 reindex
- MeiliSearch / Elasticsearch / OpenSearch 子包
- 搜索建议（autocomplete API）
- 拼写纠错（Did you mean）
- Stop-words / 同义词配置
