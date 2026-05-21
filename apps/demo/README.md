# apps/demo

> Dogfood host for the full `acme/*` stack. Real Laravel 12 application that wires up all 42 packages via path-repository symlinks.

## Quick start

```bash
cd apps/demo
composer install                       # symlinks all packages/* via path repo
cp .env.example .env
php artisan key:generate

# Pick a database in .env (mysql / pgsql / sqlite), then:
php artisan migrate --force            # runs migrations from every installed pkg
php artisan db:seed --force            # idempotent demo data
php artisan acme:modules               # prints the 42 modules + their layers
php artisan acme:rbac:sync-capabilities

php artisan serve
```

Open `http://localhost:8000/_welcome` for a smoke landing page; `/_modules` lists every installed package; `/admin` is the back office (login `super@acme.test` / `secret123`).

详细启动文档见仓库根的 [docs/07-running-the-demo.md](../../docs/07-running-the-demo.md)。

## What gets installed

42 acme/* packages across 4 layers — full list in [docs/06-package-catalog.md](../../docs/06-package-catalog.md).

## Smoke routes per package

| Package | URL |
| --- | --- |
| auth        | `/login` |
| cms-core    | every front-end page reuses the default layout |
| blog        | `/blog/hello-world`、`/blog/feed.xml` |
| catalog     | `/catalog/acme-tee` |
| cart        | `/cart` (with seeded 10%-off campaign auto-applied) |
| checkout    | `/checkout` → place order with Manual gateway |
| commerce    | stock reserved on OrderPaid; loyalty points awarded |
| membership  | `/membership/plans` + `/account/membership` |
| wishlist    | `/account/wishlist` |
| search      | `/search?q=tee` |
| seo         | `/sitemap.xml` |
| returns-portal | `/account/returns` |
| admin shell | `/admin` |

## Useful commands

```bash
php artisan acme:modules                       # full catalog
php artisan acme:search:reindex                # populate search index
php artisan acme:membership:tick               # advance subscription state machines
php artisan acme:abandoned-cart:tick --dry-run # preview abandoned-cart sweep
php artisan acme:inventory-fefo:expiring --days=30
php artisan acme:inventory-fefo:auto-discount --days=14 --percent=20 --dry-run
php artisan acme:theme:make boutique           # scaffold a new theme package
composer smoke                                  # alias for acme:modules
composer fresh                                  # migrate:fresh --seed
```

## Reset

```bash
php artisan migrate:fresh --seed
```

## Layout

```
apps/demo/
├── app/
│   ├── Models/User.php             # extends Acme\Auth\Models\User + HasRoles
│   └── Providers/AppServiceProvider.php
├── bootstrap/
│   ├── app.php                     # Laravel 12 minimal bootstrap
│   └── providers.php
├── config/                         # standard Laravel config (app, auth, db, ...)
├── database/
│   ├── factories/
│   └── seeders/DatabaseSeeder.php  # seeds super user + 1 article + 1 SKU + plan + zone
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── robots.txt
├── resources/views/demo/           # /_welcome and /_modules
├── routes/
│   ├── web.php                     # demo smoke routes
│   └── console.php                 # scheduled ticks
├── storage/                        # Laravel storage skeleton (gitignored)
├── tests/                          # add Feature tests here
├── .env.example
├── .gitignore
├── composer.json
└── phpunit.xml
```

## What this catches that unit tests don't

- **Module discovery** — typo in `$key` fails `acme:modules`
- **Migration ordering** — pkg A migration referencing pkg B's table catches dependency mistakes
- **Container bindings** — overlapping rebinds become loud
- **View finder order** — theme overrides only show up once a theme is registered
- **Route name collisions** — any two packages registering the same route name throws at boot
- **`composer install` symlink permission** — some FS / hosts reject symlinks; `--prefer-source` works around
