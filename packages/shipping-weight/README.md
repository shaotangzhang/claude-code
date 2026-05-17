# acme/shipping-weight

> Weight-bracket shipping. 总重落在 bracket 区间内 → 该 bracket 的费率。

## 依赖
- [acme/cart](../cart) ≥ 0.3
- [acme/catalog](../catalog)（FK 到 SKU）

## 数据
| 表 | 用途 |
| --- | --- |
| `acme_shipping_sku_weights` | 每 SKU 一行（克），含可选 W/H/D（毫米） |
| `acme_shipping_weight_brackets` | 重量档（min_g – max_g, max=null 表示无上限） |

录入 SKU 重量 + 重量档后，结账页自动出现匹配的运费选项。无重量数据的 SKU 视为 0 g。

## 与 acme/shipping-zones 并存

两个包都注册到 cart 的 `ShippingMethodRegistry`，并存出现在 checkout。运营可只装一个、也可两个都装让客户选。
