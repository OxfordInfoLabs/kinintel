<?php


namespace Kinintel\Services\Datasource;


use Kinikit\Core\Configuration\FileResolver;
use Kinikit\Core\Exception\ItemNotFoundException;
use Kinikit\Core\Logging\Logger;
use Kinikit\Core\Serialisation\JSON\JSONToObjectConverter;
use Kinikit\Core\Validation\FieldValidationError;
use Kinikit\Core\Validation\ValidationException;
use Kinikit\Persistence\ORM\Exception\ObjectNotFoundException;
use Kinintel\Exception\DatasourceNotUpdatableException;
use Kinintel\Exception\InvalidParametersException;
use Kinintel\Exception\MissingDatasourceUpdaterException;
use Kinintel\Exception\UnsupportedDatasourceTransformationException;
use Kinintel\Objects\Authentication\AuthenticationCredentialsInstance;
use Kinintel\Objects\Dataset\Dataset;
use Kinintel\Objects\Dataset\DatasetInstanceSummary;
use Kinintel\Objects\Datasource\BaseDatasource;
use Kinintel\Objects\Datasource\Datasource;
use Kinintel\Objects\Datasource\DatasourceInstance;
use Kinintel\Objects\Datasource\DatasourceInstanceSearchResult;
use Kinintel\Objects\Datasource\DefaultDatasource;
use Kinintel\Objects\Datasource\UpdatableDatasource;
use Kinintel\ValueObjects\Parameter\Parameter;
use Kinintel\ValueObjects\Transformation\Paging\PagingMarkerTransformation;
use Kinintel\ValueObjects\Transformation\Paging\PagingTransformation;
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
     * @return DatasourceInstance
     */
    public function saveDataSourceInstance($dataSourceInstance) {
        $this->datasourceDAO->saveDataSourceInstance($dataSourceInstance);
        return $dataSourceInstance;
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
     * Get evaluated parameters for the passed datasource and transformations array
     *
     * @param string $datasourceInstanceKey
     *
     * @return Parameter[]
     */
    public function getEvaluatedParameters($datasourceInstanceKey) {

        // Grab the datasource for this data set instance by key
        $datasourceInstance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);

        // Grab parameters from top level data source instance
        $parameters = $datasourceInstance->getParameters();

        return $parameters;

    }


    /**
     * Get the evaluated data source for a source specified by key, using the supplied parameter values and applying the
     * passed transformations
     *
     * @param string $datasourceInstanceKey
     * @param mixed[] $parameterValues
     * @param TransformationInstance[] $additionalTransformations
     *
     * @return Dataset
     */
    public function getEvaluatedDataSource($datasourceInstanceKey, $parameterValues = [], $transformations = [], $offset = null, $limit = null) {
        $datasource = $this->getTransformedDataSource($datasourceInstanceKey, $transformations, $parameterValues, $offset, $limit);

        // Return the evaluated data source
        return $datasource->materialise($parameterValues ?? []);

    }


    /**
     * Get a transformed data source
     *
     * @param $datasourceInstanceKey
     * @param $transformations
     * @param mixed[] $parameterValues
     *
     * @return Datasource
     * @throws InvalidParametersException
     * @throws ObjectNotFoundException
     * @throws UnsupportedDatasourceTransformationException
     * @throws ValidationException
     */
    public function getTransformedDataSource($datasourceInstanceKey, $transformations, &$parameterValues, $offset = null, $limit = null) {

        // Grab the datasource for this data set instance by key
        $datasourceInstance = $this->datasourceDAO->getDataSourceInstanceByKey($datasourceInstanceKey);

        // Validate parameters first up
        $parameterValues = $this->validateParameters($datasourceInstance, $transformations, $parameterValues);


        // Grab the data source for this instance
        $datasource = $datasourceInstance->returnDataSource();

        // Apply transformations
        $datasource = $this->applyTransformationsToDatasource($datasource, $transformations ?? [], $parameterValues, $offset, $limit);


        return $datasource;
    }


    /**
     * Apply the transformation instances to the supplied data source and return
     * a new datasource.
     *
     * @param Datasource $datasource
     * @param TransformationInstance[] $transformationInstances
     */
    private function applyTransformationsToDatasource($datasource, $transformationInstances, $parameterValues, $offset = null, $limit = null) {

        $pagingMarkerFound = false;
        $pagingTransformation = new PagingTransformation($limit, $offset);

        foreach ($transformationInstances as $transformationInstance) {
            $transformation = $transformationInstance->returnTransformation();

            // If a marker transformation, use a paging transformation instead
            if ($transformation instanceof PagingMarkerTransformation) {
                $pagingMarkerFound = true;
                $transformation = new PagingTransformation($limit, $offset);
            }

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

        // If no paging marker found and paging is supported as a transformation apply offset and limit
        if (!$pagingMarkerFound && $this->isTransformationSupported($datasource, $pagingTransformation) && $offset !== null && $limit !== null) {
            $datasource = $datasource->applyTransformation($pagingTransformation, $parameterValues);
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


    /**
     * Ensure all required parameters are supplied
     *
     * @param DatasourceInstance $datasourceInstance
     * @param TransformationInstance[] $transformationInstances
     *
     * @param mixed[] $parameterValues
     */
    private function validateParameters($datasourceInstance, $transformationInstances, $parameterValues) {

        $validationErrors = [];

        // Grab parameters from data source instance
        $parameters = $datasourceInstance->getParameters() ?? [];

        // Check that the parameters are supplied and of required type
        foreach ($parameters as $parameter) {
            $paramName = $parameter->getName();
            if (!isset($parameterValues[$paramName])) {

                if ($parameter->getDefaultValue()) {
                    $parameterValues[$paramName] = $parameter->getDefaultValue();
                } else {
                    $validationErrors[$paramName] = [
                        "required" => new FieldValidationError($paramName, "required", "Parameter {$paramName} is required")];
                }
            } else if (!$parameter->validateParameterValue($parameterValues[$paramName])) {
                $validationErrors[$paramName] = [
                    new FieldValidationError($paramName, "wrongtype", "Parameter {$paramName} is of the wrong type - should be {$parameter->getType()}")
                ];
            }
        }

        // Throw exception if at least one validation error
        if (sizeof($validationErrors) > 0) {
            throw new InvalidParametersException($validationErrors);
        }

        return $parameterValues;

    }


}