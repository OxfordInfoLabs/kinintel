<?php


namespace Kinintel\Objects\Datasource;


/**
 * Updatable data source.  This interface must be implemented by datasources which support update as well as read.
 * i.e. those which we wish to use for caching of data etc.
 */
interface UpdatableDatasource extends Datasource {

    // Update mode constants
    const UPDATE_MODE_REPLACE = "replace";
    const UPDATE_MODE_APPEND = "append";


    /**
     * Update this datasource from a dataset (usually returned from a different datasource)
     * use the provided mode to determine how to do the update (replace / append)
     *
     * @param $dataset
     * @param string $updateMode
     *
     * @return mixed
     */
    public function update($dataset, $updateMode = self::UPDATE_MODE_APPEND);

}