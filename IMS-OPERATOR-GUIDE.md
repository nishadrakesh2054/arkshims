# Arksh IMS — Operator Guide (Beginner)

This guide explains how to use the **Arksh Inventory Management System (IMS)** dashboard. The system is organized into **three main categories**:

| Category | Nav group | What it does |
|----------|-----------|--------------|
| **Inventory** | Inventory | Receive raw materials & finished goods, adjust stock, view ledgers |
| **Production** | Production | Define formulas and run repackaging (raw → finished goods) |
| **Sales** | Sales | Dispatch finished goods to customers |

There are also **Products** (SKU catalog), **Reports** (analytics & traceability), and **Settings** (suppliers, categories, brands, units).

---

## Quick Start

### 1. Start the application

```bash
cd /home/arksh/imsfilament
php artisan serve
```

Open: **http://localhost:8000/admin**

The homepage (`/`) redirects to the admin panel.

### 2. Login credentials (demo)

| Field | Value |
|-------|-------|
| Email | `webdeveloper@arkshgroup.com` |
| Password | `arksh12345` |

### 3. Load sample data

If the database is empty or outdated, reset and seed:

```bash
php artisan migrate:fresh --seed
```

### 4. Verify everything works

```bash
php artisan ims:verify
php artisan ims:go-live-check
php artisan test --compact
```

All commands should pass. The test suite has **37 automated tests** covering every workflow below.

---

## Dashboard Overview

After login you land on the **Operations Dashboard** (`/admin`).

| Widget | Shows |
|--------|-------|
| Inventory Stats | Raw material totals, receipts today, low-stock alerts |
| Operations Stats | Production batches, dispatches, FG receipts |
| Low Stock Table | Items below minimum stock |
| Recent Activity | Latest system events |

Use the left sidebar to navigate by category.

---

## Product Categories (3 types)

The sample data includes three product lines:

| Code | Products | Stock path |
|------|----------|------------|
| **COFFEE** | Gold & Original brands — pouches, packs, boxes | Raw receipt → Repackaging → Dispatch |
| **CREAMER** | Original brand — sachets, jars | Raw receipt → Repackaging → Dispatch |
| **CHOCOLATE** | Luxury Chocolate — vanilla, strawberry, coconut packs | FG Carton Receipt → Dispatch (no repackaging) |

Coffee and Creamer are **made in-house** from bulk raw materials. Chocolate is **received ready-packed** in cartons from suppliers.

---

## Category 1 — Inventory

**Sidebar:** Inventory group

### Sections

| Menu item | URL | Purpose |
|-----------|-----|---------|
| Raw Materials | `/admin/raw-materials` | Catalog of bulk ingredients (coffee, creamer) |
| Receipts | `/admin/material-receipts` | Record raw material arriving from suppliers |
| FG Receipts (Cartons) | `/admin/finished-goods-receipts` | Record pre-packed chocolate cartons |
| Raw Stock Ledger | `/admin/stock-ledger` | Every raw IN / OUT / ADJUSTMENT |
| FG Stock Ledger | `/admin/finished-goods-ledger` | Every finished-goods IN / OUT / ADJUSTMENT |
| Stock Adjustments | `/admin/stock-adjustments` | Manual corrections after physical counts |

### How stock is calculated

```
Current Stock = IN − OUT + ADJUSTMENT
```

Stock is **never typed directly** — it is always computed from ledger transactions.

### Sample data — Inventory

| Document | Details |
|----------|---------|
| Material Receipt `COF-BATCH-001` | 500 kg Coffee Bulk from China Coffee Imports |
| Material Receipt `COF-BATCH-002` | 300 kg Coffee Bulk from Italy Dairy Co |
| Material Receipt `CRM-BATCH-001` | 250 kg Creamer Bulk from Italy Dairy Co |
| FG Receipt `FG-VAN-2026-001` | 20 cartons × 20 packs Vanilla Chocolate (`VAN-C001` … `VAN-C020`) |
| FG Receipt `FG-COC-2026-001` | 32 cartons × 20 packs Coconut Chocolate (`COC-C001` … `COC-C032`) |
| Stock Adjustment (raw) | −2 kg Coffee Bulk — sample count variance |
| Stock Adjustment (FG) | +5 packs Coffee Gold 50gm — sample recount |

**Expected stock after seeding:**

| Item | Stock |
|------|-------|
| Coffee Bulk | 766 kg |
| Creamer Bulk | 235 kg |
| Coffee Gold 50gm Pouch | 155 packs (200 produced − 50 dispatched + 5 adjusted) |

### Operator steps — Raw Material Receipt

1. Go to **Inventory → Receipts → Create**
2. Select **Raw Material** (e.g. Coffee Bulk)
3. Select **Supplier**, enter **Batch No**, **Quantity**, **Unit**, **Date**
4. Click **Create**
5. Confirm in **Raw Stock Ledger** — a new **IN** row appears
6. Check **Raw Materials** list — current stock increased

**To reverse:** Delete the receipt — stock is restored automatically.

### Operator steps — FG Carton Receipt (Chocolate)

1. Go to **Inventory → FG Receipts (Cartons) → Create**
2. Select **SKU** (e.g. Luxury Chocolate Vanilla 400gm)
3. Enter **Cartons Count**, **Packs per Carton**, optional **Carton Prefix**
4. Click **Create**
5. System creates one batch per carton (`PREFIX-C001`, `PREFIX-C002`, …) and FG **IN** for each
6. Confirm in **FG Stock Ledger**

**Note:** FG carton receipts cannot be edited — only deleted (which reverses all stock).

### Operator steps — Stock Adjustment

1. Go to **Inventory → Stock Adjustments → Create**
2. Choose **Stock Type**: Raw Material or Finished Goods
3. Select the item, **Direction** (increase/decrease), **Quantity**, **Reason**
4. Click **Create**
5. Confirm **ADJUSTMENT** row in the appropriate ledger

**Note:** Decreases are blocked if stock would go negative. Adjustments cannot be edited — delete to reverse.

---

## Category 2 — Production

**Sidebar:** Production group

### Sections

| Menu item | URL | Purpose |
|-----------|-----|---------|
| Formulas | `/admin/repackaging-formulas` | How much raw material each SKU consumes |
| Repackaging Batches | `/admin/repackaging-batches` | Production runs that convert raw → finished goods |

### Sample data — Production

| Formula SKU | Raw consumption per unit |
|-------------|---------------------------|
| SKU-CG-50 (50gm Pouch) | 0.05 kg Coffee Bulk |
| SKU-CG-100 (100gm Pouch) | 0.10 kg Coffee Bulk |
| SKU-CO-1KG (1kg Pack) | 1.00 kg Coffee Bulk |
| SKU-CR-10 (10gm Sachet) | 0.01 kg Creamer Bulk |
| SKU-CR-200 (200gm Jar) | 0.20 kg Creamer Bulk |
| SKU-CG-BOX12 (12×50gm Box) | 0.60 kg Coffee Bulk |

| Batch | SKU | Qty produced |
|-------|-----|--------------|
| REPACK-POUCH50-001 | Coffee Gold 50gm Pouch | 200 |
| REPACK-POUCH100-001 | Coffee Gold 100gm Pouch | 100 |
| REPACK-SACHET-001 | Creamer 10gm Sachet | 500 |
| REPACK-JAR-001 | Creamer 200gm Jar | 50 |
| REPACK-BOX-001 | Coffee Gold 12×50gm Box | 20 |

### Operator steps — Repackaging Batch

**Prerequisite:** Formula must exist for the SKU, and enough raw stock must be available.

1. Go to **Production → Repackaging Batches → Create**
2. Select **SKU**, enter **Batch No**, **Quantity** (units to produce), **Date**
3. Click **Create**
4. System automatically:
   - Deducts raw material (OUT in Raw Stock Ledger)
   - Creates one Finished Goods Batch
   - Adds finished goods (IN in FG Stock Ledger)
5. Confirm both ledgers

**If stock is insufficient:** The system shows a validation error and blocks creation.

**To reverse:** Delete the batch — raw and FG stock are both restored.

**Note:** SKU and quantity cannot be changed after creation.

---

## Category 3 — Sales

**Sidebar:** Sales group

### Sections

| Menu item | URL | Purpose |
|-----------|-----|---------|
| Dispatches | `/admin/dispatches` | Ship finished goods to customers |

### Sample data — Sales

| Dispatch | Customer | Items |
|----------|----------|-------|
| DISP-001 | Metro Retail Store | 50 × Coffee Gold 50gm Pouch |
| DISP-CH-001 | Sweet Treats Distributor | 5 loose Coconut packs + 1 whole carton (20 packs) |

### Operator steps — Dispatch

1. Go to **Sales → Dispatches → Create**
2. Enter **Dispatch No**, **Customer Name**, **Date**
3. Add line items in the repeater:
   - **SKU** — required
   - **Carton Batch** — optional; link to a specific carton for chocolate traceability
   - **Quantity** — number of packs (or whole carton quantity = packs per carton)
4. Click **Create**
5. Confirm FG **OUT** in **FG Stock Ledger**

**If stock is insufficient:** Creation is blocked with a validation error.

**To reverse:** Delete the entire dispatch — all FG stock is restored.

**Note:** Line items cannot be edited after creation. To change items, delete and recreate the dispatch.

---

## Unified End-to-End Workflow

This is the complete flow a beginner should practice once. It connects all three categories.

### Path A — Coffee / Creamer (make then sell)

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────────────┐     ┌───────────┐
│  SETTINGS   │     │    INVENTORY     │     │     PRODUCTION      │     │   SALES   │
│  (one-time) │────▶│  Material Receipt│────▶│  Repackaging Batch  │────▶│ Dispatch  │
│  Suppliers  │     │  (raw IN)        │     │  (raw OUT + FG IN)  │     │ (FG OUT)  │
│  SKUs       │     │                  │     │                     │     │           │
│  Formulas   │     │  Stock Adjustment│     │                     │     │           │
└─────────────┘     │  (if count wrong)│     └─────────────────────┘     └───────────┘
                    └──────────────────┘
```

**Step-by-step practice run:**

| Step | Category | Action | Verify |
|------|----------|--------|--------|
| 1 | Settings | Ensure supplier, raw material, SKU, and formula exist | Lists show items |
| 2 | Inventory | Create Material Receipt — 100 kg Coffee Bulk | Raw Stock Ledger → IN +100 |
| 3 | Production | Create Repackaging Batch — 50 × Coffee Gold 50gm Pouch | Raw OUT −2.5 kg, FG IN +50 packs |
| 4 | Sales | Create Dispatch — 20 packs to a test customer | FG OUT −20 packs |
| 5 | Inventory | (Optional) Stock Adjustment if physical count differs | ADJUSTMENT in ledger |
| 6 | Reports | Open Stock Analytics & Batch Traceability | Numbers match ledgers |
| 7 | Reverse | Delete the adjustment (if any), dispatch, batch, receipt | Stock returns to start |

### Path B — Chocolate (receive cartons then sell)

```
Supplier → FG Carton Receipt (FG IN per carton) → Dispatch (FG OUT)
```

| Step | Action | Verify |
|------|--------|--------|
| 1 | Create FG Carton Receipt — 5 cartons × 20 packs | 5 batches created, FG IN × 5 |
| 2 | Create Dispatch — 3 loose packs + 1 whole carton | FG OUT for 23 packs total |
| 3 | Check Batch Traceability | Carton shows dispatched quantity |
| 4 | Delete dispatch, then receipt | Stock fully restored |

---

## Reports & Monitoring

| Page | URL | Use for |
|------|-----|---------|
| Stock Analytics | `/admin/stock-dashboard` | Overview charts and stock tables |
| Batch Traceability | `/admin/batch-traceability` | Track each FG lot from production/receipt to dispatch |
| Reports Center | `/admin/reports` | Production, dispatch, raw movement, FG movement tabs |
| Audit Log | `/admin/audit-logs` | Who changed what and when |

### Scheduled monitoring

A daily job runs at 08:00 to check low stock:

```bash
php artisan ims:check-low-stock
```

On production, ensure the Laravel scheduler cron is active:

```
* * * * * cd /path/to/imsfilament && php artisan schedule:run >> /dev/null 2>&1
```

---

## Settings (Master Data)

Configure once before daily operations:

| Menu | Purpose | Example |
|------|---------|---------|
| Suppliers | Who delivers materials | China Coffee Imports |
| Categories | Product lines | COFFEE, CREAMER, CHOCOLATE |
| Brands | Brand under category | Gold, Original, Luxury Chocolate |
| Units | Measurement units | kg, gm, pcs |
| SKUs (Products group) | Finished product catalog | SKU-CG-50 |
| Formulas (Production group) | Raw consumption per SKU | 0.05 kg coffee per pouch |

---

## Automated Test Coverage

Run anytime:

```bash
php artisan test --compact
```

| Test area | What is verified |
|-----------|------------------|
| Seed data | All modules populated (9 SKUs, 3 receipts, 5 batches, 2 FG receipts, 2 adjustments, 2 dispatches) |
| Inventory | Raw stock math, material receipt create/delete, raw & FG adjustments |
| Production | Repackaging consumption per packaging type, insufficient stock blocking, batch delete restore |
| Sales | Dispatch FG OUT, insufficient stock blocking, dispatch delete restore |
| Chocolate | Carton receipt (batch per carton), carton dispatch (loose + whole carton) |
| Workflows | Per-category workflow tests + full end-to-end across all 3 categories |
| UI smoke | 11 admin pages return HTTP 200 for logged-in admin |

---

## Common Mistakes & Tips

| Mistake | What happens | Fix |
|---------|--------------|-----|
| Repackaging without formula | Validation error | Create formula first in Production → Formulas |
| Dispatch more than available | Validation error | Check FG Stock Ledger or SKU current stock |
| Trying to edit FG receipt | Not allowed | Delete and recreate |
| Trying to edit dispatch items | Fields disabled | Delete dispatch and create new one |
| Stale sample data | Wrong counts in dashboard | Run `php artisan migrate:fresh --seed` |
| Chocolate via repackaging | No formula exists (by design) | Use FG Carton Receipt instead |

---

## Command Reference

| Command | Purpose |
|---------|---------|
| `php artisan migrate:fresh --seed` | Reset DB with full sample data |
| `php artisan ims:verify` | Check counts, stock math, list admin URLs |
| `php artisan ims:go-live-check` | Pre-production checklist |
| `php artisan ims:check-low-stock` | Print/log low-stock items |
| `php artisan ims:backfill-finished-goods` | Fix legacy repackaging batches missing FG records |
| `php artisan test --compact` | Run all 37 automated tests |

---

## File Map (for developers)

| File | Role |
|------|------|
| `database/seeders/ImsSampleDataSeeder.php` | All sample/demo data |
| `tests/Feature/InventorySystemTest.php` | Workflow & stock integrity tests |
| `app/Console/Commands/VerifyImsSetup.php` | `ims:verify` command |
| `app/Console/Commands/GoLiveCheck.php` | `ims:go-live-check` command |
| `app/Support/InventoryGuard.php` | Stock validation & row locking |

---

*Last verified: all 37 tests passing, stock integrity confirmed via `ims:verify`.*
