<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\StockTurnover;

use Ksfraser\CommonDb\Contract\DbConnectionInterface;

/**
 * Repository for stock turnover metrics.
 *
 * @since 1.0.0
 */
class TurnoverRepository
{
    /** @var DbConnectionInterface */
    private $db;

    /** @var string */
    private $metricsTable;

    /** @var string */
    private $cronTable;

    public function __construct(
        DbConnectionInterface $db,
        string $metricsTable = '0_ksf_stock_turnover_metrics',
        string $cronTable = '0_ksf_stock_turnover_cron'
    ) {
        $this->db = $db;
        $this->metricsTable = $metricsTable;
        $this->cronTable = $cronTable;
    }

    public function saveMetrics(TurnoverMetricsDTO $metrics): void
    {
        $sql = "INSERT INTO {$this->metricsTable}
                (stock_id, loc_code, calc_date, quantity_on_hand, avg_daily_consumption,
                 days_of_inventory, turnover_rate, sell_through_rate, reorder_point_calculated,
                 last_consumption_rate, consumption_trend, days_since_last_movement, last_movement_date)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                quantity_on_hand = VALUES(quantity_on_hand),
                avg_daily_consumption = VALUES(avg_daily_consumption),
                days_of_inventory = VALUES(days_of_inventory),
                turnover_rate = VALUES(turnover_rate),
                consumption_trend = VALUES(consumption_trend),
                days_since_last_movement = VALUES(days_since_last_movement),
                last_movement_date = VALUES(last_movement_date),
                updated_at = CURRENT_TIMESTAMP";

        $this->db->executeUpdate($sql, [
            $metrics->getStockId(),
            $metrics->getLocCode(),
            $metrics->getCalcDate()->format('Y-m-d'),
            $metrics->getQuantityOnHand(),
            $metrics->getAvgDailyConsumption(),
            $metrics->getDaysOfInventory(),
            $metrics->getTurnoverRate(),
            $metrics->getSellThroughRate(),
            $metrics->getReorderPointCalculated(),
            $metrics->getLastConsumptionRate(),
            $metrics->getConsumptionTrend(),
            $metrics->daysSinceLastMovement,
            $metrics->getLastMovementDate() !== null ? $metrics->getLastMovementDate()->format('Y-m-d') : null,
        ]);
    }

    public function getLatestMetrics(string $stockId, string $locCode = 'DEF'): ?TurnoverMetricsDTO
    {
        $sql = "SELECT * FROM {$this->metricsTable}
                WHERE stock_id = ? AND loc_code = ?
                ORDER BY calc_date DESC LIMIT 1";

        $row = $this->db->fetchAssoc($sql, [$stockId, $locCode]);

        if ($row === false) {
            return null;
        }

        return TurnoverMetricsDTO::fromArray($row);
    }

    public function getMetricsRange(
        string $stockId,
        string $locCode,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to
    ): array {
        $sql = "SELECT * FROM {$this->metricsTable}
                WHERE stock_id = ? AND loc_code = ? AND calc_date BETWEEN ? AND ?
                ORDER BY calc_date ASC";

        $rows = $this->db->fetchAll($sql, [
            $stockId,
            $locCode,
            $from->format('Y-m-d'),
            $to->format('Y-m-d'),
        ]);

        return array_map(fn($row) => TurnoverMetricsDTO::fromArray($row), $rows);
    }

    public function getItemsNeedingReorder(int $thresholdDays = 14, int $limit = 100): array
    {
        $sql = "SELECT m.* FROM {$this->metricsTable} m
                INNER JOIN {$this->locStockTable()} ls ON m.stock_id = ls.stock_id AND m.loc_code = ls.loc_code
                WHERE m.days_of_inventory < ? AND m.quantity_on_hand > 0
                ORDER BY m.days_of_inventory ASC
                LIMIT ?";

        $rows = $this->db->fetchAll($sql, [$thresholdDays, $limit]);
        return array_map(fn($row) => TurnoverMetricsDTO::fromArray($row), $rows);
    }

    public function getCronCheckpoint(string $cronType): ?array
    {
        $sql = "SELECT * FROM {$this->cronTable} WHERE cron_type = ?";
        return $this->db->fetchAssoc($sql, [$cronType]);
    }

    public function saveCronCheckpoint(string $cronType, array $data): void
    {
        $sql = "INSERT INTO {$this->cronTable}
                (cron_type, last_run_at, last_stock_move_id, records_processed, status)
                VALUES (?, NOW(), ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                last_run_at = NOW(),
                last_stock_move_id = VALUES(last_stock_move_id),
                records_processed = VALUES(records_processed),
                status = VALUES(status)";

        $this->db->executeUpdate($sql, [
            $cronType,
            $data['last_stock_move_id'] ?? 0,
            $data['records_processed'] ?? 0,
            $data['status'] ?? 'completed',
        ]);
    }

    public function readStockMovesSince(int $lastMoveId, int $limit = 1000): array
    {
        $sql = "SELECT sm.*, sm.qty as quantity, loc.stock_id as loc_stock_stock_id
                FROM stock_moves sm
                LEFT JOIN stock_master loc ON sm.stock_id = loc.stock_id
                WHERE sm.id > ? AND sm.qty < 0
                ORDER BY sm.id ASC
                LIMIT ?";

        return $this->db->fetchAll($sql, [$lastMoveId, $limit]);
    }

    private function locStockTable(): string
    {
        return 'loc_stock';
    }
}