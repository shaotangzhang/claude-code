# apps/demo

> Dogfood host that installs the entire `acme/*` stack and seeds a tiny commerce scenario. Designed to be the smoke-test for any new milestone.

## What gets installed

All current packages (M0–M13), in 4 layers:

```
L0  contracts · support · starter
L1  auth · rbac · admin · user-center
L2  cms-core · cms-admin · media · i18n · seo
L3  blog · catalog · membership · cart · payments · checkout · commerce
    · wishlist · search · notifications · loyalty-redemption
    · multi-currency-pricing · shipping-zones · shipping-weight
    · payments-stripe · payments-paypal
```

## First-run

```bash
cd apps/demo
composer install                       # symlinks every packages/* via path repo

cp .env.example .env
php artisan key:generate

# Create db (mysql or sqlite), then:
php artisan migrate                    # runs migrations from EVERY installed pkg
php artisan db:seed                    # idempotent demo data
php artisan acme:modules               # prints the 26 modules + their layers
php artisan acme:rbac:sync-capabilities

php artisan serve
```

Open `http://localhost:8000` —

| URL | What you'll see |
| --- | --- |
| `/blog/hello-world`      | cms-core renders an article using the default layout |
| `/catalog/acme-tee`      | catalog product detail page |
| `/cart`                  | empty cart; add tee → see "Launch week 10% off" auto-applied |
| `/checkout`              | place order; choose `manual` payment gateway |
| `/admin`                 | login as super@acme.test / secret123, see nav from all packages |
| `/search?q=tee`          | DatabaseDriver result |
| `/sitemap.xml`           | seo package output |

## Smoke checks

After seed:

```bash
php artisan acme:modules            # all 26 listed, no errors
php artisan acme:search:reindex     # > Indexed 1 products
php artisan acme:membership:tick    # > expired 0 / due 0 / ...
```

## Per-package smoke

| Package | Demo |
| --- | --- |
| auth        | `/login` form, super user pre-seeded |
| rbac        | super-admin role granted, capabilities synced |
| cms-core    | every other front-end page reuses the default layout |
| blog        | seeded article + RSS feed `/blog/feed.xml` |
| catalog     | one product / SKU / brand / category |
| cart        | guest → login merge works; coupons code; tax/shipping totals |
| membership  | gold-monthly plan; subscribe → trial → renew |
| checkout    | full flow with Manual gateway (admin confirms in /admin/payments/transactions) |
| commerce    | stock reserved on OrderPaid; loyalty points awarded |
| campaigns   | "launch-week" auto-applied 10% off; bundle/bxgy/freebie writable in admin in 0.2 |
| shipping-zones | US zone with USPS Ground rate |
| wishlist    | logged-in users see heart in nav |
| search      | reindex command + /search results |
| notifications | OrderPaid → mail (log driver) |
| loyalty-redemption | /cart/loyalty/apply once a user has points |

## Reset

```bash
php artisan migrate:fresh --seed
```

## What this catches that unit tests don't

- **Module discovery**: an SP that doesn't extend `PackageServiceProvider` or has a typo in `$key` will fail `acme:modules`.
- **Migration ordering**: foreign keys hit dependencies — if pkg A's migration references pkg B's table, install order matters. Demo catches it.
- **Container bindings**: pkg X overrides a contract from pkg Y → demo's seed runs end-to-end or breaks loudly.
- **View finder order**: theme overrides only show up once a theme is registered.
- **Route name collisions**: any two packages registering the same route name throws at boot.
