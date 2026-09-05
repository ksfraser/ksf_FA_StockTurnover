<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.local.md — ksf_FA_StockTurnover
## Purpose
Track stock turnover metrics (days of inventory, turnover rate, consumption trends) to enable JIT inventory management.

## Hook Communication
```
[Nightly Cron] → nightly_recalc → StockTurnover → stock_turnover_data → [Others]
```

## Dependencies
- ksf_common_db (DbConnectionInterface)
- FA stock_moves table (read only)

## Development Workflow
All development is done in the **devel tree** (`~/Documents/ksf_FA_StockTurnover`). Do **not** edit files in the Infrastructure bind point directly.

### Workflow Steps
1. **Develop** in this repo (feature/fix branches)
2. **Test**: `composer install && ./vendor/bin/phpunit`
3. **Lint**: `php -l` on modified PHP files
4. **Commit** and **Push** to GitHub
5. **Deploy** to Infrastructure:
   ```
   rsync -av --exclude='.git' ~/Documents/ksf_FA_StockTurnover/ ~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_StockTurnover/
   ```

### Infrastructure Bind Point
| Path | Purpose |
|------|---------|
| `~/Documents/ksf_FA_StockTurnover` | Devel tree |
| `~/Documents/ksf_Infrastructure/fa_modules/ksf_FA_StockTurnover` | Deployment target |