<?php
namespace Entities ;

/**
 * @var bool corrupt : This is only used for Provincial spoils corruption
 * @var bool major : Whether or not this Senator held an office before the current Senate phase
 */
/**
 * @Entity  @Table(name="senators")
 **/
class Senator extends Card
{
    public static $VALID_OFFICES = array('Dictator', 'Rome Consul' , 'Field Consul' , 'Censor' , 'Master of Horse' , 'Pontifex Maximus');

    /**
    * @Column(type="string")
    * @var string
    */
    protected $senatorID ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $baseMIL ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $baseORA ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $baseLOY ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $baseINF ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $MIL ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $ORA ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $LOY ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $INF ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $specialLOY ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $specialAbility ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $hasStatesman ; // Whether or not at least one Statesman exists for this family (TRUE|FALSE)
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $knights ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $treasury ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $POP ;
    /**
    * @Column(type="string", nullable=true)
    * @var string
    */
    protected $office = NULL ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $priorConsul = FALSE ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $corrupt = FALSE ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $major = FALSE ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $rebel = FALSE ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $captive = FALSE ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $freeTribune = 0 ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $returningGovernor = FALSE ;
    //protected $conflict ; // the card ID of the conflict this Senator is fighting or FALSE

    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */

    public function setSenatorID($senatorID) {
        $this->senatorID = $senatorID ;
    }      

    public function setBaseMIL($baseMIL) {
        $this->baseMIL = $baseMIL ;
    }      

    public function setBaseORA($baseORA) {
        $this->baseORA = $baseORA ;
    }      
    
    public function setBaseLOY($baseLOY) {
        $this->baseLOY = $baseLOY ;
    }      
    
    public function setBaseINF($baseINF) {
        $this->baseINF = $baseINF ;
    }      
    
    public function setMIL ($MIL) {
        $this->MIL = $MIL ;
    }
    
    public function setORA ($ORA) {
        $this->ORA = $ORA ;
    }
    
    public function setLOY ($LOY) {
        $this->LOY = $LOY ;
    }
    
    public function setINF ($INF) {
        $this->INF = $INF ;
    }
    
    public function setSpecialLOY ($specialLOY) {
        $this->specialLOY = $specialLOY ;
    }
    
    public function setSpecialAbility ($specialAbility) {
        $this->specialAbility = $specialAbility ;
    }
    
    public function setHasStatesman ($hasStatesman) {
        $this->hasStatesman = $hasStatesman ;
    }
    
    public function setKnights ($knights) {
        $this->knights = $knights ;
    }
    
    public function setTreasury ($treasury) {
        $this->treasury = $treasury ;
    }
    
    public function setPOP ($POP) {
        $this->POP = $POP ;
    }

    public function setOffice ($office) {
        $this->office = $office ;
    }      

    public function setPriorConsul ($priorConsul) {
        $this->priorConsul = $priorConsul ;
    }

    public function setCorrupt ($corrupt) {
        $this->corrupt = $corrupt ;
    }

    public function setMajor ($major) {
        $this->major = $major ;
    }

    public function setRebel ($rebel) {
        $this->rebel = $rebel ;
    }

    public function setCaptive ($captive) {
        $this->captive = $captive ;
    }      

    public function setFreeTribune ($freeTribune) {
        $this->freeTribune = $freeTribune ;
    }      

    public function setReturningGovernor ($returningGovernor) {
        $this->returningGovernor = $returningGovernor ;
    }      

    public function getSenatorID() {
        return $this->senatorID ;
    }      

    public function getBaseMIL() {
        return $this->baseMIL ;
    }      

    public function getBaseORA() {
        return $this->baseORA ;
    }      
    
    public function getBaseLOY() {
        return $this->baseLOY = $baseLOY ;
    }      
    
    public function getMIL () {
        return $this->MIL ;
    }
    
    public function getORA () {
        return $this->ORA ;
    }
    
    public function getLOY () {
        return $this->LOY ;
    }
    
    public function getINF () {
        return $this->INF ;
    }
    
    public function getSpecialLOY () {
        return $this->specialLOY ;
    }
    
    public function getSpecialAbility () {
        return $this->specialAbility ;
    }
    
    public function getHasStatesman () {
        return $this->hasStatesman ;
    }
    
    public function getKnights () {
        return $this->knights ;
    }
    
    public function getTreasury () {
        return $this->treasury ;
    }
    
    public function getPOP () {
        return $this->POP ;
    }

    public function getOffice () {
        return $this->office ;
    }      

    public function getPriorConsul () {
        return $this->priorConsul ;
    }

    public function getCorrupt () {
        return $this->corrupt ;
    }

    public function getMajor () {
        return $this->major ;
    }

    public function getRebel () {
        return $this->rebel ;
    }

    public function getCaptive () {
        return $this->captive ;
    }      

    public function getFreeTribune () {
        return $this->freeTribune ;
    }      

    public function getReturningGovernor () {
        return $this->returningGovernor ;
    }      

    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct($data) {
        parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) ) ;
        $this->setSenatorID( (string)( preg_match('/\d?\d\w?/i',$data[3]) ? $data[3] : NULL) ) ;
        $this->setBaseMIL ( (int)($data[4]) ) ;
        $this->setBaseORA ( (int)($data[5]) ) ;
        $this->setBaseLOY ( (int)($data[6]) ) ;
        $this->setBaseINF ( (int)($data[7]) ) ;
        $this->setMIL ( $this->baseMIL ) ;
        $this->setORA ( $this->baseORA ) ;
        $this->setLOY ( $this->baseLOY ) ;
        $this->setINF ( $this->baseINF ) ;
        $this->setSpecialLOY( ( is_string($data[8]) ? $data[8] : NULL ) ) ; /* A list of senatorID with + or - separated by ,. +X means : only loyal if X exists and is in the same party, -X : means loyalty 0 if in the same party as X*/
        $this->setSpecialAbility ( ( is_string($data[9]) ? $data[9] : NULL ) ) ; /* A list of abilities separated by ,  */
        $this->setHasStatesman ( (bool)($data[10]) ) ;
        $this->setKnights (0);
        $this->setTreasury (0) ;
        $this->setPOP (0) ;
    }

}