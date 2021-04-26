<?php


namespace Kinintel\Objects\Datasource;


use Kinikit\Persistence\ORM\ActiveRecord;

/**
 * Data source instance - can be stored in database table
 *
 * @table ki_datasource_instance
 * @generate
 */
class DatasourceInstance extends ActiveRecord {

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $dataSourceClass;

    /**
     * @var string
     * @json
     */
    private $dataSourceConfig;


}