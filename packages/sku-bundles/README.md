# acme/sku-bundles

> "买套餐" —— 把 N 个 SKU 打包成一个可购买单元。加车时展开成 N 行 cart_items（带 `bundle_source_key`），价差通过 `CartAdjustmentProvider` 自动以折扣形式呈现。

## 依赖
- [acme/cart](../cart) ≥ 0.2
- [acme/catalog](../catalog)

## 数据

| 表 | 用途 |
| --- | --- |
| `acme_bundles` | (key, slug, name, price_cents, currency) |
| `acme_bundle_items` | (bundle_id, sku_id, quantity) |
| `acme_cart_items.bundle_source_key` | 新增列，把一组 child line 绑成同个 sourceKey（每次添加唯一） |

## 流程

```
POST /cart/bundles  body: bundle_id
   │
   ▼
BundleService::addToCart
   ├─ 校验 active + currency + 非空
   ├─ 生成 sourceKey = "bundle:<key>:<ulid-suffix>"
   ├─ 为每个 child SKU 创建 cart_item（带 bundle_source_key + PriceResolver 价）
   └─ 重算 totals
                │
                ▼
   BundleAdjustmentProvider 触发
        每个 sourceKey 一组 child 行
        sum(line_total) − bundle.price_cents = saving (负数)
        ├─ saving < 0 → 发一条 CartAdjustment (discount)
        └─ saving >= 0 → 不发（"套餐反而贵了" 就别给客户看)

DELETE /cart/bundles/{sourceKey}
   └─ 删所有该 sourceKey 的 cart_item → 重算
```

## 多次加同一 bundle

允许：每次 add 都生成新的 sourceKey 后缀。两份"Summer Pack" = 两组 child + 两条 saving 折扣。

## 路由

| Method | URI | Name |
| --- | --- | --- |
| POST   | `/cart/bundles`            | `acme.cart.bundles.add` |
| DELETE | `/cart/bundles/{sourceKey}` | `acme.cart.bundles.remove` |

## 与 gift / 普通 line 的区别

| 字段 | gift | bundle child | 普通 |
| --- | --- | --- | --- |
| `is_gift` | true | false | false |
| `gift_source_key` | ✓ | null | null |
| `bundle_source_key` | null | ✓ | null |
| subtotal 贡献 | 计入 | 计入 | 计入 |
| 自动折扣 | 100% of line | bundle saving | 0 |
| 用户可改 | 否 | 否（应有 guard，0.2 加） | 是 |

## 待 0.2
- CRUD 后台 UI
- 防止用户直接 update/remove child line（沿 gift 模式 guard）
- 多档 bundle（不同子组合不同价）
- bundle 详情页 + `bundle.detail` Block
- 库存检查：bundle 整体可售 = min(child available / qty)
