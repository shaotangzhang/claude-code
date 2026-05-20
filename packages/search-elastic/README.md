# acme/search-elastic

> Elasticsearch driver for [acme/search](../search). Sibling of [search-meili](../search-meili) —— 同模式，换 backend。

## 依赖
- [acme/search](../search)
- 一个可达的 Elasticsearch（7.x / 8.x）

## 配置 `.env`

```
ELASTIC_HOST=http://elastic:9200
ELASTIC_API_KEY=<base64-encoded>     # 推荐
# 或：
ELASTIC_USERNAME=elastic
ELASTIC_PASSWORD=changeme
ELASTIC_INDEX_PREFIX=acme_products
```

## 工作机制

`ElasticClient` 是 thin REST 包装（不绑 elastic 官方 SDK）。`ElasticDriver` 实现 `Acme\Search\Drivers\Driver`：

| 操作 | ES 端点 |
| --- | --- |
| upsert | `PUT /<index>/_doc/<id>` |
| delete | `DELETE /<index>/_doc/<id>` |
| search | `POST /<index>/_search`，bool query + multi_match + filter + aggs |
| ensureIndex | `PUT /<index>` with mappings（idempotent） |

mapping 里 `attrs_json` 是 `enabled:false`（不索引），其余字段都正常索引。`brand`/`category` 是 `keyword`（精确匹配 + facet）。

`SearchElasticServiceProvider::packageRegister()` 把 `Driver::class` 重绑给 `ElasticDriver`——`IndexBuilder` / `SearchController` / `acme:search:reindex` 透明换 backend。

## 索引初始化

```php
app(\Acme\SearchElastic\ElasticDriver::class)->ensureIndex('en');
// PUT /acme_products_en with mappings — idempotent
```

## 与 Meili 选哪个？

| Meili | Elastic |
| --- | --- |
| 安装一行 docker，开箱即用 | 需要 JVM + 调优 |
| 单机 / 中小目录 | 集群 / 大目录 / 复杂分析 |
| 默认 typo-tolerant | 默认 BM25，typo 需配 fuzziness |
| 简单 filter 表达式 | 完整 query DSL，可做嵌套聚合 |

两个都装 = 一个绑给 `Driver::class` 用，另一个保留只跑实验。`Driver::class` 是单绑定，谁后绑就用谁——通常宿主 `AppServiceProvider` 显式选。

## 0.2

- 多 locale 自动 fan-out（删除）
- bulk endpoint 优化批量 reindex
- 同义词 / 拼写纠错（aggregation suggester）
