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

    public function __construct($data) {
        parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) , 'Faction card') ;
    }
        
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['name'] = $this->getName() ;
        return $data ;
    }

}