# 03 · 迭代里程碑

每个里程碑列出：**包**、**交付物**、**验收标准**、**依赖**、**典型工期**。工期为参考（按 1 个全职后端 + 0.5 前端估），用于排序，不是承诺。

---

## M0 · Starter Skeleton（1–2 周）

**包**：`acme/contracts`, `acme/starter`, `acme/support`

**交付物**
- Composer 元数据规范、ServiceProvider 基类（自动 publish/load）
- `php artisan acme:modules` 列出已装包
- `php artisan acme:install <pkg>` 与 `acme:uninstall <pkg>`（迁移 + 配置 + 资源 + 种子）
- 统一异常 → JSON / HTML 响应、API Resource 基类、Pagination 宏
- 通用 Trait：`HasUlid`, `HasTranslations`, `Sluggable`
- 测试基线（Testbench、SQLite 内存库、CI 模板）
- 演示宿主 `apps/demo`：装上 starter，首页返回"Hello"

**验收**：从零 `composer create-project laravel/laravel demo && composer require acme/starter` 后 `php artisan acme:modules` 正确列出。

---

## M1 · Auth & Admin Shell（2–3 周）

**包**：`acme/auth`, `acme/rbac`, `acme/admin`, `acme/user-center`

**交付物**
- `auth`：注册/登录/找回/2FA（TOTP）/SSO 接口/邀请/登录日志/会话管理
- `rbac`：能力扫描装载、Role/Permission CRUD、Policy 自动注册、`@can()` 工作
- `admin`：管理后台外壳——顶栏 + 侧栏 + 仪表盘骨架，**只有外壳**；从各包的 `navigation.php` 聚合菜单
- `user-center`：个人资料、密码、安全、登录设备
- 共同的"账号树"：User → Profile → Role(s) → Capability(ies)

**验收**
- 装上后 `/admin/login` 与 `/login` 都可用
- super-admin 默认拥有所有 capability
- 新包注册的 capability 与菜单立刻出现在 `admin` 里

---

## M2 · CMS Core（3–4 周）

**包**：`acme/cms-core`, `acme/media`, `acme/i18n`, `acme/seo`

**交付物（核心：渲染管线）**
- 概念模型与表（详见 [04-cms-rendering.md](04-cms-rendering.md)）：
  - `Theme`（资源集合）
  - `Layout`（包含 `slots`）
  - `Page`（实例，绑定 Layout + 内容）
  - `Slot`（命名占位）
  - `Block`（落到 Slot 的可重排单元）
  - `Widget`（无状态/弱状态可复用片段，可被 Block 嵌入）
  - `Component`（最小渲染单元，Blade Component / Livewire）
- Block / Widget / Component **注册中心**：包提供 `registers Block: HeroBlock`，CMS 自动发现
- 主题装载：theme 提供视图覆盖、资源、字体、CSS 变量
- 多语言：所有内容字段透明本地化
- `media`：上传、转码占位、关联
- `seo`：sitemap.xml 自动生成、meta 注入

**验收**
- 安装一个 "default" 主题包后，可以拖出一个 Page，选 Layout，往 slot 里填 Block，访问 URL 渲染正确
- 切换主题不丢失内容
- 同一 Layout 在不同 Page 上展示不同 Block

---

## M3 · CMS Admin & Theme Authoring（2–3 周）

**包**：`acme/cms-admin`

**交付物**
- 主题选择 / 切换 / 预览
- 布局编排：拖拽 Block 到 Slot，inline 编辑字段
- 草稿 / 发布 / 定时发布 / 版本回滚
- 菜单 / 导航 / 友情链接
- Theme 开发 CLI：`php artisan acme:theme:make <name>`

**验收**
- 非技术编辑可以独立完成"新建着陆页"全过程
- 任意 Block 改动可回滚到任意历史版本

---

## M4 · Blog（1–2 周）

**包**：`acme/blog`

**交付物**：文章 / 分类 / 标签 / 评论（含审核） / RSS / 邮件订阅 / 作者主页 / 阅读量。
所有"列表 / 详情 / 归档"页都是 CMS 的 Page，由 `BlogArticleBlock`、`BlogListBlock` 等 Block 装填——**复用** CMS 渲染。

**验收**：能用 CMS 编排出一个"博客首页 + 文章详情"站点。

---

## M5 · Catalog（2 周）

**包**：`acme/catalog`

**交付物**：产品 / 分类 / 品牌 / 规格（SKU 维度） / 多图 / 价格（仅展示）/ 筛选器 Block。

**验收**：搭出"产品展示官网"，**没有**加购物车按钮。

---

## M6 · Membership & Subscription（2 周）

**包**：`acme/membership`

**交付物**：等级 / 福利 / 订阅计划（一次性 / 周期）/ 试用 / 续订 / 暂停 / 取消 / 计费事件。
**不**绑定具体支付——只发"待收款"事件，由 `checkout` 或独立 billing 包响应。

**验收**：一个用户可订阅"金卡 ￥99/月"，进入会员区，到期降级。

---

## M7 · Cart（2 周）

**包**：`acme/cart`

**交付物**：游客 / 登录态购物车合并 / 优惠券引擎 / 税费策略 / 运费策略 / 多币种。
对外暴露 `CartContract`，结帐时由 `checkout` 消费。

**验收**：从 catalog 商品页加购，刷新、登录、跨设备均不丢失。

---

## M8 · Checkout & Payments（3 周）

**包**：`acme/checkout`, `acme/payments`（gateway 抽象 + 至少 1 个实现，如 Stripe）

**交付物**：地址 / 选物流 / 选支付 / 下单 / 支付回调 / 订单状态机 / 发票草稿。
`payments` 提供 `PaymentGateway` 接口，每个真实网关一个子包：`acme/payments-stripe`。

**验收**：完整跑通"加购→下单→支付→订单确认"，可切换网关。

---

## M9 · Commerce Plus（4+ 周）

**包**：`acme/commerce`

**交付物**：库存（多仓）/ 退换 / 评价 / 营销活动（满减、套餐、限时）/ 积分 / 推荐。

**验收**：能支撑一个中等规模的独立电商。

---

## M10 · CRM（3–4 周）

**包**：`acme/crm`

**交付物**：客户 / 联系人 / 商机 / 销售管线 / 跟进活动 / 任务 / 邮件模板。

**验收**：销售可以在后台从线索到成单全程操作。

---

## M11 · ERP（4–6 周）

**包**：`acme/erp`

**交付物**：采购单 / 入库 / 供应商 / 库存盘点 / 物流回写 / 简版 HR（员工、考勤）。
通过事件与 `commerce` / `crm` 双向同步库存与客户。

**验收**：commerce 卖出一单 → ERP 自动扣减库存 → 触发补货建议。

---

## M12 · Finance（3–5 周）

**包**：`acme/finance`

**交付物**：总账（科目 / 凭证）/ 发票（开具 / 收 / 红冲）/ 应收应付 / 银行对账 / 标准三表 / 多组织。

**验收**：从 commerce 与 erp 流入的业务凭证，能在 finance 出资产负债表。

---

## M13 · LMS（3–4 周）

**包**：`acme/lms`

**交付物**：课程 / 章节 / 课时 / 视频 / 测验 / 进度 / 证书 / 与 membership 联动（订阅解锁）。

**验收**：用户订阅会员 → 可看课 → 完成测验得证书。

---

## 跨里程碑的横向工作流

每个里程碑都要顺手做：
- **CHANGELOG** 更新与 tag
- **Smoke test**：从空仓库装到当前里程碑包，跑通端到端
- **Capability 清单** 同步到 `docs/capabilities.md`
- **升级指南**（如果是 major）
- **演示宿主 `apps/demo`** 增加对应展示
