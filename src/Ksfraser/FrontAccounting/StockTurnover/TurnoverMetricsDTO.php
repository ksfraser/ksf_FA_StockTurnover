<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\StockTurnover;

/**
 * Data transfer object for stock turnover metrics.
 *
 * @since 1.0.0
 */
class TurnoverMetricsDTO
{
    /** @var int|null */
    private $id;

    /** @var string */
    private $stockId;

    /** @var string */
    private $locCode;

    /** @var \DateTimeImmutable */
    private $calcDate;

    /** @var float */
    private $quantityOnHand;

    /** @var float */
    private $avgDailyConsumption;

    /** @var float */
    private $daysOfInventory;

    /** @var float */
    private $turnoverRate;

    /** @var float */
    private $sellThroughRate;

    /** @var float */
    private $reorderPointCalculated;

    /** @var float */
    private $lastConsumptionRate;

    /** @var string */
    private $consumptionTrend;

    /** @var int */
    private $daysSinceLastMovement;

    /** @var \DateTimeImmutable|null */
    private $lastMovementDate;

    public function __construct(
        string $stockId,
        string $locCode,
        \DateTimeImmutable $calcDate,
        float $quantityOnHand = 0,
        float $avgDailyConsumption = 0
    ) {
        $this->stockId = $stockId;
        $this->locCode = $locCode;
        $this->calcDate = $calcDate;
        $this->quantityOnHand = $quantityOnHand;
        $this->avgDailyConsumption = $avgDailyConsumption;
        $this->consumptionTrend = 'stable';
        $this->daysSinceLastMovement = 0;
    }

    public static function fromArray(array $data): self
    {
        $dto = new self(
            $data['stock_id'],
            $data['loc_code'],
            new \DateTimeImmutable($data['calc_date']),
            (float) ($data['quantity_on_hand'] ?? 0),
            (float) ($data['avg_daily_consumption'] ?? 0)
        );
        $dto->id = isset($data['id']) ? (int) $data['id'] : null;
        $dto->daysOfInventory = (float) ($data['days_of_inventory'] ?? 0);
        $dto->turnoverRate = (float) ($data['turnover_rate'] ?? 0);
        $dto->sellThroughRate = (float) ($data['sell_through_rate'] ?? 0);
        $dto->reorderPointCalculated = (float) ($data['reorder_point_calculated'] ?? 0);
        $dto->lastConsumptionRate = (float) ($data['last_consumption_rate'] ?? 0);
        $dto->consumptionTrend = $data['consumption_trend'] ?? 'stable';
        $dto->daysSinceLastMovement = (int) ($data['days_since_last_movement'] ?? 0);
        $dto->lastMovementDate = isset($data['last_movement_date'])
            ? new \DateTimeImmutable($data['last_movement_date']) : null;
        return $dto;
    }

    public function calculateMetrics(): void
    {
        if ($this->avgDailyConsumption > 0) {
            $this->daysOfInventory = $this->quantityOnHand / $this->avgDailyConsumption;
            $this->turnoverRate = ($this->avgDailyConsumption * 365) / max($this->quantityOnHand, 0.0001);
        } else {
            $this->daysOfInventory = $this->quantityOnHand > 0 ? PHP_INT_MAX : 0;
            $this->turnoverRate = 0;
        }

        $this->reorderPointCalculated = $this->avgDailyConsumption * $this->getSafetyStockDays();
    }

    public function getSafetyStockDays(): int
    {
        return 7;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStockId(): string
    {
        return $this->stockId;
    }

    public function getLocCode(): string
    {
        return $this->locCode;
    }

    public function getCalcDate(): \DateTimeImmutable
    {
        return $this->calcDate;
    }

    public function getQuantityOnHand(): float
    {
        return $this->quantityOnHand;
    }

    public function getAvgDailyConsumption(): float
    {
        return $this->avgDailyConsumption;
    }

    public function getDaysOfInventory(): float
    {
        return $this->daysOfInventory;
    }

    public function getTurnoverRate(): float
    {
        return $this->turnoverRate;
    }

    public function getConsumptionTrend(): string
    {
        return $this->consumptionTrend;
    }

    public function needsReorder(): bool
    {
        return $this->daysOfInventory < $this->getReorderThresholdDays();
    }

    public function getReorderThresholdDays(): int
    {
        return 14;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'stock_id' => $this->stockId,
            'loc_code' => $this->locCode,
            'calc_date' => $this->calcDate->format('Y-m-d'),
            'quantity_on_hand' => $this->quantityOnHand,
            'avg_daily_consumption' => $this->avgDailyConsumption,
            'days_of_inventory' => $this->daysOfInventory,
            'turnover_rate' => $this->turnoverRate,
            'sell_through_rate' => $this->sellThroughRate,
            'reorder_point_calculated' => $this->reorderPointCalculated,
            'last_consumption_rate' => $this->lastConsumptionRate,
            'consumption_trend' => $this->consumptionTrend,
            'days_since_last_movement' => $this->daysSinceLastMovement,
            'last_movement_date' => $this->lastMovementDate !== null ? $this->lastMovementDate->format('Y-m-d') : null,
        ];
    }
}