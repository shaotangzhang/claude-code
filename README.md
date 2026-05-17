# Laravel 12+ 模块化平台路线图

本仓库**不**包含运行时代码，它是一组规划文档，定义一个基于 Laravel 12+ 的、以 Composer 包为单位的、可层层扩展的 Web 平台。

核心原则：
- **一切皆包**：每个能力域（Auth、CMS、Blog、Cart…）都是独立的 Composer 包，自带 ServiceProvider、迁移、配置、视图、资源、测试。
- **单向依赖**：依赖关系是 DAG，下游依赖上游，上游永不感知下游。Starter → Auth → CMS → 业务包。
- **约定优于配置**：包名、命名空间、表前缀、能力（permission）键、事件名、视图命名空间、资源 publish 路径都遵循同一套规则。
- **可演进**：每一次"真实项目"都是 1) 选择已有包 + 2) 写一个薄薄的项目壳。新需求要么落到现有包，要么开新包，**永远不**改下游业务壳里的下游代码。

## 文档索引

| 文档 | 内容 |
| --- | --- |
| [docs/01-architecture.md](docs/01-architecture.md) | 包分层、依赖图、命名空间约定 |
| [docs/02-conventions.md](docs/02-conventions.md) | 包开发规范：迁移、配置、事件、能力、视图、资源 |
| [docs/03-roadmap.md](docs/03-roadmap.md) | M0–M13 迭代里程碑，每阶段交付物与验收 |
| [docs/04-cms-rendering.md](docs/04-cms-rendering.md) | Theme → Layout → Blade → Slot → Block + Widget/Component 渲染模型 |
| [docs/05-extension-model.md](docs/05-extension-model.md) | 下游项目/客户化包如何扩展上游包 |

## 速读：依赖链

```
starter
  └── auth ────────────────┐
       ├── cms-core ───────┼── blog
       │    └── cms-admin  ├── catalog ──── cart ──── checkout ──── commerce
       │                   └── lms
       ├── membership ─────┘                          │
       └── crm ──────────────────────────── erp ──────┴──── finance
```

> 注：`erp` 同时依赖 `commerce` 与 `crm`；`finance` 依赖 `erp`。`lms` 依赖 `cms-core` 与 `membership`。

## 速读：里程碑

```
M0  Starter Skeleton                  基础设施 / 模块加载器 / 安装器
M1  Auth & Admin Shell                用户、角色、权限、Admin/User Center
M2  CMS Core (Rendering Pipeline)     Theme/Layout/Slot/Block/Widget/Component
M3  CMS Admin & Theme Marketplace     主题切换、可视化编排
M4  Blog                              文章、分类、评论、订阅
M5  Catalog                           产品展示（无购物车）
M6  Membership & Subscription         会员等级、计费周期
M7  Cart                              购物车、优惠券、税运费
M8  Checkout & Payments               下单、支付网关抽象、订单
M9  Commerce Plus                     库存、退换、评价、营销
M10 CRM                               客户、商机、销售管线
M11 ERP                               采购、库存、HR
M12 Finance                           账务、发票、对账、报表
M13 LMS                               课程、章节、测验、证书
```

详见 [docs/03-roadmap.md](docs/03-roadmap.md)。
