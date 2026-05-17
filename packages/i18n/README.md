# acme/i18n

> 内容翻译。**与** Laravel 自带的"接口翻译"互补：那个是 PHP/Blade 字符串翻译，这个是 **DB 内的内容字段**翻译。

## 两种翻译落地方式
1. **JSON 列**（轻量）：模型用 `Acme\Support\Concerns\HasTranslations`，字段存为 `{locale: value}` JSON。适合标题、摘要等少量字段。
2. **本包提供的多态表**（重型）：`acme_i18n_translations` 表 + `TranslationStore`。适合长正文、大量字段、需要按 locale 索引 / 全文检索的场景。

```php
$store = app(\Acme\I18n\Support\TranslationStore::class);
$store->set($article, 'body', 'zh-CN', '正文…');
$body = $store->get($article, 'body', app()->getLocale());
```
