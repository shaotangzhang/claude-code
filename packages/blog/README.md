# acme/blog

> 博客业务包：文章 / 分类 / 标签 / 评论 / RSS / 邮件订阅。**第一个真正的业务层（layer 3）包**——示范了上层包如何与 cms-core 集成。

## 依赖
- [acme/cms-core](../cms-core) — 通过 `BlockRegistry` 注入 Block 类型；通过 `acme-cms-core::layouts.default` 渲染详情页

## 表
- `acme_blog_articles` — 文章主表（ULID + soft delete + 多状态 + view_count）
- `acme_blog_categories` — 分类（自引用 parent_id）
- `acme_blog_tags` + `acme_blog_article_tag` — 标签 + pivot
- `acme_blog_comments` — 评论（嵌套 + 审核状态）
- `acme_blog_subscriptions` — 邮件订阅（token + confirmed_at + unsubscribed_at）

## 与 CMS 的集成（**这是这个包的关键模式**）

`BlogServiceProvider::packageBoot()` 在 `BlockRegistry` 被解析时注册三个 Block 类型：

| Block key | 类 | 用途 |
| --- | --- | --- |
| `blog.article` | `ArticleBlock` | 渲染一篇文章 |
| `blog.article-list` | `ArticleListBlock` | 渲染文章列表，可按 category/tag 过滤 |
| `blog.latest-posts` | `LatestPostsBlock` | 侧栏最新文章片段 |

→ 编辑去 `/admin/cms/pages` 编排：用 CMS Page 拼出 `/blog`（list block）、`/blog/category/{slug}`（list + 过滤）等。详情页 `/blog/{slug}` 由本包路由处理。

## 路由

| Method | URI | Name |
| --- | --- | --- |
| GET  | `/blog/feed.xml` | `acme.blog.rss` |
| POST | `/blog/subscribe` | `acme.blog.subscribe` |
| GET  | `/blog/subscribe/confirm/{token}` | `acme.blog.subscribe.confirm` |
| GET  | `/blog/unsubscribe/{token}` | `acme.blog.unsubscribe` |
| POST | `/blog/{slug}/comments` | `acme.blog.comments.store` |
| GET  | `/blog/{slug}` | `acme.blog.articles.show` |

> 路径前缀通过 `ACME_BLOG_PREFIX` 配置；详情 URL `/{slug}` 路由必须排在订阅/RSS/评论之后，否则会把它们都吞进来。

## 事件

| Event | 触发位置 |
| --- | --- |
| `ArticlePublished` | 文章发布（host 项目通过监听器做缓存清理 / sitemap 重建 / 推送 webhook） |
| `CommentReceived` | 评论提交后（无论是否进 pending） |
| `SubscriberConfirmed` | 订阅者确认邮箱后 |

订阅邮件本身**不**在这个包内发送 —— 注册一个监听 `SubscriberConfirmed`、`ArticlePublished` 的 mailer 是宿主项目的工作。

## 待定（0.2+）
- 文章 CRUD 后台 UI（数据层 ready，UI 留下个 minor）
- 评论审核队列 UI
- 订阅者邮件投递（具体 mailer / 模板由宿主决定）
- 多语言版本切换器（依赖 acme/i18n 的 multilang slug 策略）
