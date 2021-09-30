<?php


namespace Kinintel\Services\Alert;


use Kiniauth\Services\Workflow\Task\Task;

class AlertGroupTask implements Task {

    /**
     * @var AlertService
     */
    private $alertService;


    /**
     * Construct with an alert service instance
     *
     * AlertGroupTask constructor.
     *
     * @param AlertService $alertService
     */
    public function __construct($alertService) {
        $this->alertService = $alertService;
    }

    /**
     * Run all alerts for the alert group id identified in config
     *
     * @param $configuration
     * @return bool|void
     */
    public function run($configuration) {
        if ($configuration && ($configuration["alertGroupId"] ?? null)) {
            $this->alertService->processAlertGroup($configuration["alertGroupId"]);
            return "Success";
        }
    }
}