<?php


namespace Kinintel\Objects\Datasource;


use Kinintel\ValueObjects\Dataset\Field;

interface UpdatableTabularDatasource extends UpdatableDatasource {

    /**
     * Update from a new collection of fields if required
     *
     * @param Field[] $fields
     */
    public function updateFields($fields);

}