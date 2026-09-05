<?php
declare(strict_types=1);

namespace Ksfraser\Tests\FrontAccounting\StockTurnover;

use Ksfraser\FrontAccounting\StockTurnover\TurnoverMetricsDTO;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TurnoverMetricsDTO.
 *
 * @BABOK Related: BR-ST-001
 * @since 1.0.0
 */
class TurnoverMetricsDTOTest extends TestCase
{
    public function testConstructorSetsProperties(): void
    {
        $dto = new TurnoverMetricsDTO(
            'TEST-001',
            'DEF',
            new \DateTimeImmutable('2026-09-03'),
            100.0,
            10.0
        );

        $this->assertEquals('TEST-001', $dto->getStockId());
        $this->assertEquals('DEF', $dto->getLocCode());
        $this->assertEquals(100.0, $dto->getQuantityOnHand());
        $this->assertEquals(10.0, $dto->getAvgDailyConsumption());
    }

    public function testCalculateMetrics(): void
    {
        $dto = new TurnoverMetricsDTO(
            'TEST-001',
            'DEF',
            new \DateTimeImmutable('2026-09-03'),
            100.0,
            10.0
        );

        $dto->calculateMetrics();

        $this->assertEquals(10.0, $dto->getDaysOfInventory());
        $this->assertGreaterThan(0, $dto->getTurnoverRate());
    }

    public function testNeedsReorderWhenBelowThreshold(): void
    {
        $dto = new TurnoverMetricsDTO(
            'TEST-001',
            'DEF',
            new \DateTimeImmutable('2026-09-03'),
            50.0,
            10.0
        );

        $dto->calculateMetrics();

        $this->assertTrue($dto->needsReorder());
    }

    public function testNeedsReorderWhenAboveThreshold(): void
    {
        $dto = new TurnoverMetricsDTO(
            'TEST-001',
            'DEF',
            new \DateTimeImmutable('2026-09-03'),
            200.0,
            5.0
        );

        $dto->calculateMetrics();

        $this->assertFalse($dto->needsReorder());
    }

    public function testFromArrayCreatesDTO(): void
    {
        $data = [
            'stock_id' => 'TEST-002',
            'loc_code' => 'MAIN',
            'calc_date' => '2026-09-03',
            'quantity_on_hand' => 150.0,
            'avg_daily_consumption' => 7.5,
            'days_of_inventory' => 20.0,
            'turnover_rate' => 18.25,
            'consumption_trend' => 'increasing',
        ];

        $dto = TurnoverMetricsDTO::fromArray($data);

        $this->assertEquals('TEST-002', $dto->getStockId());
        $this->assertEquals(150.0, $dto->getQuantityOnHand());
        $this->assertEquals('increasing', $dto->getConsumptionTrend());
    }

    public function testToArrayContainsAllFields(): void
    {
        $dto = new TurnoverMetricsDTO(
            'TEST-001',
            'DEF',
            new \DateTimeImmutable('2026-09-03'),
            100.0,
            10.0
        );

        $array = $dto->toArray();

        $this->assertArrayHasKey('stock_id', $array);
        $this->assertArrayHasKey('loc_code', $array);
        $this->assertArrayHasKey('days_of_inventory', $array);
        $this->assertArrayHasKey('turnover_rate', $array);
    }
}