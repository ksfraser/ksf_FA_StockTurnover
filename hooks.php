<?php
declare(strict_types=1);

define('SS_ksf_FA_StockTurnover', 148 << 8);

class hooks_ksf_FA_StockTurnover extends hooks
{
    var $module_name = 'ksf_FA_StockTurnover';
    var $version = '2.4.19-1.0.0';

    function install_extension($check_only=true)
    {
        if (!$check_only) {
            $this->_ensureComposerDependencies();
        }
        return true;
    }

    function activate_extension($company, $check_only=true)
    {
        if ($check_only) {
            return true;
        }

        $autoload = __DIR__ . '/vendor/autoload.php';
        if (file_exists($autoload)) {
            require_once $autoload;
        }

        $sqlFile = __DIR__ . '/sql/install.sql';
        if (file_exists($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            $sql = str_replace('0_', get_company_preference($company)['_prefix'], $sql);
            run_db_import($sql, $company);
        }

        add_security_section(SS_ksf_FA_StockTurnover, 'Stock Turnover', 'SA_INVENTORY');
        return true;
    }

    function deactivate_extension($company, $check_only=true)
    {
        if ($check_only) {
            return true;
        }

        $uninstallFile = __DIR__ . '/sql/uninstall.sql';
        if (file_exists($uninstallFile)) {
            $sql = file_get_contents($uninstallFile);
            run_db_import($sql, $company);
        }

        remove_security_section(SS_ksf_FA_StockTurnover);
        return true;
    }

    function getModuleConstants(&$data, $opts = [])
    {
        $data['constants']['SS_ksf_FA_StockTurnover'] = SS_ksf_FA_StockTurnover;
        $data['constants']['SA_ksf_FA_STOCKTURNOVER'] = SS_ksf_FA_StockTurnover | 1;
        $data['constants']['SA_ksf_FA_STOCKTURNOVER_VIEW'] = SS_ksf_FA_StockTurnover | 2;
        return $data;
    }

    function getModuleCapabilities(&$data, $opts = [])
    {
        $data['capabilities']['stock_turnover'] = [
            'view' => 'SA_ksf_FA_STOCKTURNOVER_VIEW',
            'manage' => 'SA_ksf_FA_STOCKTURNOVER',
        ];
        return $data;
    }

    public function hasCapability(&$data, $opts = null)
    {
        $capability = isset($opts['capability']) ? $opts['capability'] : (isset($data['capability']) ? $data['capability'] : null);
        if ($capability === null) {
            $data['has_capability'] = false;
            return false;
        }
        $caps = ['view', 'manage'];
        $hasCapability = in_array($capability, $caps);
        $data['has_capability'] = $hasCapability;
        return $hasCapability;
    }

    public function respondToCapabilityRequest(&$data, $opts = null)
    {
        $request = isset($opts['request']) ? $opts['request'] : (isset($data['request']) ? $data['request'] : 'capabilities');
        $data['request'] = $request;
        $data['module'] = $this->module_name;

        if (strpos($request, 'has:') === 0) {
            $capability = substr($request, 4);
            return $this->hasCapability($data, ['capability' => $capability]);
        }

        switch ($request) {
            case 'capabilities':
                $data['capabilities'] = $this->getModuleCapabilities($data, $opts);
                return $data['capabilities'];
            default:
                return null;
        }
    }

    /**
     * FA hook: db_postwrite - capture stock movements for turnover calc.
     *
     * @param object $cart
     * @param int $trans_type
     *
     * @since 1.0.0
     */
    function db_postwrite($cart, $trans_type)
    {
        if (!in_array($trans_type, [ST_SALESINVOICE, ST_CUSTDELIVERY, ST_CREDIT, ST_ADJUSTMENT])) {
            return;
        }

        $this->recordStockMovement($cart, $trans_type);
    }

    /**
     * Cron hook: nightly_recalc - first responder reads stock_moves and caches.
     *
     * Uses hook_invoke_first pattern so only ONE module queries stock_moves,
     * then broadcasts the data for others to consume.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function nightly_recalc(array &$data)
    {
        $handler = $this->getHandler();
        $handler->performNightlyRecalculation();

        $data['stock_turnover_data'] = $handler->getCachedMetrics();
        $data['stock_turnover_processed'] = true;

        hook_invoke_all('stock_turnover_data', $data);
    }

    /**
     * Listen for stock_turnover_data from other modules (cache consumers).
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function stock_turnover_data(array &$data)
    {
        if (isset($data['consumers']) && is_array($data['consumers'])) {
            $data['consumers'][] = 'ksf_FA_StockTurnover';
        } else {
            $data['consumers'] = ['ksf_FA_StockTurnover'];
        }
    }

    /**
     * Listen for insufficient_stock from StockReservations.
     *
     * @param array &$data
     *
     * @since 1.0.0
     */
    function stock_reservation_insufficient(array &$data)
    {
        $handler = $this->getHandler();
        $handler->updateDemandFromReservationShortfall($data);
    }

    private function recordStockMovement($cart, $trans_type): void
    {
        if (!class_exists(\Ksfraser\FrontAccounting\StockTurnover\StockMovementRecorder::class)) {
            return;
        }

        $handler = $this->getHandler();
        $handler->recordFromCart($cart, $trans_type);
    }

    private function getHandler(): \Ksfraser\FrontAccounting\StockTurnover\StockTurnoverHandler
    {
        static $handler = null;

        if ($handler !== null) {
            return $handler;
        }

        $db = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
        $repository = new \Ksfraser\FrontAccounting\StockTurnover\TurnoverRepository($db);
        $handler = new \Ksfraser\FrontAccounting\StockTurnover\StockTurnoverHandler($repository);

        return $handler;
    }

    private function _ensureComposerDependencies(): void
    {
        $composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
        if (file_exists($composerDepsPath)) {
            require_once $composerDepsPath;
            \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
        }
    }

    function hook_invoke_all($hook, &$data)
    {
        $autoload = __DIR__ . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            return null;
        }
        require_once $autoload;

        return parent::hook_invoke_all($hook, $data);
    }
}