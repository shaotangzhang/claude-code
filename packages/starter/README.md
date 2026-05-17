# acme/starter

> **职责**：所有 `acme/*` 包的地基。模块注册中心、安装器、ServiceProvider 基类、CLI。

## 依赖
- [acme/contracts](../contracts)
- [acme/support](../support)

## 安装
```bash
composer require acme/starter
php artisan acme:modules            # 列出所有已装的 acme 模块
php artisan acme:install <key>      # 发布配置/迁移 + migrate
php artisan acme:uninstall <key>    # 反向操作（--with-data 会真正删表）
```

## 配置 (`config/acme/starter.php`)
| Key | 默认 | 用途 |
| --- | --- | --- |
| `modules.allow` | `null` | 仅启用列表中的模块（逗号分隔） |
| `modules.deny` | `null` | 排除列表中的模块 |
| `id_strategy` | `ulid` | 全局 ID 策略：`ulid` / `snowflake` / `auto` |
| `admin.prefix` | `admin` | 管理后台路由前缀 |
| `api.prefix` / `api.version` | `api` / `v1` | API 路由前缀与版本 |

## 命令
| 命令 | 说明 |
| --- | --- |
| `acme:modules [--json]` | 列出已装模块 |
| `acme:install {key} [--seed]` | 安装模块 |
| `acme:uninstall {key} [--with-data]` | 卸载模块 |

## 扩展点
其它 `acme/*` 包继承 `Acme\Starter\Support\PackageServiceProvider`，设置 `$key` 与 `$root` 即可自动获得：
- config merge + publish
- migrations / views / lang 加载
- web / admin / api 路由文件加载
- `acme-<key>-config|views|migrations` publish tags

## 模块清单 (`composer.json`)
```json
"extra": {
  "laravel": { "providers": ["Acme\\YourPkg\\YourPkgServiceProvider"] },
  "acme": {
    "module": {
      "key": "your-pkg",
      "title": "Your Package",
      "version": "0.1.0",
      "layer": 3,
      "depends": ["cms-core"]
    }
  }
}
```
