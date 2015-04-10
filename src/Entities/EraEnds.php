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

    public function __construct($data) {
        parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) ) ;
    }
        
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['name'] = $this->getName() ;
        return $data ;
    }

}