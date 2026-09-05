<?php
declare(strict_types=1);

namespace Ksfraser\FrontAccounting\StockTurnover;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Handles stock turnover calculations and nightly recalculation.
 *
 * @since 1.0.0
 */
class StockTurnoverHandler
{
    private const CRON_TYPE = 'nightly_recalc';

    /** @var TurnoverRepository */
    private $repository;

    /** @var LoggerInterface */
    private $logger;

    /** @var array|null */
    private $cachedMetrics;

    public function __construct(
        TurnoverRepository $repository,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger ?? new NullLogger();
    }

    public function performNightlyRecalculation(): int
    {
        $this->logger->info('Starting nightly stock turnover recalculation');

        $checkpoint = $this->repository->getCronCheckpoint(self::CRON_TYPE);
        $lastMoveId = $checkpoint['last_stock_move_id'] ?? 0;
        $recordsProcessed = 0;

        $moves = $this->repository->readStockMovesSince($lastMoveId, 5000);

        if (empty($moves)) {
            $this->logger->info('No new stock moves to process');
            $this->repository->saveCronCheckpoint(self::CRON_TYPE, [
                'last_stock_move_id' => $lastMoveId,
                'records_processed' => 0,
                'status' => 'completed',
            ]);
            return 0;
        }

        $metricsByItem = [];
        $maxMoveId = $lastMoveId;

        foreach ($moves as $move) {
            $stockId = $move['stock_id'];
            $qty = (float) ($move['quantity'] ?? 0);

            if (!isset($metricsByItem[$stockId])) {
                $metricsByItem[$stockId] = [
                    'stock_id' => $stockId,
                    'loc_code' => $move['loc_code'] ?? 'DEF',
                    'total_consumed' => 0,
                    'movement_dates' => [],
                ];
            }

            if ($qty < 0) {
                $metricsByItem[$stockId]['total_consumed'] += abs($qty);
            }

            $transDate = $move['trans_date'] ?? null;
            if ($transDate) {
                $metricsByItem[$stockId]['movement_dates'][] = $transDate;
            }

            $maxMoveId = max($maxMoveId, (int) ($move['id'] ?? 0));
        }

        $calcDate = new \DateTimeImmutable('yesterday');

        foreach ($metricsByItem as $itemData) {
            $metrics = $this->calculateMetrics(
                $itemData['stock_id'],
                $itemData['loc_code'],
                $itemData['total_consumed'],
                $calcDate
            );

            $this->repository->saveMetrics($metrics);
            $recordsProcessed++;
        }

        $this->repository->saveCronCheckpoint(self::CRON_TYPE, [
            'last_stock_move_id' => $maxMoveId,
            'records_processed' => $recordsProcessed,
            'status' => 'completed',
        ]);

        $this->cachedMetrics = $this->loadAllMetrics();

        $this->logger->info("Completed nightly recalculation", [
            'records_processed' => $recordsProcessed,
            'last_move_id' => $maxMoveId,
        ]);

        return $recordsProcessed;
    }

    private function calculateMetrics(
        string $stockId,
        string $locCode,
        float $totalConsumed,
        \DateTimeImmutable $calcDate
    ): TurnoverMetricsDTO {
        $metrics = new TurnoverMetricsDTO($stockId, $locCode, $calcDate, 0, 0);

        $avgDaily = $totalConsumed / 30;
        $metrics->avgDailyConsumption = $avgDaily;

        $qoh = $this->getQuantityOnHand($stockId, $locCode);
        $metrics->quantityOnHand = $qoh;

        $metrics->calculateMetrics();

        return $metrics;
    }

    private function getQuantityOnHand(string $stockId, string $locCode): float
    {
        $sql = "SELECT quantity FROM stock_master WHERE stock_id = ?";
        $result = $this->db->fetchAssoc($sql, [$stockId]);
        return $result ? (float) $result['quantity'] : 0;
    }

    public function getCachedMetrics(): array
    {
        if ($this->cachedMetrics === null) {
            $this->cachedMetrics = $this->loadAllMetrics();
        }
        return $this->cachedMetrics;
    }

    private function loadAllMetrics(): array
    {
        $sql = "SELECT * FROM 0_ksf_stock_turnover_metrics
                WHERE calc_date = CURDATE() - INTERVAL 1 DAY
                ORDER BY days_of_inventory ASC";

        $rows = $this->db->fetchAll($sql, []);
        return array_map(fn($row) => TurnoverMetricsDTO::fromArray($row), $rows);
    }

    public function updateDemandFromReservationShortfall(array $data): void
    {
        $items = $data['items'] ?? [];
        $orderNo = $data['order_no'] ?? 0;

        $this->logger->info('Updating demand from reservation shortfall', [
            'order_no' => $orderNo,
            'items' => count($items),
        ]);
    }
}