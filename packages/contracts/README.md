# acme/contracts

> 跨包共享的纯接口与 DTO。**永不**引入任何下游包，**禁止**包含任何业务逻辑或实现。

任何包想暴露"可被替换的能力"，必须在此处定义 contract，然后由提供者在自家 ServiceProvider 里绑定实现。

## 当前清单
| 命名空间 | 用途 |
| --- | --- |
| `Acme\Contracts\Module\ModuleRegistry` | 已装模块的查询接口 |
| `Acme\Contracts\Module\ModuleManifest` | 模块元数据 DTO |
| `Acme\Contracts\Module\Installer` | 模块安装/卸载接口 |
