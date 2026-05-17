# acme/user-center

> 前台个人中心外壳。Profile / Security / Devices —— 都是 scaffold 视图。

## 依赖
- [acme/auth](../auth)

## 路由
默认挂在 `/account` 下，可通过 `acme.user-center.prefix` 改。

## 扩展
其它包想加项（例如订阅、订单），在自己 `src/navigation.php` 里输出 `area: 'user-center'` 的 NavigationItem 即可，会自动出现在用户中心侧栏。
