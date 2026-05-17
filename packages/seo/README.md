# acme/seo

> Sitemap、robots、canonical、OG/Twitter card 元信息。

## 路由
- `/sitemap.xml` — 自动从 `acme_cms_pages` 拉所有 published 的页面，缓存可配。
- `/robots.txt` — 默认放行 + 指向 sitemap。

## 后续扩展
- `SeoMeta` value object：每个 Block 可以在 `RenderContext::bag()` 累积 OG/Twitter/title/desc，PageRenderer 在 `<head>` 一次性输出。
- 多 sitemap 分片（按 locale / per pkg）。
