# acme/returns-portal

> 用户中心 RMA 自助门户。**只是 UI 壳**——所有状态变更都走 [acme/commerce](../commerce) 的 `ReturnService`。

## 依赖
- [acme/checkout](../checkout)（订单从这里来）
- [acme/commerce](../commerce)（`ReturnService::request` 是真正的写者）

## 路由

| Method | URI | Name | 守护 |
| --- | --- | --- | --- |
| GET  | `/account/returns` | `acme.returns-portal.index` | `auth` |
| GET  | `/account/returns/create/{orderId}` | `acme.returns-portal.create` | `auth` + 订单归属 |
| POST | `/account/returns/create/{orderId}` | `acme.returns-portal.store` | 同上 |
| GET  | `/account/returns/{return}` | `acme.returns-portal.show` | `auth` + RMA 归属 |

控制器一律检查 `$rma->user_id === currentUserId()`——隔离不同用户的退货。

## 流程

```
GET /account/returns
    │
    ▼
列出该用户的全部 RMA（分页）
    │
    ▼ "Start a return" button on an order
GET /account/returns/create/{orderId}
    │
    ▼
表单：勾选 order line + qty + condition + per-item reason + overall reason
    │
    ▼
POST → PortalController::store
    │
    ▼
ReturnService::request($order, $userId, $items, $reason)
    ├─ commerce 校验：order 必须 paid、在退货窗口内
    ├─ 写 acme_commerce_returns + acme_commerce_return_items
    └─ dispatch ReturnRequested 事件
                  │
                  ▼
       acme/notifications listener
                  │
                  ▼
       发邮件给用户 + ops 邮箱
```

## 与 admin 后台的关系

本包只覆盖**用户侧**。管理员审核 / 收货 / 退款流程仍走 `/admin/commerce/returns`（commerce 的 admin scaffold）和 `ReturnService::approve/markReceived/refund`。

## 0.2

- 退货物流号录入（用户填快递单号，admin 看到）
- 上传照片（依附 acme/media）
- 跨语言（i18n 化 condition 标签）
- 用户撤销已提交但未审核的 RMA
