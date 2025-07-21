<?php

namespace Kinintel\Objects\Hook;


use Kiniauth\Traits\Account\AccountProject;
use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * @table ki_datasource_hook_instance
 * @generate
 */
class DatasourceHookInstance extends ActiveRecord {

    use AccountProject;


    /**
     * @var int
     */
    protected ?int $id = null;

    /**
     * @var string
     */
    protected ?string $title = null;

    /**
     * @var string
     */
    protected ?string $datasourceInstanceKey;

    /**
     * @var string
     */
    protected ?string $hookKey;

    /**
     * @json
     */
    protected mixed $hookConfig;

    /**
     * @var string
     */
    protected ?string $dataProcessorInstanceKey;

    /**
     * @var int
     */
    protected ?int $scheduledTaskId;

    /**
     * @var string
     */
    protected ?string $hookMode;

    /**
     * @var bool
     */
    protected bool $enabled = true;

    /**
     * @var bool
     */
    protected bool $executeInsecure = false;


    /**
     * Related item id - if this hook is being added as a subordinate to another
     * object.
     *
     * @var int
     */
    protected ?int $relatedItemId = null;

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
     * @param bool $enabled
     * @param bool $executeInsecure
     * @param int $relatedItemId
     * @param int $accountId
     * @param string $projectKey
     */
    public function __construct(?string $title = null, ?string $datasourceInstanceKey = null, ?string $hookKey = null, mixed $hookConfig = null, ?string $dataProcessorInstanceKey = null, ?int $scheduledTaskId = null,
                                string  $hookMode = self::HOOK_MODE_ALL, bool $enabled = true, bool $executeInsecure = false,
                                ?int    $relatedItemId = null,
                                ?int    $accountId = null, ?string $projectKey = null, $id = null) {
        $this->title = $title;
        $this->datasourceInstanceKey = $datasourceInstanceKey;
        $this->dataProcessorInstanceKey = $dataProcessorInstanceKey;
        $this->scheduledTaskId = $scheduledTaskId;
        $this->hookMode = $hookMode;
        $this->hookKey = $hookKey;
        $this->hookConfig = $hookConfig;
        $this->enabled = $enabled;
        $this->executeInsecure = $executeInsecure;
        $this->relatedItemId = $relatedItemId;
        $this->accountId = $accountId;
        $this->projectKey = $projectKey;
        $this->id = $id;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function setId(?int $id): void {
        $this->id = $id;
    }

    public function getTitle(): ?string {
        return $this->title;
    }

    public function setTitle(?string $title): void {
        $this->title = $title;
    }

    public function getDatasourceInstanceKey(): ?string {
        return $this->datasourceInstanceKey;
    }

    public function setDatasourceInstanceKey(?string $datasourceInstanceKey): void {
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

    public function getHookMode(): string {
        return $this->hookMode;
    }

    public function setHookMode(string $hookMode): void {
        $this->hookMode = $hookMode;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isExecuteInsecure(): bool {
        return $this->executeInsecure;
    }

    /**
     * @param bool $executeInsecure
     */
    public function setExecuteInsecure(bool $executeInsecure): void {
        $this->executeInsecure = $executeInsecure;
    }

    /**
     * @return int|null
     */
    public function getRelatedItemId(): ?int {
        return $this->relatedItemId;
    }

    /**
     * @param int|null $relatedItemId
     */
    public function setRelatedItemId(?int $relatedItemId): void {
        $this->relatedItemId = $relatedItemId;
    }


}