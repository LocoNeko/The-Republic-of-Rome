<?php
namespace Entities ;

/**
 * string $causes : a list of effects caused by the war, separated by ','
 * For a Conflict, the cards_controlled attribute points to leaders
 * For example :
 * - Hannibal and Hasdrubal in the "leaders" deck of the second Punic War
 */
/**
 * @Entity  @Table(name="conflicts")
 **/
class Conflict extends Card
{
    /**
    * @Column(type="string")
    * @var string
    */
    protected $matches ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $nbOfMatch ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $description ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $active ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $causes ;
    /**
    * @Column(type="string", nullable=true)
    * @var string
    */
    protected $attacks ;
    /**
    * @Column(type="string", nullable=true)
    * @var string
    */
    protected $revolt ;
    /**
    * @Column(type="string", nullable=true)
    * @var string
    */
    protected $creates ;
    /**
    * @Column(type="integer", nullable=true)
    * @var int
    */
    protected $land ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $support ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $fleet ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $disaster ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $standoff ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $spoils ;
 
    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
    
    public function setMatches ($matches) {
        $this->matches = $matches ;
    }

    public function setNbOfMatch ($nbOfMatch) {
        $this->nbOfMatch = $nbOfMatch ;
    }
    
    public function setDescription ($description) {
        $this->description = $description ;
    }
    
    public function setActive ($active) {
        $this->active = $active ;
    }
    
    public function setCauses ($causes) {
        $this->causes = $causes ;
    }
    
    public function setAttacks ($attacks) {
        $this->attacks = $attacks ;
    }
    
    public function setRevolt ($revolt) {
        $this->revolt = $revolt ;
    }        // Unique to Conflicts

    
    public function setCreates ($creates) {
        $this->creates = $creates ;
    }
    
    public function setLand ($land) {
        $this->land = $land ;
    }
    
    public function setSupport ($support) {
        $this->support = $support ;
    }
    
    public function setFleet ($fleet) {
        $this->fleet = $fleet ;
    }
    
    public function setDisaster ($disaster) {
        $this->disaster = $disaster ;
    }
    
    public function setStandoff ($standoff) {
        $this->standoff = $standoff ;
    }
    
    public function setSpoils ($spoils) {
        $this->spoils = $spoils ;
    }
    
    public function getMatches() {
        return $this->matches ;
    }
    
    public function getNbOfMatch() {
        return $this->nbOfMatch ;
    }
    
    public function getDescription() {
        return $this->description ;
    }
    
    public function getActive() {
        return $this->active ;
    }
    
    public function getCauses() {
        return $this->causes ;
    }
        
    public function getAttacks() {
        return $this->attacks ;
    }
    
    public function getRevolt() {
        return $this->revolt ;
    }
    
    public function getCreates() {
        return $this->creates ;
    }
    
    public function getLand() {            
        return $this->land ;
    }
    
    public function getSupport() {            
        return $this->support ;
    }
    
    public function getFleet() {            
        return $this->fleet ;
    }
    
    public function getDisaster() {            
        return $this->disaster ;
    }
    
    public function getStandoff() {            
        return $this->standoff ;
    }
    
    public function getSpoils() {            
        return $this->spoils ;
    }
    
    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct($data) {
        parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) ) ;
        $this->setMatches ( (string)$data[3] ) ;
        $this->setNbOfMatch ( (int)$data[4]) ;
        $this->setDescription ( (string)$data[5] ) ;
        $this->setActive ( (bool)$data[6]) ;
        $this->setCauses ( is_string($data[7]) ? $data[7] : NULL ) ;
        $this->setAttacks ( is_string($data[8]) ? $data[8] : NULL ) ;
        $this->setRevolt = ( is_string($data[9]) ? $data[9] : NULL ) ;
        $this->setCreates( is_string($data[10]) ? $data[10] : NULL ) ;
        $this->setLand ( (int)$data[11] ) ;
        $this->setSupport ( (int)$data[12] ) ;
        $this->setFleet ( (int)$data[13] ) ;
        $this->setDisaster ( $data[14] ) ;
        $this->setStandoff ( $data[15] ) ;
        $this->setSpoils ( $data[16] ) ;
    }
    
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['name'] = $this->getName() ;
        $data['matches'] = $this->getMatches() ;
        $data['nbOfMatch'] = $this->getNbOfMatch() ;
        $data['description'] = $this->getDescription() ;
        $data['active'] = $this->getActive() ;
        $data['causes'] = $this->getCauses() ;
        $data['attacks'] = $this->getAttacks() ;
        $data['revolt'] = $this->getRevolt() ;
        $data['creates'] = $this->getCreates() ;
        $data['land'] = $this->getLand() ;
        $data['support'] = $this->getSupport() ;
        $data['fleet'] = $this->getFleet() ;
        $data['disaster'] = $this->getDisaster() ;
        $data['standoff'] = $this->getStandoff() ;
        $data['spoils'] = $this->getSpoils() ;
        return $data ;
    }
        
}