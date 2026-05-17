# acme/abandoned-cart

> 检测 idle 购物车 → 标记 abandoned → 通过 notifications 发恢复链接 → 客户点击复活。完全通过 cart 0.5 的状态机 + notifications 的 dispatcher 接入，**无需修改 cart 或 notifications**。

## 依赖
- [acme/cart](../cart) ≥ 0.5
- [acme/notifications](../notifications)

## 数据

迁移在 `acme_cart_carts` 上加 3 列：
- `recovery_token` (uniq nullable) —— 单 cart 一 token
- `abandoned_at` —— 被标记时刻；TTL 由此算
- `reminded_at` —— 最近发提醒时刻（用于将来"二轮提醒"逻辑）

## 状态机

```
                          ┌──────────────┐
                          │   active     │
                          └──────┬───────┘
                                 │   updated_at < now - idle_hours
                                 │   AND non-gift item count >= min_items
                                 ▼
   acme:abandoned-cart:tick
                                 │
              AbandonmentService::mark()
                                 │
              ┌──────────────────┼──────────────────┐
              │                  │                  │
        status=abandoned    abandoned_at = now   recovery_token minted
                                 │
                                 ▼
                       dispatch CartAbandoned event
                                 │
                                 ▼
                  SendRecoveryReminder listener
                                 │
                                 ▼
                 notifications.Dispatcher → mail / sms / webhook
                                 │
                                 ▼
                  user clicks /cart/recover/{token}
                                 │
                AbandonmentService::recover()
                                 │
                                 ▼
                         status=active again
                         （绑到 auth 用户，如果当时已登录）
```

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET | `/cart/recover/{token}` | `acme.abandoned-cart.recover` |

## 命令

```
php artisan acme:abandoned-cart:tick           # 跑一次
php artisan acme:abandoned-cart:tick --dry-run # 只看会标记谁，不写库
```

建议每 10–30 分钟跑一次（Laravel scheduler）：

```php
// app/Console/Kernel.php
$schedule->command('acme:abandoned-cart:tick')->everyTenMinutes()->withoutOverlapping();
```

## 与 notifications 的解耦

- 包**自动**为 `cart.abandoned` 设默认通道（`config('acme.abandoned-cart.default_channels')`，默认 `['mail']`），未配置 `acme.notifications.events.cart.abandoned` 时生效
- 宿主项目想改：直接在自家 config 里 override 该 key
- 装上 `acme/notifications-sms` 后，把 `['mail','sms']` 加进去即可双通道发

## TTL 与防滥发

- `token_ttl_hours` (默认 72h) —— 链接过期后控制器拒收
- `batch_limit` (默认 200) —— 单次 tick 上限，防雷暴
- `reminded_at` —— 已记录，第二轮 / 第三轮提醒留 0.2 实现（需要 idle 后再 idle 的二次判断）

## 0.2 规划

- 多轮提醒（24h / 72h / 7d 多段，每段不同邮件模板 + 不同折扣码）
- 后台报表（abandoned/recovered/value-lost 趋势）
- 自动作废老 abandoned 行（30d 后状态改 archived）
- 与 commerce 的 campaigns 联动："放弃 24h → 发 10% coupon"
