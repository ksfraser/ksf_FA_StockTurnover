-- Stock Turnover Metrics table (calculated values we add on top of FA stock_moves)
CREATE TABLE IF NOT EXISTS `0_ksf_stock_turnover_metrics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `stock_id` VARCHAR(20) NOT NULL,
    `loc_code` VARCHAR(5) NOT NULL DEFAULT 'DEF',
    `calc_date` DATE NOT NULL,
    `quantity_on_hand` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `avg_daily_consumption` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `days_of_inventory` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'QOH / ADC',
    `turnover_rate` DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT 'Annual turns (ADC * 365 / QOH)',
    `sell_through_rate` DECIMAL(10,4) NOT NULL DEFAULT 0 COMMENT 'Units sold / starting inventory',
    `reorder_point_calculated` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `last_consumption_rate` DECIMAL(15,4) NOT NULL DEFAULT 0,
    `consumption_trend` ENUM('increasing', 'stable', 'decreasing') DEFAULT 'stable',
    `days_since_last_movement` INT DEFAULT 0,
    `last_movement_date` DATE NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_stock_loc_date` (`stock_id`, `loc_code`, `calc_date`),
    INDEX `idx_stock` (`stock_id`),
    INDEX `idx_calc_date` (`calc_date`),
    INDEX `idx_days_of_inventory` (`days_of_inventory`)
) ENGINE=InnoDB;

-- Cron processing checkpoint (avoids re-reading stock_moves repeatedly)
CREATE TABLE IF NOT EXISTS `0_ksf_stock_turnover_cron` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cron_type` VARCHAR(30) NOT NULL COMMENT 'nightly_recalc, etc',
    `last_run_at` DATETIME NOT NULL,
    `last_stock_move_id` INT NOT NULL DEFAULT 0 COMMENT 'FA stock_moves.id of last processed',
    `records_processed` INT NOT NULL DEFAULT 0,
    `next_scheduled_run` DATETIME NULL,
    `status` ENUM('idle', 'running', 'completed', 'failed') NOT NULL DEFAULT 'idle',
    `error_message` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `uk_cron_type` (`cron_type`)
) ENGINE=InnoDB;