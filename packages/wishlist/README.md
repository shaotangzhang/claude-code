# acme/wishlist

> 用户心愿单（多列表）+ "Move to cart" 流。

## 依赖
- [acme/cart](../cart)（move-to-cart 直接调 `CartService::addItem`）
- [acme/catalog](../catalog)（FK 到 SKU）
- [acme/cms-core](../cms-core)（header mini-summary Block）

## 数据
| 表 | 用途 |
| --- | --- |
| `acme_wishlist_lists` | 一个用户可有多个命名列表（默认 1 个，"Birthday" / "Wedding" 等） |
| `acme_wishlist_items` | `(list_id, sku_id)` 唯一；加同样的 SKU 是 idempotent |

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET    | `/wishlist`                       | `acme.wishlist.show` |
| POST   | `/wishlist/items`                 | `acme.wishlist.items.add` |
| DELETE | `/wishlist/items/{item}`          | `acme.wishlist.items.remove` |
| POST   | `/wishlist/items/{item}/to-cart`  | `acme.wishlist.items.to-cart`  (附挂 `CartIdentifier` 中间件) |

## 服务

`WishlistService`：
- `defaultListFor($userId)`：取/建默认列表
- `addItem($userId, $sku, ?$list, ?$note)`：幂等
- `removeItem($item, $userId)`：守护所有权
- `moveToCart($item, $userId, $cart, $quantity, $keepInWishlist)`：复用 `CartService::addItem`，可选保留心愿项

## 事件
- `WishlistItemAdded`
- `WishlistItemMovedToCart`

## 与 cart 的协作

`moveToCart` 走 `acme/cart` 的 `CartService::addItem`——意味着：
- 货币不一致会被 cart 自己挡掉
- 数量上限沿用 `acme.cart.max_quantity_per_line`
- 加入瞬间触发 cart 的 `ItemAdded` + `TotalsCalculator` 重算（含 campaigns/coupons/loyalty）

## 不在范围（0.2+）
- 游客 wishlist（用 cookie + 登录合并，沿用 cart 的模式）
- 心愿单分享（公开链接 / 协作）
- 缺货回补提醒（监听 commerce 的 `StockReserved` 反向逻辑）
- 价格降价通知
