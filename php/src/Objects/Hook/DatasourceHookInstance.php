<?php

namespace Kinintel\Objects\Hook;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ki_datasource_hook_instance
 * @generate
 */
class DatasourceHookInstance extends ActiveRecord {

    const HOOK_MODE_ADD = "add";

    const HOOK_MODE_UPDATE = "update";

    const HOOK_MODE_DELETE = "delete";

    const HOOK_MODE_REPLACE = "replace";

    protected ?int $id = null;

    protected string $dataProcessorInstanceKey;

    protected string $datasourceInstanceKey;

    protected string $hookMode;

    /**
     * @var mixed
     * @sqlType LONGTEXT
     * @json
     */
    protected $config;

    /**
     * @param string $datasourceInstanceKey
     * @param string $dataProcessorInstanceKey
     * @param string $hookMode
     * @param mixed $config
     */
    public function __construct(string $datasourceInstanceKey, string $dataProcessorInstanceKey, string $hookMode, mixed $config = []) {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->dataProcessorInstanceKey = $dataProcessorInstanceKey;
        $this->hookMode = $hookMode;
        $this->config = $config;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getDatasourceInstanceKey(): string {
        return $this->datasourceInstanceKey;
    }

    public function setDatasourceInstanceKey(string $datasourceInstanceKey): void {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
    }

    public function getDataProcessorInstanceKey(): string {
        return $this->dataProcessorInstanceKey;
    }

    public function setDataProcessorInstanceKey(string $dataProcessorInstanceKey): void {
        $this->dataProcessorInstanceKey = $dataProcessorInstanceKey;
    }

    public function isHookMode(): string {
        return $this->hookMode;
    }

    public function setHookMode(string $hookMode): void {
        $this->hookMode = $hookMode;
    }

    public function getConfig(): array {
        return $this->config;
    }

    public function setConfig(array $config): void {
        $this->config = $config;
    }

}