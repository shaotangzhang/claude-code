# 05 · 扩展模型：下游项目如何吃这套框架

## 1. 三种扩展场景

| 场景 | 应该做什么 | 不应该做什么 |
| --- | --- | --- |
| **A. 通用能力缺失**（"所有项目都会用到"） | 改/扩 现有 upstream 包，发新版本 | 在客户项目壳里写 fork |
| **B. 客户/项目专属功能** | 新建 `client-x/<pkg>` 包，依赖必要的 upstream 包 | 改 upstream |
| **C. 仅是配置/视觉差异** | 新建 `acme-theme-<name>` 或在项目壳 `config/` 中 override | 复制视图到项目里硬改 |

## 2. 一个真实项目的形态

```
project-acme-mall/
  composer.json
    require:
      acme/auth: ^1
      acme/cms-core: ^1
      acme/blog: ^1
      acme/catalog: ^1
      acme/cart: ^1
      acme/checkout: ^1
      acme/payments-stripe: ^1
      acme/commerce: ^1
      acme-theme-mall: ^1     # 这个项目自己的主题包
      client-acme-mall/blocks: ^0.1  # 项目专属 Block
  app/Providers/
    ProjectServiceProvider.php   # 注册项目专属事件监听 / 路由
  config/
    acme/cms-core.php            # override 一些默认
  routes/web.php                 # 只放真正"独属"路由
```

`app/` 几乎是空的。所有"功能"都是 composer 依赖。

## 3. 客户项目专属包的骨架

```
client-acme-mall/blocks/
  composer.json   # require acme/cms-core
  src/
    BlocksServiceProvider.php
    Blocks/
      DoubleElevenCountdownBlock.php
      VipLoungeBannerBlock.php
    Widgets/
      VipBadgeWidget.php
  resources/views/
  tests/
```

**关键**：客户特殊需求 → 新 Block / Widget / Listener。`Block` 是这个架构里"客户化的天然单位"。

## 4. 何时升级 / fork upstream

| 信号 | 处理 |
| --- | --- |
| 3 个以上项目都自己写过类似的客户包 | 把它合并进 upstream，发新版 |
| upstream 的 API 不够灵活 → 客户包不得不做反射/魔法绕过 | 给 upstream 加扩展点（hook / event / contract），而不是 fork |
| 客户对 upstream 行为有破坏性要求 | 用 **Decorator + Contract Binding**：绑一个自家的实现替换 upstream 默认实现，**不改 upstream 代码** |

## 5. 替换 upstream 实现的标准姿势

```php
// 在 ProjectServiceProvider::register()
$this->app->bind(
    \Acme\Checkout\Contracts\TaxCalculator::class,
    \ClientAcme\Mall\Tax\CnVatCalculator::class
);
```

Upstream 包必须：所有"可能被替换"的能力都对外暴露 `Contract`，并默认绑定到一个内置实现。这是契约式架构的核心成本，但也是单向依赖能成立的前提。

## 6. 数据迁移与回退

- 升级 upstream major 版本前：**先在 staging 演练**，跑 upstream 自带的 `php artisan acme:<pkg>:upgrade-check`。
- 每个 upstream 包必须提供 `tests/Upgrade/<From>To<To>Test.php`。
- 回退路径：所有迁移必须实现 `down()`，且不丢用户数据（破坏性变更需先做 backup 命令）。

## 7. 演化路径（一个具体客户项目的真实节奏）

1. **第 1 周**：装 `starter+auth+cms-core+blog`，跑通"公司官网 + 博客"。
2. **第 2 周**：加 `catalog`，把"产品展示"用 Block 拼出来。
3. **决定要卖货**：加 `cart+checkout+payments-stripe`。
4. **要做会员**：加 `membership`。
5. **运营要做活动**：开 `client-x/blocks` 写"双 11 倒计时 Block"。
6. **后端做 CRM**：加 `crm`，与 `auth` 自动共用客户 = 用户身份。
7. **要做财务**：加 `erp + finance`。

每一步：**改 composer.json + migrate + 配置**，不应该有 `app/` 里的业务代码增长。

## 8. 反模式（禁止）

- 在项目壳里复制 upstream 包的视图后硬改 → **永远**通过 Theme override 或新 Block。
- 在项目壳里写 `Model extends Acme\...\Article` 再悄悄替换 → 用 Contract + Binding。
- 把"项目本周才发现的需求"提交到 upstream 主分支 → 先在客户包里活几个月，**沉淀**了再上行。
- "为了好看"复制粘贴 upstream 的代码到客户包里"改一改" → 一定继承 / 装饰 / 监听。

## 9. 衡量这套架构成功的指标

- 一个新项目从 0 到上线"博客 + 产品展示"应在 **2 周**内（多数时间花在视觉与内容，不是代码）。
- upstream 包的 issue 中，"客户专属需求"占比应趋近于 **0**。
- 客户项目壳里的代码行数应保持 **小于 2000 行**（除模板与配置）。
- 升级 upstream minor 版本应 **不需要** 改客户项目代码。
