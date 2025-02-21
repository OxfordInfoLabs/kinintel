<?php

namespace Kinintel\Services\Hook\Hook;

use Kinimailer\Objects\Template\TemplateParameter;
use Kinimailer\Services\Mailing\MailingService;
use Kinimailer\ValueObjects\Mailing\AdhocMailing;
use Kinintel\Services\Hook\DatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\AdhocMailingDatasourceHookConfig;

class AdhocMailingDatasourceHook implements DatasourceHook {

    public function __construct(private MailingService $mailingService) {
    }

    /**
     * @return string
     */
    public function getConfigClass() {
        return AdhocMailingDatasourceHookConfig::class;
    }

    /**
     * @param AdhocMailingDatasourceHookConfig $hookConfig
     * @param string $updateMode
     * @param mixed $updateData
     *
     * @return void
     */
    public function processHook($hookConfig, $updateMode, $updateData) {

        foreach ($updateData ?? [] as $updateDataItem) {

            foreach ($hookConfig->getEmailAddresses() ?? [null] as $emailAddress) {
                $adhocMailing = new AdhocMailing($hookConfig->getMailingId(), "", $emailAddress, $emailAddress === null, [],
                    [new TemplateParameter("data", "Data", null, $updateDataItem)]);
                $this->mailingService->processAdhocMailing($adhocMailing);
            }
        }
    }
}