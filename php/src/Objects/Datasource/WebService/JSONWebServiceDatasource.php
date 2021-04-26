<?php


namespace Kinintel\Objects\Datasource\WebService;


use Kinikit\Core\Util\Primitive;
use Kinintel\ValueObjects\Dataset\Dataset;
use Kinintel\ValueObjects\Dataset\Field;
use Kinintel\ValueObjects\Dataset\TabularDataset;
use Kinintel\ValueObjects\Datasource\WebService\JSONWebServiceDataSourceConfig;

class JSONWebServiceDatasource extends WebServiceDatasource {

    /**
     * Override the config class to ensure it's the right one for JSON web services
     *
     * @return string
     */
    public function getConfigClass() {
        return JSONWebServiceDataSourceConfig::class;
    }


    /**
     * Map the result from the webservice to JSON using configured rules
     *
     * @param $result
     * @return Dataset
     */
    public function materialiseWebServiceResult($result) {

        $columns = [];
        $data = [];

        $decodedResult = json_decode($result, true);


        $config = $this->getConfig() ?? new JSONWebServiceDataSourceConfig();


        // if result path, drill down to here first
        if ($config->getResultMapping()->getResultPath()) {
            $resultPath = $config->getResultMapping()->getResultPath();
            $decodedResult = $this->drillDown($resultPath, $decodedResult);
        }

        // If a single result, convert to array for processing
        if (!is_array($decodedResult) || $config->getResultMapping()->isSingleResult()) {
            $decodedResult = [$decodedResult];
        }

        // Loop through each item
        foreach ($decodedResult as $item) {
            // If item is an array, we map accordingly
            if (is_array($item)) {
                $dataItem = [];
                foreach ($item as $field => $value) {
                    $columnName = $field;
                    if (is_numeric($field)) {
                        $columnName = "value" . ($columnName + 1);
                    }
                    $columns = $this->ensureColumn($columnName, $columns);
                    $dataItem[$columnName] = $value;
                }
                $data[] = $dataItem;
            } else if (Primitive::isPrimitive($item)) {
                $columns = $this->ensureColumn("value", $columns);
                $data[] = ["value" => $item];
            }
        }

        return new TabularDataset(array_values($columns), $data);

    }

    // Ensure a column exists
    private function ensureColumn($columnName, $columns) {

        if (!isset($columns[$columnName])) {

            $fieldTitle = ucfirst($columnName);
            $substituted = preg_replace("/([A-Z0-9]+)/", " $1", substr($fieldTitle, 1));
            $substituted = preg_replace("/_([a-z0-9])/", " $1", $substituted);
            $fieldTitle = substr($fieldTitle, 0, 1) . $substituted;

            $columns[$columnName] = new Field($columnName, $fieldTitle);

        }

        return $columns;
    }


    // Drill down to a sub path in an object
    private function drillDown($path, $object) {

        $path = explode(".", $path);
        foreach ($path as $pathElement) {
            if (isset($object[$pathElement])) {
                $object = $object[$pathElement];
            } else {
                $object = [];
            }
        }

        return $object;
    }


}