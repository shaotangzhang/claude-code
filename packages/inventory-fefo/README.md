# acme/inventory-fefo

> First-Expired-First-Out 库存分配。食品 / 药品 / 化妆品 / 电池等带保质期品类必备。装上即替换 commerce 的默认 `StockAllocator`，整个订单管线自动按"最快过期先发"分配。

## 依赖
- [acme/commerce](../commerce)
- contracts ≥ 0.10（`StockAllocator` 接口）

## 表

| 表 | 用途 |
| --- | --- |
| `acme_inventory_batches` | (sku_id, warehouse_id, lot_code, expiry_date, on_hand, reserved, received_at, supplier_ref) |
| `acme_inventory_allocations` | 每次预留 = 一行；记录 batch_id + reference (order) + state(reserved\|shipped\|released) |

## 流程

```
OrderPaid  → HandleOrderPaid (commerce)
              │
              ▼
       StockAllocator (FefoStockAllocator 已绑定)
              │
              ▼
   FOR EACH SKU line:
     SELECT batches WHERE sku=? AND warehouse=? AND expiry >= today
                    ORDER BY expiry_date ASC, received_at ASC
                    LOCK FOR UPDATE
     walk batches, allocating min(remaining, batch.available)
     write Allocation rows; bump StockLevel summary
     short → throw RuntimeException (one-shot atomic)

OrderFulfilled → batch.on_hand -= alloc.qty   (decrement)
OrderCanceled  → batch.reserved -= alloc.qty  (release, on_hand intact)
```

`acme_commerce_stock_levels` 仍由 fefo 维护成"全部 batches 的 on_hand/reserved 总和"，commerce 的 read path（admin UI、StockLow event 阈值）继续工作。

## 命令

```
php artisan acme:inventory-fefo:receive <sku_id> <warehouse_id> <qty> <yyyy-mm-dd> \
            [--lot=BATCH-001] [--supplier=SUP-A]

php artisan acme:inventory-fefo:expiring --days=30                # 报告近 30 天到期
php artisan acme:inventory-fefo:expiring --include-expired         # 含已过期
```

## 与 commerce 的关系

不替换 commerce 的 `StockService`；只接管 `StockAllocator` 这一对接口。`StockService::receive/adjust`（仓库管理后台用）继续可用，但其 reserve/ship/release 走 commerce 自带逻辑，FEFO 这边走自己——同时同一个 listener 注入的是接口，宿主选哪个实现就走哪条路。

## 0.2
- 跨 warehouse 自动调拨（同区库存先借，再扣本仓）
- 临近到期自动折扣（监听 `acme:inventory-fefo:expiring` 输出 → 自动生成 timed_discount campaign）
- 退货回库时的 batch 选择（默认按 release 时记录的 allocation 反向归还）
- 多种 perishability 等级（"DLC" / "DDM" / "best-before"）
