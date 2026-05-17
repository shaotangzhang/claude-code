# acme/notifications

> 把上游业务事件（订单 / 退货 / 库存 / 文章）翻译成通知，按用户偏好通过多通道发出。

## 依赖
- [acme/checkout](../checkout)
- [acme/commerce](../commerce)
- [acme/blog](../blog)

## 架构

```
                                                          ┌─ MailChannel
OrderPaid / OrderFulfilled / ReturnRequested / ...        │   (Laravel mail)
            │                                              ├─ LogChannel
            ▼                                              │   (psr logger)
       Listener (在本包) ──→ Dispatcher::dispatch(event,   ├─ SmsChannel
            │                       payload)               │   (sub-package)
            │                          │                   └─ WebhookChannel
            │                          ▼                       (sub-package)
            │                  resolveChannels()
            │                  (config + 用户偏好)
            │                          │
            ▼                          ▼
            写 acme_notifications_log（每次尝试都留痕）
```

## 通道
| key | 实现 |
| --- | --- |
| `mail` | `MailChannel` —— 走 Laravel `Mail` |
| `log`  | `LogChannel` —— 写 PSR 日志（ops 用，stock.low 默认走这条） |

新通道（SMS / WhatsApp / Webhook / WeChat）写一个 `Channel` 实现 + 在自己 SP `$registry->register(new MyChannel())` 即可。

## 事件映射

`config/notifications.php` 的 `events` 数组决定每个 event_type 默认走哪些通道：

```php
'order.paid'      => ['mail'],
'stock.low'       => ['log'],
'article.published' => ['mail'],
```

## 用户偏好

`acme_notifications_preferences` 一行 = `(user_id, event_type, channel, enabled)`。用户在个人中心关闭"邮件订单通知"，就把对应行 `enabled=false`。Dispatcher 用 diff 把禁用的通道从默认通道列表里剔除。

## 不在 0.1 范围（0.2）
- Blade mailable 模板（目前只有 plain text body）
- 多语言通知文案（i18n 化 subject/body）
- Queue 化投递（高吞吐时把 dispatch 改成 dispatch-now-or-queue）
- 用户中心的偏好管理 UI
