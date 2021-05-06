<?php

namespace Kinintel\Objects\Datasource\Amazon;

use Kinikit\Core\Configuration\Configuration;
use Kinintel\ValueObjects\Authentication\Generic\AccessKeyAndSecretAuthenticationCredentials;
use Kinintel\ValueObjects\Datasource\Configuration\Amazon\AmazonS3DatasourceConfig;

include_once "autoloader.php";

class AmazonS3DatasourceTest extends \PHPUnit\Framework\TestCase {

    public function testCanAccessUnauthenticatedBucketResource() {
        $this->assertTrue(true);
//
//        $datasource = new AmazonS3Datasource(new AmazonS3DatasourceConfig("eu-west-1", "support.oxil.uk", "test.json"),
//            new AccessKeyAndSecretAuthenticationCredentials(trim(Configuration::readParameter("test.s3.access_key")),
//                trim("test.s3.secret")));
//
//        $result = $datasource->materialiseDataset();
//
//        print_r($result);

    }

}