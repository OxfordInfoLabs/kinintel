<?php

namespace Kinintel\Objects\Hook;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ki_datasource_hook_instance
 * @generate
 */
class DatasourceHookInstance extends ActiveRecord {


    protected ?int $id = null;

    protected ?string $datasourceInstanceKey;

    protected ?string $hookKey;

    /**
     * @json
     */
    protected mixed $hookConfig;

    protected ?string $dataProcessorInstanceKey;

    protected ?int $scheduledTaskId;

    protected ?string $hookMode;

    const HOOK_MODE_ADD = "add";
    const HOOK_MODE_UPDATE = "update";
    const HOOK_MODE_DELETE = "delete";
    const HOOK_MODE_REPLACE = "replace";
    const HOOK_MODE_ALL = "all";


    /**
     * @param string $datasourceInstanceKey
     * @param string|null $hookKey
     * @param mixed|null $hookConfig
     * @param string $dataProcessorInstanceKey
     * @param int $scheduledTaskId
     * @param string $hookMode
     */
    public function __construct(?string $datasourceInstanceKey = null, ?string $hookKey = null, mixed $hookConfig = null, ?string $dataProcessorInstanceKey = null, ?string $scheduledTaskId = null, ?string $hookMode = null) {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->dataProcessorInstanceKey = $dataProcessorInstanceKey;
        $this->scheduledTaskId = $scheduledTaskId;
        $this->hookMode = $hookMode;
        $this->hookKey = $hookKey;
        $this->hookConfig = $hookConfig;
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

    /**
     * @return string|null
     */
    public function getHookKey(): ?string {
        return $this->hookKey;
    }

    /**
     * @param string|null $hookKey
     */
    public function setHookKey(?string $hookKey): void {
        $this->hookKey = $hookKey;
    }

    /**
     * @return mixed|null
     */
    public function getHookConfig(): mixed {
        return $this->hookConfig;
    }

    /**
     * @param mixed|null $hookConfig
     */
    public function setHookConfig(mixed $hookConfig): void {
        $this->hookConfig = $hookConfig;
    }


    public function getDataProcessorInstanceKey(): ?string {
        return $this->dataProcessorInstanceKey;
    }

    public function setDataProcessorInstanceKey(?string $dataProcessorInstanceKey): void {
        $this->dataProcessorInstanceKey = $dataProcessorInstanceKey;
    }

    /**
     * @return int|null
     */
    public function getScheduledTaskId(): ?int {
        return $this->scheduledTaskId;
    }

    /**
     * @param int|null $scheduledTaskId
     */
    public function setScheduledTaskId(?int $scheduledTaskId): void {
        $this->scheduledTaskId = $scheduledTaskId;
    }

    public function isHookMode(): string {
        return $this->hookMode;
    }

    public function setHookMode(string $hookMode): void {
        $this->hookMode = $hookMode;
    }


}