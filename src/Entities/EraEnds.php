<?php
namespace Entities ;

/**
 * @Entity  @Table(name="EraEnds")
 **/
class EraEnds extends Card
{
    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct($data , $fromcsv = TRUE) {
        if ($fromcsv)
        {
            parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) , 'Era ends') ;
        }
        else
        {
            parent::__construct((int)$data['cardId'], $data['name'] , 'Era ends' ) ;
        }
    }
        
}