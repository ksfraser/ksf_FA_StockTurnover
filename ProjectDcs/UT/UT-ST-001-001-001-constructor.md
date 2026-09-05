# UT-ST-001-001-001: TurnoverMetricsDTO Constructor

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Unit Test

## UT-ST-001-001-001: TurnoverMetricsDTO Constructor

### Class Under Test
`Ksfraser\FrontAccounting\StockTurnover\TurnoverMetricsDTO`

### Method
`__construct()`

### Test Case
Verify constructor sets all properties correctly.

### Test Data
```php
stock_id = 'TEST-001'
loc_code = 'DEF'
calc_date = DateTimeImmutable('2026-09-03')
quantity_on_hand = 100.0
avg_daily_consumption = 10.0
```

### Expected Result
All properties accessible via getters with correct values.

### Related FR
FR-ST-001-001