<?php
declare(strict_types=1);

define('SS_ksf_FA_StockTurnover', 148 << 8);

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

$composerDepsPath = dirname(__DIR__) . '/ksf_FA_Common/src/Utils/ComposerDependencies.php';
if (file_exists($composerDepsPath)) {
    require_once $composerDepsPath;
    \ksfraser\FrontAccounting\Common\Utils\ComposerDependencies::ensure(__DIR__);
}

class hooks_ksf_FA_StockTurnover extends hooks
{
    var $module_name = 'ksf_FA_StockTurnover';
    var $version = '2.4.19-1.0.0';

    function activate_extension($company, $check_only=true)
    {
        if (!file_exists(dirname(__FILE__) . '/sql/install.sql')) {
            return true;
        }

        $updates = array(
            'install.sql' => array(
                'ksf_stock_turnover_daily',
                'ksf_stock_turnover_metrics',
            ),
        );

        return $this->update_databases($company, $updates, $check_only);
    }

    function deactivate_extension($company, $check_only=true)
    {
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

    function db_postwrite($cart, $trans_type)
    {
        if (!in_array($trans_type, [ST_SALESINVOICE, ST_CUSTDELIVERY, ST_CREDIT, ST_ADJUSTMENT])) {
            return;
        }

        $this->recordStockMovement($cart, $trans_type);
    }

    function nightly_recalc(array &$data)
    {
        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->performNightlyRecalculation();

        $data['stock_turnover_data'] = $handler->getCachedMetrics();
        $data['stock_turnover_processed'] = true;

        hook_invoke_all('stock_turnover_data', $data);
    }

    function stock_turnover_data(array &$data)
    {
        if (isset($data['consumers']) && is_array($data['consumers'])) {
            $data['consumers'][] = 'ksf_FA_StockTurnover';
        } else {
            $data['consumers'] = ['ksf_FA_StockTurnover'];
        }
    }

    function stock_reservation_insufficient(array &$data)
    {
        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->updateDemandFromReservationShortfall($data);
    }

    private function recordStockMovement($cart, $trans_type): void
    {
        if (!class_exists('\Ksfraser\FrontAccounting\StockTurnover\StockMovementRecorder::class)) {
            return;
        }

        $handler = $this->getHandler();
        if ($handler === null) {
            return;
        }
        $handler->recordFromCart($cart, $trans_type);
    }

    private function getHandler()
    {
        static $handler = null;

        if ($handler !== null) {
            return $handler;
        }

        if (!class_exists('\Ksfraser\FrontAccounting\StockTurnover\StockTurnoverHandler')) {
            return null;
        }

        $db = new \ksfraser\CommonDb\Adapter\FaDbAdapter(TB_PREF);
        $repository = new \Ksfraser\FrontAccounting\StockTurnover\TurnoverRepository($db);
        $handler = new \Ksfraser\FrontAccounting\StockTurnover\StockTurnoverHandler($repository);

        return $handler;
    }

    function install_access()
    {
        $security_sections[SS_ksf_FA_StockTurnover] = _("Stock Turnover");
        $security_areas['SA_ksf_FA_STOCKTURNOVER'] = array(
            SS_ksf_FA_StockTurnover | 1,
            _("Manage Stock Turnover")
        );
        $security_areas['SA_ksf_FA_STOCKTURNOVER_VIEW'] = array(
            SS_ksf_FA_StockTurnover | 2,
            _("View Stock Turnover")
        );
        return array($security_areas, $security_sections);
    }
}