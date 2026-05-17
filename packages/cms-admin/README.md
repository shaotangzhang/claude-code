# acme/cms-admin

> CMS 编辑后台：页面 CRUD、版本管理、草稿/发布/回滚、主题切换、菜单、`acme:theme:make` 脚手架。

## 依赖
- [acme/admin](../admin)（后台外壳）
- [acme/cms-core](../cms-core)（数据与渲染）

## 工作流模型

```
                ┌──────────────────────────┐
                │  Page (slug, layout,     │
                │       current_version_id)│
                └──────────────┬───────────┘
                               │ 1:N
                               ▼
                ┌──────────────────────────┐    ← 不可变快照
                │  PageVersion             │
                │     snapshot_json        │
                │     blocks (1:N)         │
                └──────────────────────────┘
```

- **draft** = 任何 `id != page.current_version_id` 的 PageVersion
- **publish** = `page.current_version_id ← draft.id`，并把 blocks 反序列化到 `snapshot_json`
- **rollback** = `page.current_version_id ← historical_version.id`
- 历史版本永不被改写或删除（除非 `acme.cms-admin.version_history_limit` 配额裁剪）

## 服务（DI 可注入）
| 服务 | 职责 |
| --- | --- |
| `PageDraftService::createFrom($page)` | 从当前版本克隆出一个可编辑的 draft |
| `PageDraftService::discard($draft)` | 删除 draft（拒绝删 current） |
| `SlotEditor::replace($draft, $blocks)` | 对 draft 整体重写 blocks；调用每个 block 的 `validate()` |
| `PagePublishService::publish($page, $version, ?$at)` | 切换 current 指针；可选定时发布 |
| `PageRollbackService::restore($page, $version)` | 改 current 指针回到历史 version |
| `ThemeActivationService::activate($theme)` | 切换 active 主题；触发 view-finder 重新前置 |

## 事件
| Event | 何时触发 |
| --- | --- |
| `PageDraftCreated` | draft 落库后 |
| `PagePublished` | 任意发布或定时发布完成后 |
| `PageRolledBack` | 回滚指针成功后 |
| `ThemeActivated` | 主题切换提交后 |

下游包用这些事件做"清缓存"、"重生成 sitemap"、"通知作者"等副作用。

## 路由（前缀 `/{ACME_ADMIN_PREFIX:-admin}/cms`）

| Method | URI | Name |
| --- | --- | --- |
| GET  | `/pages` | `acme.cms.admin.pages.index` |
| GET  | `/pages/{page}/edit` | `acme.cms.admin.pages.edit` |
| PUT  | `/versions/{version}` | `acme.cms.admin.versions.save` |
| POST | `/pages/{page}/publish/{version}`  | `acme.cms.admin.pages.publish` |
| POST | `/pages/{page}/rollback/{version}` | `acme.cms.admin.pages.rollback` |
| GET  | `/themes` | `acme.cms.admin.themes.index` |
| POST | `/themes/{theme}/activate` | `acme.cms.admin.themes.activate` |
| GET  | `/menus` | `acme.cms.admin.menus.index` |
| PUT  | `/menus/{menu}` | `acme.cms.admin.menus.update` |

## CLI

| 命令 | 用途 |
| --- | --- |
| `acme:theme:make <key>` | 从 stubs 复制出新主题目录（含 composer.json、theme.json、ServiceProvider、默认 Layout） |
| `acme:cms:theme:activate <key>` | 切换 active 主题 |

## 菜单
M3 交付 schema + 模型 + 读写控制器；前端 JS 树形编辑器留给后续小版本。

## M3 状态 & 后续
- 视图层是结构化的表单脚手架（textarea + JSON），可工作但不"WYSIWYG"。真正的拖拽编辑器（Livewire / Alpine / 自渲染前端）建议放下一个小版本 `0.2.0`。
- 不在范围：版本快照的差异对比 UI、并发编辑锁、协作多人编辑。
