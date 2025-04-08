<?php

namespace Kinintel\Test\Services\Util\ValueFunction;


use Kiniauth\Objects\Account\Account;
use Kiniauth\Services\Application\Session;
use Kinikit\Core\Testing\MockObject;
use Kinikit\Core\Testing\MockObjectProvider;
use Kinintel\Services\Util\ValueFunction\AuthenticatedItemsValueFunction;

include_once "autoloader.php";

class AuthenticatedItemsValueFunctionTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var MockObject|Session
     */
    private $session;


    /**
     * @var AuthenticatedItemsValueFunction
     */
    private $valueFunction;


    public function setUp(): void {
        $this->session = MockObjectProvider::mock(Session::class);
        $this->valueFunction = new AuthenticatedItemsValueFunction($this->session);
    }

    public function testCanGetAuthenticatedItemsAsValueFunctions() {

        $this->session->returnValue("__getLoggedInAccount", new Account("Test Account", 0, Account::STATUS_ACTIVE, 55));

        $this->assertEquals("Test Account", $this->valueFunction->applyFunction("loggedInAccountName", 1, []));
        $this->assertEquals(55, $this->valueFunction->applyFunction("loggedInAccountId", 1, []));

        $this->valueFunction->setActiveProjectKey("testProject");

        $this->assertEquals("testProject", $this->valueFunction->applyFunction("activeProjectKey", 1, []));

    }

}