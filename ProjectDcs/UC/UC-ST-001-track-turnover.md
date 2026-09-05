# UC-ST-001: Track Stock Turnover

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Use Case

## UC-ST-001: Track Stock Turnover

### Primary Actor
Inventory Manager / Purchasing Agent

### Goal
Maintain optimal stock levels based on actual consumption patterns

### Trigger
Nightly cron job

### Main Flow

1. Cron invokes `nightly_recalc`
2. StockTurnover is first responder (via hook_invoke_first)
3. Module reads `stock_moves` since last checkpoint
4. Calculates metrics per item
5. Stores in `ksf_stock_turnover_metrics`
6. Broadcasts `stock_turnover_data` for other modules

### Alternative Flows

**A1: No new movements**
- Checkpoint updated, no recalculation needed

**A2: Error during calculation**
- Log error, mark checkpoint as failed
- Retry on next run

### Related FR
FR-ST-001-001