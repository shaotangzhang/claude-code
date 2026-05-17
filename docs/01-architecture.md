# 01 · 包分层与依赖图

## 1. 分层

我们把包划分为 4 层。**同层之间禁止直接依赖**，必须通过事件 / 接口 / 合约（contracts 包）解耦。

### Layer 0 — Foundation（基础层）
| 包 | 职责 |
| --- | --- |
| `acme/starter` | 模块加载器、统一安装器、CLI 基线、配置约定、测试基线、共享 Trait/Cast/Rule |
| `acme/contracts` | 跨包共享的纯接口与 DTO（无实现，永不引入下游） |
| `acme/support` | 工具函数、UI 元件（无业务）、统一异常、响应宏 |

### Layer 1 — Identity（身份层）
| 包 | 依赖 | 职责 |
| --- | --- | --- |
| `acme/auth` | starter | 用户、会话、密码、2FA、SSO 接口、邀请、登录日志 |
| `acme/rbac` | auth | 角色、权限、能力注册中心、Policy 自动绑定 |
| `acme/admin` | rbac | 管理后台外壳（导航、菜单、面包屑、审计入口）—— 不含业务页面 |
| `acme/user-center` | auth | 用户前台个人中心外壳 |

### Layer 2 — Content（内容层）
| 包 | 依赖 | 职责 |
| --- | --- | --- |
| `acme/cms-core` | rbac | Theme/Layout/Page/Slot/Block/Widget/Component 模型与渲染管线 |
| `acme/cms-admin` | cms-core, admin | 主题选择、布局编排、Block 拖拽、版本与发布 |
| `acme/media` | rbac | 媒体库、上传、转码、CDN 适配 |
| `acme/i18n` | starter | 多语言内容存储与回退策略（区别于 Laravel 内置的接口翻译） |
| `acme/seo` | cms-core | sitemap、robots、canonical、OG/Twitter card |

### Layer 3 — Business（业务层）

#### 前端业务（面向访客 / 会员）
| 包 | 依赖 | 职责 |
| --- | --- | --- |
| `acme/blog` | cms-core | 文章、分类、标签、评论、RSS、订阅 |
| `acme/catalog` | cms-core, media | 产品、分类、规格、价格（仅展示） |
| `acme/membership` | rbac | 会员等级、订阅计划、计费周期、试用 |
| `acme/cart` | catalog | 购物车、优惠券、税费、运费规则 |
| `acme/checkout` | cart, membership | 下单流程、支付网关抽象、订单、发票草稿 |
| `acme/commerce` | checkout | 库存、退换、评价、营销活动、积分 |

#### 后端业务（面向运营）
| 包 | 依赖 | 职责 |
| --- | --- | --- |
| `acme/crm` | rbac | 客户、联系人、商机、销售管线、活动 |
| `acme/erp` | commerce, crm | 采购、库存、供应商、HR、生产 |
| `acme/finance` | erp | 总账、发票、收付款、对账、报表 |
| `acme/lms` | cms-core, membership | 课程、章节、测验、证书、学习进度 |

## 2. 依赖图（详细）

```
                ┌─────────────────────────────────────────┐
                │            acme/contracts                │  (纯接口)
                └─────────────────────────────────────────┘
                                   ▲
                                   │ (所有包都可依赖)
                                   │
        ┌──────────────────────────────────────────────┐
        │                acme/starter                  │
        │   acme/support                               │
        └──────────────────────────────────────────────┘
                                   ▲
                ┌──────────────────┴──────────────────┐
                │             acme/auth                │
                │              acme/rbac               │
                │              acme/admin              │
                │              acme/user-center        │
                └──────────────────────────────────────┘
                                   ▲
        ┌──────────────────────────┴──────────────────────┐
        │         acme/cms-core   acme/cms-admin           │
        │         acme/media      acme/i18n   acme/seo     │
        └──────────────────────────────────────────────────┘
                                   ▲
   ┌──────┬──────────┬─────────────┼──────────┬──────────┬──────┐
   │      │          │             │          │          │      │
 blog  catalog  membership        crm        lms        ...
                    │              │          │
                   cart            │       (cms+membership)
                    │              │
                 checkout          │
                    │              │
                 commerce ─────────┤
                                   │
                                  erp
                                   │
                                finance
```

## 3. 命名与命名空间

| 项 | 约定 | 示例 |
| --- | --- | --- |
| Composer 名 | `acme/<kebab>` | `acme/cms-core` |
| 命名空间 | `Acme\<Pascal>` | `Acme\CmsCore` |
| 配置文件 | `config('acme.<kebab>')` | `config('acme.cms-core.theme')` |
| 表前缀 | `acme_<snake>_` | `acme_cms_core_blocks` |
| 视图前缀 | `acme-<kebab>::` | `view('acme-cms-core::layout.default')` |
| 翻译前缀 | `acme-<kebab>::` | `__('acme-blog::messages.published')` |
| 资源 publish tag | `acme-<kebab>-<asset>` | `acme-cms-core-views` |
| 事件类 | `Acme\<Pkg>\Events\<Verb><Noun>` | `Acme\Blog\Events\ArticlePublished` |
| 能力键 | `<pkg>.<resource>.<action>` | `blog.article.publish` |
| 路由前缀 | `/<pkg-area>/...`（业务面） | `/blog`, `/admin/blog` |

## 4. 单向依赖的强制

- CI 中加入 **依赖方向检查**（如自写 `deptrac` 配置或 `composer why-not`），禁止上层包出现在下层包的 `require` 中。
- 跨包通信优先级：**Event > Contract（接口） > Facade > 直接类引用**。
- 任何包都不得 `use` 比自己高层的具体类。例：`auth` 包不能 `use Acme\CmsCore\...`。
- 业务层之间若要互通（如 `commerce` 需要消费 `crm` 的"客户"概念），通过：
  1. `acme/contracts` 中定义共享接口（`CustomerResolver`）；
  2. 各方提供自己的实现并通过 Service Provider 绑定；
  3. 调用方注入接口，不感知具体实现。

## 5. 物理仓库布局

两种可选，**先 Monorepo，必要时拆分**：

### 5.1 Monorepo（推荐起步）
```
platform/
  packages/
    starter/
    contracts/
    auth/
    cms-core/
    blog/
    ...
  apps/
    demo/              # 一个吃自己狗粮的演示 Laravel 应用
  composer.json        # path repositories 指向 packages/*
```

工具：`symplify/monorepo-builder` 或 `git subtree split` 在 release 时把各 package 推到独立只读仓库（供 packagist 安装）。

### 5.2 Polyrepo
每个包独立仓库，通过 Satis / Private Packagist 发布。**只在团队规模或发布节奏要求分离时再切**。

## 6. 应用壳（Project Shell）

最终的"真实项目"是一个极薄的 Laravel 应用：
```
project-x/
  composer.json   # require acme/auth, acme/cms-core, acme/blog ...
  app/            # 仅项目独有的少量类
  config/         # 仅 override
  resources/      # 仅项目专属主题
  routes/         # 仅项目专属路由
```
原则：**没有业务逻辑应该出现在 `app/` 里**，业务一律下沉到包。
