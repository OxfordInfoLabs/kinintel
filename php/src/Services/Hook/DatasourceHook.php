<?php

namespace Kinintel\Services\Hook;

use Kinintel\ValueObjects\Hook\DatasourceHookConfig;

interface DatasourceHook {


    /**
     * @return string
     */
    public function getConfigClass();

    /**
     * @param DatasourceHookConfig $hookConfig
     * @param string $hookMode
     * @param mixed $updateData
     *
     * @return mixed
     */
    public function processHook($hookConfig, $hookMode, $updateData);

}