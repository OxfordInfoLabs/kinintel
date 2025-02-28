<?php

namespace Kinintel\Test\Services\Hook\Hook;

use Kinikit\Core\Testing\MockObjectProvider;
use Kinimailer\Objects\Template\TemplateParameter;
use Kinimailer\Services\Mailing\MailingService;
use Kinimailer\ValueObjects\Mailing\AdhocMailing;
use Kinintel\Services\Hook\Hook\AdhocMailingDatasourceHook;
use Kinintel\ValueObjects\Hook\Hook\AdhocMailingDatasourceHookConfig;
use PHPUnit\Framework\TestCase;

include_once "autoloader.php";

class AdhocMailingDatasourceHookTest extends TestCase {

    /**
     * @var MailingService
     */
    private $mailingService;

    /**
     * @var AdhocMailingDatasourceHook
     */
    private $hook;

    public function setUp(): void {
        $this->mailingService = MockObjectProvider::mock(MailingService::class);
        $this->hook = new AdhocMailingDatasourceHook($this->mailingService);
    }

    public function testAdhocMailingCalledForConfiguredMailingWithSendToMailingListEnabled() {

        $hookConfig = new AdhocMailingDatasourceHookConfig(22);
        $this->hook->processHook($hookConfig,"add", [
            ["name" => "John Brown", "age" => 25, "dob" => "01/01/1997"]
        ]);

        $expectedMailing = new AdhocMailing(22,"",null,true,[],[
            new TemplateParameter("data", "Data", null, ["name" => "John Brown", "age" => 25, "dob" => "01/01/1997"])
        ]);

        $this->assertTrue($this->mailingService->methodWasCalled("processAdhocMailing", [$expectedMailing]));
    }

    public function testAdhocMailingSentToExplicitEmailAddressesIfSupplied() {

        $hookConfig = new AdhocMailingDatasourceHookConfig(22,[
            "test@one.com",
            "test@two.com"
        ]);
        $this->hook->processHook($hookConfig,"add", [
            ["name" => "John Brown", "age" => 25, "dob" => "01/01/1997"]
        ]);

        $expectedMailing1 = new AdhocMailing(22,"","test@one.com",false,[],[
            new TemplateParameter("data", "Data", null, ["name" => "John Brown", "age" => 25, "dob" => "01/01/1997"])
        ]);

        $expectedMailing2 = new AdhocMailing(22,"","test@two.com",false,[],[
            new TemplateParameter("data", "Data", null, ["name" => "John Brown", "age" => 25, "dob" => "01/01/1997"])
        ]);

        $this->assertTrue($this->mailingService->methodWasCalled("processAdhocMailing", [$expectedMailing1]));
        $this->assertTrue($this->mailingService->methodWasCalled("processAdhocMailing", [$expectedMailing2]));

    }



}