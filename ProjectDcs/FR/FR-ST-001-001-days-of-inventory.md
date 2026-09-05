# FR-ST-001-001: Calculate Days of Inventory

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Functional Requirement

## FR-ST-001-001: Calculate Days of Inventory

### Description
Calculate days of inventory (DOI) using quantity on hand divided by average daily consumption.

### Input
- Current stock quantity on hand (from `stock_moves`)
- Average daily consumption over lookback period (from `stock_moves`)

### Processing
```
DOI = QOH / ADC
```

Where:
- QOH = Current quantity on hand
- ADC = Average daily consumption over lookback period

### Output
- Days of inventory per stock item per location

### Business Rules
- ADC calculated using exponential moving average (EMA) with configurable alpha
- Minimum ADC = 0.001 to avoid division by zero
- Stock items with no movements use last known ADC

### Acceptance Criteria
- [ ] DOI calculated for all active stock items
- [ ] DOI reflects actual consumption patterns
- [ ] Stock items with no movements handled gracefully