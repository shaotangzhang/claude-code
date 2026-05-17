# acme/catalog

> 产品展示包：**仅展示，无购物车**。产品 / 分类 / 品牌 / SKU 变体 / 多图 / 价格。

## 依赖
- [acme/cms-core](../cms-core)
- [acme/media](../media)（图片）

## 数据模型

```
Brand ─┐
       ├─ Product ── SKU* (variants with price, attrs_json)
Category┘
```

- `Product` = master，每个产品对应一个 slug 与详情页
- `Sku` = 一个具体可标价的变体（颜色 + 尺寸等），`price_cents` + `list_price_cents` 让 SKU 直接支持促销价对比
- `Money::format()` 简单格式化，**只用于展示**——一旦要做跨币种算术，宿主层换上专业 money 库

## 与 CMS 的集成（4 个 Block）

| Block key | 类 | 用途 |
| --- | --- | --- |
| `catalog.product` | `ProductBlock` | 单产品详情（标题/品牌/画廊/描述/SKU 表） |
| `catalog.product-grid` | `ProductGridBlock` | 产品网格，支持 query string 过滤（category/brand/price/order） |
| `catalog.category-filter` | `CategoryFilterBlock` | 侧栏过滤器，链接生成 query string |
| `catalog.featured` | `FeaturedProductsBlock` | 推荐产品横幅，可手选 IDs 或自动取最新 N 个 |

> 用 CMS 编排"分类着陆页"：装一个 product-grid + category-filter 在一个 page 上即可。

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET | `/catalog/{slug}` | `acme.catalog.products.show` |

分类 / 品牌的列表页**不是**写死的路由——它们是 CMS Page，用 `catalog.product-grid` block 装。

## 价格

- 数据库存 `price_cents` 整数 + `currency` 三字母代码
- 展示统一走 `Money::format($cents, $currency)`，由 `config('acme.catalog.currency.*')` 决定符号 / 位置 / 小数位
- 默认币种与对应符号在 config 里改

## M5 状态 & 后续
- CRUD 后台 UI 是 scaffold（0.2 跟 cms-admin 一起做出一致的编辑体验）
- "按价格排序"网格目前 fallback 到 id 排序——需 join 子查询，0.2 加
- 库存只是 `stock_label` 字符串（"In stock" 等展示用）；真正的库存扣减在 M9 commerce
