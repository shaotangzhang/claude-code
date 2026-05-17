# acme/cms-core

> CMS 核心。**Theme / Layout / Page / Slot / Block / Widget / Component** 模型与渲染管线。
> 设计：[docs/04-cms-rendering.md](../../docs/04-cms-rendering.md)。

## 依赖
- [acme/rbac](../rbac)（passive use；任何 capability 注册都通过 contracts）

## 数据表
| 表 | 用途 |
| --- | --- |
| `acme_cms_themes` | 已装主题元数据 + active 标志 |
| `acme_cms_layouts` | 模板入口与 slot 定义 |
| `acme_cms_pages` | 页面（slug + locale + 当前版本指针） |
| `acme_cms_page_versions` | 不可变版本快照（发布只动 `current_version_id` 指针） |
| `acme_cms_blocks` | 落到某 slot 的可重排单元 |
| `acme_cms_widgets` | 全局可复用片段 |

## 核心服务（DI）

| 接口（来自 acme/contracts） | 实现 |
| --- | --- |
| `BlockRegistry` | `InMemoryBlockRegistry` —— `register(class)`、`resolve(key)` |
| `LayoutRegistry` | `InMemoryLayoutRegistry` |
| `ThemeRegistry` | `InMemoryThemeRegistry` |
| `WidgetRegistry` | `InMemoryWidgetRegistry` |
| `PageRenderer` | 编排：取 Page → currentVersion → 渲染 layout 模板 + slots[] |
| `SlotRenderer` | 取 version.blocks，按 slot_key 分组并按 position 排序，逐个 render |

## 自带 Block 类型
- `cms.text` —— 纯文本，escape，自动 paragraph
- `cms.html` —— 受信任 HTML（authoring 需 `cms.html.author` capability）

## 默认 Layout
`default` —— `acme-cms-core::layouts.default`，提供 `head` / `header` / `main` / `sidebar` / `footer` 五个 slot。

## 主题视图覆盖
`CmsCoreServiceProvider::boot()` 在启动期，把当前 active 主题的 `themes/<key>/views` 前置到 view finder，从而**任何**包视图（`acme-blog::article` 等）都可被主题覆盖。Force override 用 `ACME_CMS_FORCE_THEME=<key>`。

## 扩展点（业务包接入示意）

```php
// packages/blog/src/BlogServiceProvider.php
$this->app->resolving(BlockRegistry::class, function ($reg): void {
    $reg->register(\Acme\Blog\Blocks\ArticleBlock::class);
    $reg->register(\Acme\Blog\Blocks\ArticleListBlock::class);
});
```

## 前台路由
默认挂一个 catch-all `/{slug?}` —— 命中已发布 Page 即渲染，否则 404。可通过 `ACME_CMS_MOUNT_CATCH_ALL=false` 关闭，由宿主项目自行装配路由。

## 反模式提醒
- 不要把 HTML 字符串塞 `data_json` —— `data_json` 是结构化字段，渲染由 Block 类决定。
- 不要直接读 `acme_cms_blocks` 表 —— 经 `PageRenderer` / `SlotRenderer`，因为缓存与版本逻辑在这里。
- 客户/项目特有的 Block 写在客户包里（`client-x/blocks`），不要污染 `acme/cms-core`。
