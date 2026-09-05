# UT-ST-001-001-002: TurnoverMetricsDTO Calculate Metrics

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Unit Test

## UT-ST-001-001-002: TurnoverMetricsDTO Calculate Metrics

### Class Under Test
`Ksfraser\FrontAccounting\StockTurnover\TurnoverMetricsDTO`

### Method
`calculateMetrics()`

### Test Case
Verify DOI calculation and turnover rate.

### Test Data
```php
quantity_on_hand = 100.0
avg_daily_consumption = 10.0
```

### Expected Result
- DOI = 10.0 (100 / 10)
- Turnover rate > 0

### Related FR
FR-ST-001-001