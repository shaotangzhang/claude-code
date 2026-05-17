# acme/auth

> 用户、登录/注册/找回、2FA scaffolding、SSO 接口、邀请、会话日志。

## 依赖
- [acme/starter](../starter) → contracts + support

## 数据表
- `acme_auth_users` — 用户主表（ULID + soft delete + 2FA 字段）
- `acme_auth_sessions` — 会话记录（用于"在哪些设备登录"）
- `acme_auth_login_log` — 登录尝试日志（成功/失败/锁定）
- `acme_auth_invitations` — 邀请 token

## 能力
见 [src/capabilities.php](src/capabilities.php)：`auth.user.{view,create,update,delete,invite}`、`auth.session.{view,revoke}`。

## 扩展点
- 替换 `\Acme\Contracts\Auth\UserResolver` 绑定即可注入自家"当前用户"概念。
- 实现 `\Acme\Contracts\Auth\TwoFactorProvider` 并绑定，可换 TOTP / WebAuthn / 短信。
- 实现 `\Acme\Contracts\Auth\SsoProvider`，每个 IdP 一个实现，配 `acme.auth.sso.providers`。

## M1 状态
本里程碑只完成"骨架 + scaffold 视图"。具体 UX（Breeze / Fortify / 自写）留给后续小版本。
