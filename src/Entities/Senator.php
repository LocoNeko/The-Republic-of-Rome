<?php
namespace Entities ;

/**
 * @var bool corrupt : This is only used for Provincial spoils corruption
 * @var bool major : Whether or not this Senator held an office before the current Senate phase
 * @var bool hasStatesman : Whether or not at least one Statesman exists for this family
 */
/**
 * @Entity  @Table(name="senators")
 **/
class Senator extends Card
{
    public static $VALID_OFFICES = array('Dictator', 'Rome Consul' , 'Field Consul' , 'Censor' , 'Master of Horse' , 'Pontifex Maximus');

    /** @Column(type="string") @var string */
    protected $senatorID ;
    /** @Column(type="integer") @var int */
    protected $baseMIL ;
    /** @Column(type="integer") @var int */
    protected $baseORA ;
    /** @Column(type="integer") @var int */
    protected $baseLOY ;
    /** @Column(type="integer") @var int */
    protected $baseINF ;
    /** @Column(type="integer") @var int */
    protected $MIL ;
    /** @Column(type="integer") @var int */
    protected $ORA ;
    /** @Column(type="integer") @var int */
    protected $LOY ;
    /** @Column(type="integer") @var int */
    protected $INF ;
    /** @Column(type="string") @var string */
    protected $specialLOY ;
    /** @Column(type="string") @var string */
    protected $specialAbility ;
    /** @Column(type="boolean") @var int */
    protected $hasStatesman = FALSE ;
    /** @Column(type="integer") @var int */
    protected $knights ;
    /** @Column(type="integer") @var int */
    protected $treasury ;
    /** @Column(type="integer") @var int */
    protected $POP ;
    /** @Column(type="string", nullable=true) @var string */
    protected $office = NULL ;
    /** @Column(type="boolean") @var int */
    protected $priorConsul = FALSE ;
    /** @Column(type="boolean") @var int */
    protected $corrupt = FALSE ;
    /** @Column(type="boolean") @var int */
    protected $major = FALSE ;
    /** @Column(type="boolean") @var int */
    protected $rebel = FALSE ;
    /** @Column(type="boolean") @var int */
    protected $captive = FALSE ;
    /** @Column(type="integer") @var int */
    protected $freeTribune = 0 ;
    /** @Column(type="boolean") @var int */
    protected $returningGovernor = FALSE ;
    //protected $conflict ; // the card ID of the conflict this Senator is fighting or FALSE
    /** @OneToOne(targetEntity="Party" , inversedBy="leader") **/
    private $leaderOf ;
    


    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */

    public function setSenatorID($senatorID) { $this->senatorID = $senatorID ; }
    public function setBaseMIL($baseMIL) { $this->baseMIL = $baseMIL ; }
    public function setBaseORA($baseORA) { $this->baseORA = $baseORA ; }
    public function setBaseLOY($baseLOY) { $this->baseLOY = $baseLOY ; }
    public function setBaseINF($baseINF) { $this->baseINF = $baseINF ; }
    public function setMIL ($MIL) { $this->MIL = $MIL ; }
    public function setORA ($ORA) { $this->ORA = $ORA ; }
    public function setLOY ($LOY) { $this->LOY = $LOY ; }
    public function setINF ($INF) { $this->INF = $INF ; }
    public function setSpecialLOY ($specialLOY) { $this->specialLOY = $specialLOY ; }
    public function setSpecialAbility ($specialAbility) { $this->specialAbility = $specialAbility ; }
    public function setHasStatesman ($hasStatesman) { $this->hasStatesman = $hasStatesman ; }
    public function setKnights ($knights) { $this->knights = $knights ; }
    public function setTreasury ($treasury) { $this->treasury = $treasury ; }
    public function setPOP ($POP) { $this->POP = $POP ; }
    public function setOffice ($office) { $this->office = $office ; }
    public function setPriorConsul ($priorConsul) { $this->priorConsul = $priorConsul ; }
    public function setCorrupt ($corrupt) { $this->corrupt = $corrupt ; } 
    public function setMajor ($major) { $this->major = $major ; }
    public function setRebel ($rebel) { $this->rebel = $rebel ; }
    public function setCaptive ($captive) { $this->captive = $captive ; }
    public function setFreeTribune ($freeTribune) { $this->freeTribune = $freeTribune ; }
    public function setReturningGovernor ($returningGovernor) { $this->returningGovernor = $returningGovernor ; }      

    public function getSenatorID() { return $this->senatorID ; }      
    public function getBaseMIL() { return $this->baseMIL ; }
    public function getBaseORA() { return $this->baseORA ; }
    public function getBaseLOY() { return $this->baseLOY ; }
    public function getBaseINF() { return $this->baseINF ; }
    public function getMIL () { return $this->MIL ; }
    public function getORA () { return $this->ORA ; }
    public function getLOY () { return $this->LOY ; }
    public function getINF () { return $this->INF ; }
    public function getSpecialLOY () { return $this->specialLOY ; }
    public function getSpecialAbility () { return $this->specialAbility ; }
    public function getHasStatesman () { return $this->hasStatesman ; }
    public function getKnights () { return $this->knights ; }
    public function getTreasury () { return $this->treasury ; }
    public function getPOP () { return $this->POP ; }
    public function getOffice () { return $this->office ; }
    public function getPriorConsul () { return $this->priorConsul ; }
    public function getCorrupt () { return $this->corrupt ; }
    public function getMajor () { return $this->major ; }
    public function getRebel () { return $this->rebel ; }
    public function getCaptive () { return $this->captive ; }
    public function getFreeTribune () { return $this->freeTribune ; }
    public function getReturningGovernor () { return $this->returningGovernor ; }      
    
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['name'] = $this->getName() ;
        $data['senatorID'] = $this->getSenatorID() ;
        $data['baseMIL'] = $this->getBaseMIL() ;
        $data['baseORA'] = $this->getBaseORA() ;
        $data['baseLOY'] = $this->getBaseLOY() ;
        $data['baseINF'] = $this->getBaseINF() ;
        $data['MIL'] = $this->getMIL () ;
        $data['ORA'] = $this->getORA () ;
        $data['LOY'] = $this->getLOY () ;
        $data['INF'] = $this->getINF () ;
        $data['specialLOY'] = $this->getSpecialLOY () ;
        $data['specialAbility'] = $this->getSpecialAbility () ;
        $data['hasStatesman'] = $this->getHasStatesman () ;
        $data['knights'] = $this->getKnights () ;
        $data['treasury'] = $this->getTreasury () ;
        $data['POP'] = $this->getPOP () ;
        $data['office'] = $this->getOffice () ;
        $data['priorConsul'] = $this->getPriorConsul () ;
        $data['corrupt'] = $this->getCorrupt () ;
        $data['major'] = $this->getMajor () ;
        $data['rebel'] = $this->getRebel () ;
        $data['captive'] = $this->getCaptive () ;
        $data['freeTribune'] = $this->getFreeTribune () ;
        $data['returningGovernor'] = $this->getReturningGovernor () ;
        return $data ;
    }

    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct($data) {
        parent::__construct(
            (int)$data[0] ,
            ( is_string($data[1]) ? $data[1] : NULL ) ,
            ( preg_match('/\d{1,2}[a-cA-C]/' , (string)$data[3]) == 0 ? 'Senator' : 'Statesman')
        ) ;
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
    
    public function changeINF($value) {
        $this->INF += $value ;
        if ( $this->INF < 0 ) { $this->INF = 0 ; }
    }

    public function changeORA($value) {
        $this->ORA += $value ;
        if ( $this->ORA < 0 ) { $this->ORA = 0 ; }
    }

    public function changePOP($value) {
        $this->POP += $value ;
        if ( $this->POP < -9 ) { $this->POP = -9 ; }
        if ( $this->POP >  9 ) { $this->POP =  9 ; }
    }
    
    /**
     * Appoints this Senator to an Office
     * @param string $office
     * @throws Exception : Invalid Office , Already has non-Censor Office
     */
    public function appoint($office) {
        if (!in_array($office, self::$VALID_OFFICES)) {
            throw new Exception(sprintf(_('%s is not a valid office') , $office)) ;
        }
        $currentOffice = $this->getOffice() ;
        if ($currentOffice!=NULL && $currentOffice!='Censor') {
            throw new Exception(sprintf(_('The Senator cannot hold another office while he is %s') , $currentOffice)) ;
        }
        $this->setOffice($office) ;
        switch ($office) {
            case 'Dictator' :        $INFincrease = 7 ; break ;
            case 'Master of Horse' : $INFincrease = 3 ; break ;
            default :                $INFincrease = 5 ;
        }
        $this->changeINF($INFincrease) ;
    }
    
    /**
     * Return Boolean when testing the Senator against a Criteria 
     * @param string $criteria TRUE | 'alignedInRome' | a deck name
     * @return boolean
     */
    public function checkCriteria($criteria) {
        if ($criteria===TRUE) {
            return TRUE ;
        } else {
            switch($criteria) {
                
                // In a party & in Rome
                case 'alignedInRome' : 
                    return ( ($this->getDeck()->getInParty() != NULL) && $this->inRome() ) ;
                    
                // In a party, in Rome, not the Censor
                case 'possibleProsecutor' :
                    return ( ($this->getOffice() != 'Censor') && ($this->getDeck()->getInParty() != NULL) && $this->inRome() ) ;
                    
                // In a Party, in Rome, not an Official except Censor
                case 'possibleDictator' :
                case 'possibleMastersOfHorse' :
                    return ( ($this->getOffice() === 'Censor' || $this->getOffice() === NULL) && ($this->getDeck()->getInParty() != NULL) && $this->inRome() ) ;
                    
                // In a party & either Rome Consul, Field Consul or Dictator
                case 'possibleCommanders' :
                    return (in_array($this->getOffice() , array('Rome Consul' , 'Field Consul' , 'Dictator')) && ($this->getDeck()->getInParty() != NULL) ) ;
                    
                // In a party, in Rome, & hasn't been assassination target yet
                case 'assassinationTarget' :
                    return ( ($this->getDeck()->getInParty() != NULL) && !($this->getDeck()->getInParty()->getAssassinationTarget()) && $this->inRome() ) ;
                    
                // In the Game's deck with the name $criteria
                default : 
                    $location = $this->getLocation() ;
                    return ( ($location['type'] == 'game') && ($location['name'] == $criteria ) ) ;
            }
        }
    }
    
    public function inRome() {
        $result = TRUE ;
        // Captive
        if ($this->captive !== FALSE) {
            $result = FALSE ;
        }
        // Governor
        // TO DO : What about legates ?
        if ($this->getCardsControlled()!=NULL) {
            foreach ($this->getCardsControlled()->getCards() as $card) {
                if($card->getPreciseType=='Province') {
                    $result = FALSE ;
                }
            }
        }
        // Commander of an army & Proconsul
        // TO DO
        /*
        if ($this->getConflict!=FALSE) {
            $result = FALSE ;
        }
        */
        return $result ;
    }
    
    /**
     * Returns the family number of a Statesman
     * @return String Family number
     */
    public function statesmanFamily () {
        if ($this->getPreciseType()!= 'Statesman') {
            return (string)$this->getSenatorID() ;
        } else {
            return str_replace ( Array('A' , 'B' , 'C') , Array('' , '' , '') , $this->getSenatorID() );
        }
    }
    
    public function statesmanPlayable($user_id) {
        if ($this->getPreciseType() != 'Statesman') {
            return array('flag' => FALSE , 'message' => _('ERROR - The Statesman is not a statesman'));
        }
        $hand = $this->getDeck() ;
        if (!method_exists($hand, 'getInHand')) {
            return array('flag' => FALSE , 'message' => _('ERROR - The statesman is not in hand'));
        }
        $statesmanParty = $hand->getInHand() ;
        if ($statesmanParty !=NULL) {
            $game = $statesmanParty->getGame() ;
            foreach ($game->getParties() as $party) {
                foreach ($party->getSenators()->getCards() as $senator) {
                    // Check if the family is already in play
                    if ( ($senator->getPreciseType() == 'Senator') && ($senator->getSenatorID() == $this->statesmanFamily()) ) {
                        if ($party->getUser_id() != $user_id) {
                            return array('flag' => FALSE , 'message' => sprintf(_('The Family is already in party %s') , $party->getName()) );
                        } else {
                            return array('flag' => TRUE , 'message' => _('You have the family'));
                        }
                    }
                    // Check if a related Statesman is already in play
                    if ( ($senator->getPreciseType() == 'Statesman') && ($senator->statesmanFamily() == $this->statesmanFamily()) ) {
                        if ( ($this->statesmanFamily()!=25) && ($this->statesmanFamily()!=29) ) {
                            return array('flag' => FALSE , 'message' => sprintf(_('The related statesman %s is already in play.' , $senator->name)));
                        } else {
                            // The other brother is in play : this is valid
                            return array('flag' => TRUE , 'message' => sprintf(_('%1$s playable, but the other brother %2$s is in play.') , $this->name , $senator->name));
                        }
                    }
                }
            }
            foreach ($game->getDeck('forum')->getCards() as $card) {
                if ($card->getPreciseType()=='Senator' && ($card->getSenatorID() == $this->statesmanFamily()) ) {
                    return array('flag' => TRUE , 'message' => _('The corresponding family card is in the forum'));
                }
            }
        } else {
            return array('flag' => FALSE , 'message' => _('ERROR - This is not a hand'));
        }
        return array('flag' => TRUE , 'message' => _('The corresponding family card is not in play') );
    }

}