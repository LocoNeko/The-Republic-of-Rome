<?php
namespace Entities ;

/**
 * @var bool corrupt : This is only used for Provincial spoils corruption
 * @var bool major : Whether or not this Senator held an office before the current Senate phase
 */
/**
 * @Entity  @Table(name="leaders")
 **/
class Leader extends Card
{
    /** @Column(type="string", nullable=true) @var string */
    protected $matches = NULL ;
    
    /** @Column(type="string") @var string */
    protected $description='' ;
    
    /** @Column(type="integer") @var int */
    protected $strength ;
    
    /** @Column(type="integer") @var int */
    protected $disaster ;
    
    /** @Column(type="integer") @var int */
    protected $standoff ;
    
    /** @Column(type="string", nullable=true) @var string */
    protected $ability = NULL ;
    
    /*
    Causes : 0 = nothing , 1 = a random tax farmer , 2 = drought , ...
    '2+1' means : if matched with war 2, causes effect 1
    */
    /** @Column(type="string", nullable=true) @var int */
    protected $causes = NULL ;

    /**
    * ----------------------------------------------------
    * Getters & Setters
    * ----------------------------------------------------
    */
    
    public function setMatches ($matches) { $this->matches = $matches ; }
    public function setDescription ($description) { $this->description = $description ; }
    public function setStrength ($strength) { $this->strength = $strength ; }
    public function setDisaster ($disaster) { $this->disaster = $disaster ; }
    public function setStandoff ($standoff) { $this->standoff = $standoff ; }
    public function setAbility ($ability) { $this->ability = $ability ; }
    public function setCauses ($causes) { $this->causes = $causes ; }

    public function getMatches () { return $this->matches ; }
    public function getDescription () { return $this->description ; }
    public function getStrength () { return $this->strength ; }
    public function getDisaster () { return $this->disaster ; }
    public function getStandoff () { return $this->standoff ; }
    public function getAbility () { return $this->ability ; }
    public function getCauses () { return $this->causes ; }
    
    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct($data) {
        parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) , 'Leader' ) ;
        $this->setMatches ( is_string($data[3]) ? $data[3] : NULL ) ;
        $this->setDescription ((string)($data[4])) ;
        $this->setStrength ( (int)$data[5] ) ;
        $this->setDisaster ( (int)$data[6] ) ;
        $this->setStandoff ( (int)$data[7] ) ;
        $this->setAbility ( is_string($data[8]) ? $data[8] : NULL ) ;
        $this->setCauses ( is_string($data[9]) ? $data[9] : NULL ) ;

    }
            
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['name'] = $this->getName() ;
        $data['matches'] = $this->getMatches () ;
        $data['description'] = $this->getDescription () ;
        $data['strength'] = $this->getStrength () ;
        $data['disaster'] = $this->getDisaster () ;
        $data['standoff'] = $this->getStandoff () ;
        $data['ability'] = $this->getAbility () ;
        $data['causes'] = $this->getCauses () ;
        return $data ;
    }

}