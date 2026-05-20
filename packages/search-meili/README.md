# acme/search-meili

> MeiliSearch driver for [acme/search](../search). Replaces the default `DatabaseDriver` —— `IndexBuilder` / `SearchController` / `acme:search:reindex` 透明走 Meili。

## 依赖
- [acme/search](../search)
- 一个可达的 MeiliSearch 实例（自托管 / Meili Cloud）

## 配置 `.env`
```
MEILI_HOST=http://meili:7700
MEILI_API_KEY=<master or scoped key>
MEILI_INDEX_PREFIX=acme_products       # 每 locale 一个 index：acme_products_en / acme_products_zh
```

## 工作机制

`MeiliClient` 是 thin REST wrapper（v1 API）。`MeiliDriver` 实现 `Acme\Search\Drivers\Driver`：

```
upsert(productId, document)
   ├─ index = "{prefix}_{locale}"
   └─ POST /indexes/{index}/documents

search(filters, page, perPage)
   ├─ compile filters → ["category = \"shoes\"", "brand = \"acme\"", ...]
   ├─ POST /indexes/{index}/search { q, offset, limit, facets, filter }
   └─ map response → {items, total, facets}

delete(productId)
   └─ DELETE /indexes/{index}/documents/{productId}
```

`SearchMeiliServiceProvider::packageRegister()` 把 `Driver::class` 绑给 `MeiliDriver`，老的 `DatabaseDriver` 不再被解析。重新跑 `php artisan acme:search:reindex` 把 catalog 数据导进 Meili。

## 索引初始化

新部署或换 prefix 时：

```php
app(\Acme\SearchMeili\MeiliDriver::class)->ensureIndex('en');
// 创建 index + 设置 filterable attributes（brand/category/min_price_cents/max_price_cents）
```

写入文档前调一次即可；后续 reindex 不重建 index。

## 后续 (0.2)
- 监听 `Product::saved/deleted` 自动 reindex（目前依赖 `acme:search:reindex` 命令）
- `acme:search-meili:bootstrap` 命令把 ensureIndex 包装成 CLI
- 多 locale 自动 swap（删除时不需要 host 提供 locale）
- 同义词 / stop-words / typos 容忍度配置
