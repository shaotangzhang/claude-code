# 04 · CMS 渲染模型

> 这是整个平台的"美学核心"。这一层做错了，所有上层主题与业务页面都会被牵着走。

## 1. 术语与边界

| 概念 | 一句话 | 比喻 |
| --- | --- | --- |
| **Theme** | 一套视觉资源 + 视图覆盖 + 默认 Layout 集合 | "皮肤包" |
| **Layout** | 一个 Blade 框架文件，含若干命名 **Slot** | "页面骨架" |
| **Page**（CMS 实体） | 绑定 Layout + 一组 Block 实例 + 路由 | "一篇内容" |
| **Slot** | Layout 中的命名占位（如 `header`, `main`, `sidebar`） | "格子" |
| **Block** | 落到 Slot 中、可排序的内容单元，有数据 + 渲染逻辑 | "积木" |
| **Widget** | 可被 Block 或 Layout 嵌入的小复用片段（菜单、搜索框、最新评论） | "标准件" |
| **Component** | 最小 Blade Component / Livewire，**没有持久化数据**，只接收 props | "螺丝钉" |

层级关系（**包含 ⊇**）：
```
Theme ⊇ Layout ⊇ Slot ⊇ Block ⊇ (Widget | Component)
```

层级关系（**渲染时**）：
```
Page → 选 Layout → 渲染 Layout → 每个 Slot 找到自己挂的 Block 列表
     → 逐个 Block 渲染 → Block 内部用 Widget / Component 组装
```

## 2. 为什么这么切？

- **Theme** 是替换最频繁的（设计迭代）；
- **Layout** 是结构性的（页面骨架在主题升级里大体稳定）；
- **Block** 是内容编辑频繁触碰的（落地页天天调）；
- **Widget** 是跨页面复用（导航、订阅条）；
- **Component** 是开发者写的最小单元。

每一层的**变更频率不同**、**变更人不同**、**复用半径不同**——分层后让"内容编辑改 Block"不波及"前端工程师改 Component"，"主题设计师换 Theme"不丢"运营调好的 Block 排版"。

## 3. 数据模型

```
themes
  id, key (unique), name, version, screenshot, manifest_json, active(bool)

layouts
  id, theme_id (nullable, null=共享), key, name, template_path, slots_json
  -- slots_json: [{key:"header", label:"Header", allowed:["MenuBlock","HeroBlock"], max:1}, ...]

pages
  id, layout_id, slug, title, status(draft|scheduled|published),
  publish_at, locale, meta_json, version_id (current)

page_versions
  id, page_id, snapshot_json, author_id, note, created_at
  -- snapshot_json 内含 blocks 排布与字段值

block_types          -- 由包注册时落库（只读元数据，便于查询）
  id, key, name, package, schema_json, preview_path

blocks
  id, page_version_id, slot_key, position, block_type, data_json, locale

widgets              -- 全局可重用
  id, key, type, data_json, scopes_json
  -- scopes: 哪些页面/路由/角色可见

theme_settings       -- 主题级开关（色、字体）
  id, theme_id, key, value_json
```

> **不可变快照**：`page_versions.snapshot_json` 是该版本的完整可渲染数据。发布只是把 `pages.version_id` 指向某个 version。回滚 = 改指针。

## 4. 注册中心

包通过 ServiceProvider 注册：

```php
BlockRegistry::register(HeroBlock::class);
WidgetRegistry::register('latest-posts', LatestPostsWidget::class);
LayoutRegistry::register('default', resource_path('views/layouts/default.blade.php'));
```

每个 `BlockType` 类必须提供：
- `key()`、`label()`、`icon()`
- `schema()` —— 字段 schema（用于后台动态表单）
- `render(array $data, array $context): View`
- `preview(array $data): View`（编排器里用）
- `validate(array $data)`（提交时）

## 5. 渲染管线

```
RouteResolver(url)
  → Page (locale, draft?preview-token : published)
    → Layout
      → 取 layout.template_path 渲染：
          <html>...
            @slot('header')   ── 由 SlotRenderer 输出 blocks(slot='header')
            @slot('main')     ── 同上，按 position 排序
            ...
          </html>
```

`SlotRenderer`：
```php
foreach ($blocks as $block) {
    $type = BlockRegistry::resolve($block->block_type);
    yield $type->render($block->data, $context); // context: page, locale, user, theme
}
```

**视图查找优先级**（高 → 低）：
1. 当前 Theme override：`themes/<active>/views/<pkg>/<view>.blade.php`
2. Theme 包视图命名空间：`acme-theme-<name>::`
3. 业务包视图命名空间：`acme-<pkg>::`
4. Fallback `acme-cms-core::`

由 `cms-core` 在 boot 期把 active theme 路径**前置**到 view paths。

## 6. 缓存

- **Page HTML 全页缓存**：key = `page:{id}:{version}:{locale}:{role-bucket}`
- **Block 片段缓存**：每个 Block 可声明 `cache_ttl` 与 `cache_keys()`（默认 = data hash）
- **失效**：发布新版本 → 推送 `PagePublished` → 监听器清除对应前缀
- **ESI / Lazy**：动态 Block（购物车、用户名）用 Livewire / HTMX 异步占位，不破坏全页缓存

## 7. 扩展点（每个上层包如何接入）

| 上层包 | 提供的扩展 |
| --- | --- |
| `blog` | `BlogArticleBlock`（详情）、`BlogListBlock`（列表）、`LatestPostsWidget` |
| `catalog` | `ProductBlock`、`ProductGridBlock`、`CategoryFilterWidget` |
| `cart` | `MiniCartWidget`（异步） |
| `commerce` | `RecommendBlock`、`PromoBannerBlock` |
| `lms` | `CourseBlock`、`CourseProgressWidget` |

> 业务包**不**创建自己的"前台模板系统"——所有展示都退化为"给 CMS 注册 Block / Widget"。这是这个架构的关键纪律。

## 8. Theme 作为独立包

主题也是 Composer 包：`acme-theme-<name>`，依赖 `acme/cms-core`，提供：
- `views/` 视图覆盖
- `public/` 资源（publish 到宿主 public）
- `layouts/` 至少一个 Layout
- `theme.json` manifest（颜色、字体、可调 token）

切换主题 = 改 `themes.active`，**内容数据完全不动**。

## 9. 反模式（禁止）

- 在 Block 的 `data_json` 里塞 HTML 字符串 —— data 是结构化的，渲染由 Block 类决定。
- 在主题里写业务查询 —— 主题只做展示，业务通过 Block / Widget 注入。
- 给单个客户项目写一个"特殊 Block"放进 `acme/blog` —— 应该开一个项目专属包 `client-x/blocks` 注册自己的 Block。
- 直接读取 `blocks` 表 —— 永远通过 `PageRenderer` / `SlotRenderer`，因为缓存与版本逻辑在这里。
