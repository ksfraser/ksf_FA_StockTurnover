# FR-ST-001-002: Detect Consumption Trends

## Version
1.0.0

## Author
KSF Development Team

## Created
2026-09-04

## Status
Approved

## Functional Requirement

## FR-ST-001-002: Detect Consumption Trends

### Description
Detect whether consumption is increasing, stable, or decreasing over time.

### Input
- Historical daily consumption data (last 30 days)

### Processing
Compare recent period (last 7 days) average to prior period (days 8-30) average:
```
trend_ratio = recent_avg / prior_avg

if trend_ratio > 1 + threshold: 'increasing'
if trend_ratio < 1 - threshold: 'decreasing'
else: 'stable'
```

### Output
- Trend classification: increasing, stable, or decreasing

### Business Rules
- Configurable threshold (default 0.15 = 15%)
- Requires minimum 7 days of data for trend detection
- Insufficient data = 'unknown' trend

### Acceptance Criteria
- [ ] Correctly identifies increasing consumption
- [ ] Correctly identifies decreasing consumption
- [ ] Correctly identifies stable consumption
- [ ] Handles insufficient data gracefully