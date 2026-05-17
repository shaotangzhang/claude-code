# acme/multi-currency-pricing

> 同 SKU、多币种独立定价。装上即把 cart 的 `PriceResolver` 从 default 单币种切到 price-book 表查询。

## 依赖
- [acme/cart](../cart) ≥ 0.4（需要 `PriceResolver` 抽象）
- [acme/catalog](../catalog)

## 数据
```
acme_pricing_sku_prices
  (sku_id, currency, price_cents, list_price_cents, active)
```

每 SKU × currency 至多一条 active 行。

## 用法

录入：

```php
SkuPrice::create([
    'sku_id'           => $product->skus[0]->id,
    'currency'         => 'EUR',
    'price_cents'      => 1899,
    'list_price_cents' => 2299,
    'active'           => true,
]);
```

之后任何 EUR currency 的 cart 添加这个 SKU 时，自动用 €18.99，老的 USD-locked SKU 在 USD currency 下照常工作。

## 优雅降级

`PriceBookResolver::priceFor`：
1. 查 `acme_pricing_sku_prices` active 行 → 返回
2. 否则 fall back 到 SKU 自身的 `price_cents`（仅当 SKU.currency 匹配）
3. 否则 `null` —— cart 拒绝加入并清晰报错

所以**渐进迁移**很自然：先把热门 SKU 录到 price-book，其它的留着 SKU.price_cents 跑，整套切换无 downtime。

## 后续 (0.2)
- 汇率自动换算 fallback（priceFor 找不到 → 用 base currency × rate）
- 价格历史归档（变价时存旧档便于回看）
- "promo price" 与 list price 时间窗口
