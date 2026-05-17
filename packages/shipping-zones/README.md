# acme/shipping-zones

> Zone-based shipping rates. Group countries into zones, define one or more rates per zone (standard / express / overnight) with optional subtotal windows.

## 依赖
- [acme/cart](../cart) ≥ 0.3（需要 `ShippingMethodRegistry`）

## 数据
| 表 | 用途 |
| --- | --- |
| `acme_shipping_zones` | 区命名（"EU"、"Asia-Pacific"、"North America"...） |
| `acme_shipping_zone_countries` | 国别 → 区，多对多 |
| `acme_shipping_zone_rates` | 每区一到多条费率，含币种 + 可选 subtotal 窗 + 工期 |

## 用法

迁移装上后录入数据（运营/seeder 都行）：

```php
$us = Zone::create(['key' => 'us', 'name' => 'United States']);
$us->countries()->createMany([['country_code' => 'US']]);

ZoneRate::create(['zone_id' => $us->id, 'key' => 'standard', 'label' => 'USPS Ground',
    'cost_cents' => 599,  'currency' => 'USD', 'days_min' => 3, 'days_max' => 7]);
ZoneRate::create(['zone_id' => $us->id, 'key' => 'express',  'label' => 'USPS Priority',
    'cost_cents' => 1499, 'currency' => 'USD', 'days_min' => 1, 'days_max' => 3]);
```

下单时 `ShippingZonesServiceProvider` 已经把 `ZoneRateMethod` 注册到 cart 的 `ShippingMethodRegistry`，每个匹配 zone 的 rate 都会作为一个 `ShippingOption` 出现在 `/checkout` 页面。

## subtotal 窗口

`min_subtotal_cents` / `max_subtotal_cents` 可分段定价 —— 例如美区 $50 以下 $5 邮，$50-$200 是 $3 邮，$200+ 包邮，开 3 条 rate 同 key 不同窗口即可。

## 多币种

`acme_shipping_zone_rates.currency` 是 rate 的一部分；cart 锁定 currency 后只取匹配那一档。同 zone + 同 key 不同 currency 可并存。
