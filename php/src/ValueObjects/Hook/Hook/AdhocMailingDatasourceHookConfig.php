<?php

namespace Kinintel\ValueObjects\Hook\Hook;

use Kinintel\ValueObjects\Hook\DatasourceHookConfig;

class AdhocMailingDatasourceHookConfig implements DatasourceHookConfig {

    public function __construct(private int $mailingId, private ?array $emailAddresses = []) {
    }

    /**
     * @return int
     */
    public function getMailingId(): int {
        return $this->mailingId;
    }

    /**
     * @param int $mailingId
     */
    public function setMailingId(int $mailingId): void {
        $this->mailingId = $mailingId;
    }

    /**
     * @return int|null
     */
    public function getMailingListId(): ?int {
        return $this->mailingListId;
    }

    /**
     * @param int|null $mailingListId
     */
    public function setMailingListId(?int $mailingListId): void {
        $this->mailingListId = $mailingListId;
    }

    /**
     * @return array|null
     */
    public function getEmailAddresses(): ?array {
        return $this->emailAddresses;
    }

    /**
     * @param array|null $emailAddresses
     */
    public function setEmailAddresses(?array $emailAddresses): void {
        $this->emailAddresses = $emailAddresses;
    }


}

