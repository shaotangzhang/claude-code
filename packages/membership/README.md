# acme/membership

> 会员等级 + 订阅计划 + 计费状态机。**不绑定具体支付** —— 通过 `PaymentDue` / `PaymentReceived` 事件与计费包解耦。

## 依赖
- [acme/rbac](../rbac)（可选自动角色绑定）
- [acme/cms-core](../cms-core)（注册定价 Block）

## 数据模型

```
Tier ─┐  (level, perks_json)
      │
      └── Plan ── Subscription ── SubscriptionEvent (audit log)
       (billing_period,
        price_cents, currency,
        trial_days)
```

- `Tier` — 用户的"等级"概念（free/silver/gold），有 `level`（int）用于比较
- `Plan` — 一个具体可订阅的计划，绑定一个 tier 与计费周期（once/monthly/quarterly/yearly）
- `Subscription` — 用户的某次订阅实例；有完整状态机
- `SubscriptionEvent` — 所有状态变更的不可变审计日志

## 状态机

```
       start (trial_days>0)
              ↓
         ┌─Trialing─┐──trial ends, free──→ Active
         │          │──trial ends, paid──→ PastDue (PaymentDue emitted)
         │
         ↓ start (trial_days=0)
       Active ─── period_end ──→ PastDue ── grace exceeded ──→ Expired
         │           ↑                │
         │           └─ payment received
       pause ↓                        │
        Paused ── paused_until past ──→ Active
        cancel ↓
      Canceled (immediate, or at period_end via tick)
```

### 与支付包的契约

| Membership emits | 计费包应该 |
| --- | --- |
| `PaymentDue` | 发起一次扣款；成功则 `PaymentReceived` |
| `PaymentReceived`（自己 listen） | `SubscriptionService::recordPayment()` 推进 period 与状态 |

→ `acme/checkout`、`acme/payments-stripe` 之后会注册 `PaymentDue` listener；本包**永远不**碰 stripe / paypal / wechat-pay。

## 服务

| 服务 | 职责 |
| --- | --- |
| `SubscriptionService::start($userId, $plan)` | 创建订阅。zero-price → 直接 Active；trial → Trialing；paid+no-trial → 触发 PaymentDue |
| `SubscriptionService::recordPayment($sub)` | 推进 period，Active |
| `SubscriptionService::pause/resume/cancel/expire` | 状态机过渡，幂等 |
| `TierResolver::forUser($userId)` | 返回当前最高级 active tier；`null` 即无会员 |

## Tick 命令

`php artisan acme:membership:tick` —— 推进状态机，建议每 5-15 分钟跑一次：
- Active 周期到期 → 触发 `PaymentDue` + PastDue
- PastDue 超过 `grace_days` → Expired
- Trialing 试用结束 → 转 Active（免费）或 PastDue（付费）
- Paused `paused_until` 过期 → 自动 Resume
- `cancel_at_period_end=true` 且周期到期 → Canceled 立即生效

支持 `--dry-run` 看会做什么但不写库。

## CMS 集成

注册 `membership.plans` Block —— 在任意 CMS Page 拖一个上去就是定价页。

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET    | `/membership` | `acme.membership.show` |
| POST   | `/membership/subscribe` | `acme.membership.subscribe` |
| DELETE | `/membership/{sub}` | `acme.membership.cancel` |
| POST   | `/membership/{sub}/pause` | `acme.membership.pause` |
| POST   | `/membership/{sub}/resume` | `acme.membership.resume` |

## rbac 自动同步（可选）

`config('acme.membership.tier_to_role')` 把 tier key 映射到 rbac role key；订阅开始 → 加角色，过期 → 移角色。留空即关闭。
