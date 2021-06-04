<?php


namespace Kinintel\Services\Datasource;


use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\MissingDatasourceUpdaterException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\ValueObjects\Transformation\TransformationInstance;

class DatasourceService {


    /**
     * @var DatasourceDAO
     */
    private $datasourceDAO;

    /**
     * DatasourceService constructor.
     *
     * @param DatasourceDAO $datasourceDAO
     */
    public function __construct($datasourceDAO) {
        $this->datasourceDAO = $datasourceDAO;
    }


    /**
     * Get an array of filtered datasources using passed filter string to limit on
     * name of data source.  This checks both local datasources and database ones.
     *
     * @param string $filterString
     * @param int $limit
     * @param int $offset
     */
    public function filterDatasourceInstances($filterString = "", $limit = 10, $offset = 0) {
        return $this->datasourceDAO->filterDatasourceInstances($filterString, $limit, $offset);
    }


    /**
     * Get a datasource instance by key
     *
     * @param $key
     * @return DatasourceInstance
     */
    public function getDataSourceInstanceByKey($key) {
        return $this->datasourceDAO->getDataSourceInstanceByKey($key);
    }


    /**
     * Save a datasource instance
     *
     * @param DatasourceInstance $dataSourceInstance
     */
    public function saveDataSourceInstance($dataSourceInstance) {
        $this->datasourceDAO->saveDataSourceInstance($dataSourceInstance);
    }


    /**
     * Remove a datasource instance by key
     *
     * @param $dataSourceInstanceKey
     */
    public function removeDatasourceInstance($dataSourceInstanceKey) {
        $this->datasourceDAO->removeDatasourceInstance($dataSourceInstanceKey);
    }


    /**
     * Get the evaluated data source for a source specified by key, using the supplied parameter values and applying the
     * passed transformations
     *
     * @param DatasetInstanceSummary $dataSetInstance
     * @param mixed[] $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return BaseDatasource
     */
    public function getEvaluatedDataSource($datasourceInstanceKey, $parameterValues = [], $transformations = []) {


        // Grab the datasource for this data set instance by key
        $datasourceInstance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);

        // Grab the data source for this instance
        $datasource = $datasourceInstance->returnDataSource();

        // If we have transformations, apply these now
        if ($transformations ?? []) {
            $datasource = $this->applyTransformationsToDatasource($datasource, $transformations, $parameterValues);
        }

        // Return the evaluated data source
        return $datasource;

    }


    /**
     * Apply the transformation instances to the supplied data source and return
     * a new datasource.
     *
     * @param Datasource $datasource
     * @param TransformationInstance[] $transformationInstances
     */
    private function applyTransformationsToDatasource($datasource, $transformationInstances, $parameterValues) {
        foreach ($transformationInstances as $transformationInstance) {
            $transformation = $transformationInstance->returnTransformation();

            if ($this->isTransformationSupported($datasource, $transformation)) {
                $datasource = $datasource->applyTransformation($transformation, $parameterValues);
            } else if ($datasource instanceof DefaultDatasource) {
                throw new UnsupportedDatasourceTransformationException($datasource, $transformation);
            } else {
                $defaultDatasource = new DefaultDatasource($datasource);
                if ($this->isTransformationSupported($defaultDatasource, $transformation))
                    $datasource = $defaultDatasource->applyTransformation($transformation, $parameterValues);
                else
                    throw new UnsupportedDatasourceTransformationException($datasource, $transformation);
            }

        }
        return $datasource;
    }

    // Check whether a transformation is supported by a datasource
    private function isTransformationSupported($datasource, $transformation) {
        foreach ($datasource->getSupportedTransformationClasses() ?? [] as $supportedTransformationClass) {
            if (is_a($transformation, $supportedTransformationClass) || is_subclass_of($transformation, $supportedTransformationClass)) {
                return true;
            }
        }
        return false;
    }


}