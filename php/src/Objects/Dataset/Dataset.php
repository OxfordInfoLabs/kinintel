<?php

namespace Kinintel\Objects\Dataset;

/**
 * Result of a query for a data source
 *
 * Interface Dataset
 */
interface Dataset {

    /**
     * @return mixed
     */
    public function getAllData();
}