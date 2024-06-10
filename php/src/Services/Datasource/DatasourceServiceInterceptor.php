<?php

namespace Kinintel\Services\Datasource;

use Kinikit\Core\DependencyInjection\ContainerInterceptor;
use Kinikit\Core\Logging\Logger;

class DatasourceServiceInterceptor extends ContainerInterceptor {

    /**
     * Intercept method calls to check access before proceeding with execution of data sources.
     * In particular, we want to do a tree check for snapshots as they present possible security
     * holes in data sharing.
     *
     * @param DatasourceService $objectInstance
     * @param $methodName
     * @param $params
     * @param $methodInspector
     *
     * @return void
     */
    public function beforeMethod($objectInstance, $methodName, $params, $methodInspector) {

        // If we are calling get transformed data source grab the dataset tree as a means of checking access permissions where
        // datasources are built from queries or other sources.
        if ($methodName == "getTransformedDataSource" && isset($params["datasourceInstance"]) && $params["datasourceInstance"]->getKey()) {
            $objectInstance->getDatasetTreeForDatasourceKey($params["datasourceInstance"]->getKey());
        }
        return $params;
    }


}