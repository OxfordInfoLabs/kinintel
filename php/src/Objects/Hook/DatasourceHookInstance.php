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

    protected ?string $datasourceInstanceKey;

    protected ?string $dataProcessorInstanceKey;

    protected ?int $scheduledTaskId;

    protected ?string $hookMode;


    /**
     * @param string $datasourceInstanceKey
     * @param string $dataProcessorInstanceKey
     * @param int $scheduledTaskId
     * @param string $hookMode
     */
    public function __construct(?string $datasourceInstanceKey, ?string $dataProcessorInstanceKey, ?string $scheduledTaskId, ?string $hookMode) {
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->dataProcessorInstanceKey = $dataProcessorInstanceKey;
        $this->scheduledTaskId = $scheduledTaskId;
        $this->hookMode = $hookMode;
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