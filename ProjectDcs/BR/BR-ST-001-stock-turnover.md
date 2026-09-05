# BR-ST-001: Stock Turnover Module

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Business Requirement

Track stock turnover metrics (days of inventory, turnover rate, consumption trends) to enable JIT (Just-In-Time) inventory management. The module reads FA's native `stock_moves` table and computes metrics that inform reordering decisions.

### Problem Statement

Without turnover tracking:
1. No visibility into slow-moving inventory
2. No way to calculate optimal reorder points
3. Reordering based on fixed levels only, not actual consumption patterns

### Solution

- Read consumption from `stock_moves` (no duplication)
- Calculate: days of inventory, turnover rate, avg daily consumption
- Broadcast `stock_turnover_data` hook for other modules
- Nightly cron recalculation

### Scope

**In Scope:**
- Consume `stock_moves` via cron (first responder pattern)
- Calculate days of inventory (QOH / ADC)
- Track consumption trends (increasing/stable/decreasing)
- Broadcast data to other modules via hook
- Listen to `stock_reservation_insufficient` for demand updates

**Out of Scope:**
- Duplicating `stock_moves` data
- Real-time calculations (cron-based only)
- Direct UI (handled by pages)

### Dependencies

- FA `stock_moves` table (read only)
- FA `stock_master` table (read only)
- `ksf_common_db` for DB abstraction

### Inter-Module Hooks

| Hook | Direction | Payload |
|------|-----------|---------|
| `nightly_recalc` | Receives | Cron trigger |
| `stock_turnover_data` | Broadcasts | Metrics cache |
| `stock_reservation_insufficient` | Receives | Shortfall data |