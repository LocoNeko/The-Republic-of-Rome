<?php
namespace Entities ;

/**
 * @Entity  @Table(name="factionCards")
 **/
class FactionCard extends Card
{
    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct($data , $fromcsv = TRUE) {
        if ($fromcsv)
        {
            parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) , 'Faction card') ;
        }
        else
        {
            parent::__construct((int)$data['cardId'], $data['name'] , 'Faction card' ) ;
        }
    }

}