# 02 · 包开发规范

本规范是**所有**包必须遵守的契约。`acme/starter` 提供基类与命令以强制执行其中大部分。

## 1. 包骨架

```
packages/<name>/
  composer.json
  src/
    <Pkg>ServiceProvider.php
    Contracts/          # 包对外的接口（也可以拆到 acme/contracts）
    Models/
    Http/
      Controllers/
      Requests/
      Middleware/
    Console/
    Events/
    Listeners/
    Policies/
    Support/
  config/
    <pkg>.php
  database/
    migrations/
    seeders/
    factories/
  resources/
    views/
    lang/
    css/  js/          # 主题/前端资产
  routes/
    web.php
    admin.php
    api.php
  tests/
    Feature/
    Unit/
  README.md
```

## 2. composer.json

- `type: "library"`
- `extra.laravel.providers` 自动注册 ServiceProvider
- `extra.acme.module` 描述模块元数据（见 §4 Manifest）
- 显式声明 `require` 中所有上游包，**用 `^`** 锁定主版本
- 不依赖任何同层或下层包

## 3. ServiceProvider 模板

每个包的 SP 必须：
1. **register()** 中绑定 contracts → 实现；合并 config（`mergeConfigFrom`）。
2. **boot()** 中：
   - `loadMigrationsFrom`
   - `loadRoutesFrom`（按 web/admin/api 分文件）
   - `loadViewsFrom('acme-<pkg>')`
   - `loadTranslationsFrom('acme-<pkg>')`
   - `publishes([...], 'acme-<pkg>-config' | '-views' | '-assets' | '-migrations')`
   - 注册 Gates / Policies（来自 Capability Registry，见 §6）
   - 注册命令、事件订阅、调度任务
3. **不**在 boot 中触发数据库查询（避免 install 期间出错）。

## 4. Module Manifest

`composer.json` 中：
```json
{
  "extra": {
    "acme": {
      "module": {
        "key": "cms-core",
        "title": "CMS Core",
        "version": "1.0.0",
        "depends": ["auth", "rbac"],
        "capabilities": "src/capabilities.php",
        "navigation": "src/navigation.php",
        "install": ["AcmeCmsCore\\Install\\Setup"]
      }
    }
  }
}
```
`acme/starter` 提供 `php artisan acme:modules` 列出所有已安装模块、依赖关系、版本与健康检查。

## 5. 数据库

- **表前缀**：`acme_<pkg_snake>_`（避免与宿主应用冲突）。
- **迁移命名**：`YYYY_MM_DD_HHMMSS_<pkg>_<verb>_<table>.php`。
- **绝不**在迁移中假设其它包的表已存在；如需外键，写在**专门的关联迁移**里，并放在依赖关系明确的较晚版本号。
- **种子**：只提供"系统必需"种子（角色、权限、默认 Block 类型），示例数据放 `database/seeders/Demo*`，由宿主显式调用。
- **模型 ID**：默认 ULID（字符串 26）。理由：跨包合并、迁移期间不冲突，且对外公开 ID 时安全。可在 `acme/starter` 配置 `acme.id_strategy` 切换为雪花/自增。

## 6. 能力（Capability）与权限

- 每个包在 `src/capabilities.php` 中返回 `['blog.article.create' => '创建文章', ...]`。
- `acme/rbac` 启动时扫描所有模块的 capabilities，写入 `acme_rbac_capabilities` 表。
- Policy 用 capability 键，不写硬编码字符串。
- 角色聚合 capabilities；**包不预设角色**（除 `super-admin` 由 `rbac` 自带），由宿主项目装配。

## 7. 配置与 Override

- 每个包的 `config/<pkg>.php` 中所有配置项**必须**有默认值。
- 宿主 `php artisan vendor:publish --tag=acme-<pkg>-config` 后才能修改。
- **禁止**把"业务开关"塞到 `.env`；`.env` 只放秘密与环境差异。

## 8. 事件 / 监听

- 事件类放 `Events/`，**只承载数据**（实现 `ShouldBroadcast` 视场景）。
- 跨包通信**强制**通过事件而非直接调用：
  - `blog` 发 `ArticlePublished` → `seo` 重生成 sitemap，`crm` 记录用户活动。
- 监听器幂等、可重试；耗时操作走 queue。

## 9. 路由

- 三个分区：`web.php`（前台）/ `admin.php`（后台，挂在 `/<admin-prefix>` 下，由 `admin` 包提供）/ `api.php`（`/api/v1/<pkg>`）。
- 路由名 `acme.<pkg>.<resource>.<action>`，如 `acme.blog.article.show`。
- API 版本化：`/api/v{n}/<pkg>`；破坏性变更升 n，旧版本至少保留一个 minor。

## 10. 视图与主题

- 包内视图永远使用 `acme-<pkg>::` 命名空间。
- 主题可以通过 `cms-core` 的 view finder 优先级覆盖（详见 [04-cms-rendering.md](04-cms-rendering.md)）。
- 包**不**写死 CSS 框架；CMS 渲染层提供"语义化标签 + slot"，主题决定样式。

## 11. 测试

- 每个包带 `phpunit.xml` 与 `tests/`，使用 `orchestra/testbench` 拉起 Laravel。
- 三档：
  - Unit（纯类）
  - Feature（路由 + DB，用 SQLite in-memory）
  - Integration（依赖真实 MySQL/Redis，CI 矩阵跑）
- 覆盖率门槛：Core 包 ≥ 80%，业务包 ≥ 60%。
- 每个包必须有一个 `tests/Smoke/InstallTest.php` 校验"全新装上后能跑 migrate + 主页 200"。

## 12. 版本与发布

- SemVer。`major` 含破坏性 contracts 变更。
- 每个包维护 `CHANGELOG.md`（Keep a Changelog）。
- Monorepo 用 `monorepo-builder release`；polyrepo 用 GitHub Actions tag-and-split。

## 13. 代码风格

- PHP 8.3+，`declare(strict_types=1)`。
- Pint（Laravel preset）+ PHPStan level 8 + Rector（最少配置：php8.3）。
- 所有 public 方法有返回类型；DTO 用 readonly class。

## 14. 安全基线

- 所有写操作走 FormRequest；所有 Policy 默认 deny。
- 文件上传必经 `acme/media`（MIME 嗅探 + 病毒扫描钩子）。
- CSP / HSTS / X-Frame 由 `starter` 中间件默认开启，包不得关闭。
- 任何来自 CMS 的用户输入在渲染前默认 escape；要 raw 必须显式标注且过审。

## 15. 文档

每个包 README 必含：
1. 一句话职责
2. 依赖图（链到上游）
3. 安装：composer require + vendor:publish + migrate
4. 配置项表
5. 路由表
6. 能力清单
7. 事件清单
8. 扩展点（如何注册新 Block / Widget / Gateway）
9. 升级指南（major 之间）
