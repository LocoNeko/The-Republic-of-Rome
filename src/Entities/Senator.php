<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

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
    /** @Column(type="boolean") @var int */
    protected $steppedDown = FALSE ;
    /** @Column(type="integer") @var int */
    protected $freeTribune = 0 ;
    /** @Column(type="boolean") @var int */
    protected $returningGovernor = FALSE ;
    //the card where this Senator is fighting (conflict or Province) or NULL
    /** @ManyToOne(targetEntity="Card" , inversedBy="battlefieldFor") @JoinColumn(name="commanderIn_id", referencedColumnName="id", nullable=true) **/
    protected $commanderIn = NULL ;
    /** @OneToOne(targetEntity="Party" , inversedBy="leader") **/
    private $leaderOf ;
    /** @OneToOne(targetEntity="Party" , inversedBy="bidWith") **/
    private $biddingFor ;
    // A Senator can have any number of loyal legions
    /** @OneToMany(targetEntity="Legion", mappedBy="loyalTo") **/
    private $loyalLegions ;

    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
    public function setMIL ($MIL) 
    {
        if ($MIL!= $this->MIL) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('MIL', $this->MIL, $MIL);
            $this->MIL = $MIL ;
        }
    }

    public function setORA ($ORA) 
    {
        if ($ORA!= $this->ORA) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('ORA', $this->ORA, $ORA);
            $this->ORA = $ORA ;
        }
    }

    public function setLOY ($LOY) 
    {
        if ($LOY!= $this->LOY) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('LOY', $this->LOY, $LOY);
            $this->LOY = $LOY ;
        }
    }

    public function setINF ($INF) 
    {
        if ($INF!= $this->INF) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('INF', $this->INF, $INF);
            $this->INF = $INF ;
        }
    }

    public function setKnights ($knights) 
    {
        if ($knights!= $this->knights) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('knights', $this->knights, $knights);
            $this->knights = $knights ;
        }
    }

    public function setTreasury ($treasury) 
    {
        if ($treasury!= $this->treasury) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('treasury', $this->treasury, $treasury);
            $this->treasury = $treasury ;
        }
    }

    public function setPOP ($POP) 
    {
        if ($POP!= $this->POP) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('POP', $this->POP, $POP);
            $this->POP = $POP ;
        }
    }

    public function setOffice ($office) 
    {
        if ($office!= $this->office) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('office', $this->office, $office);
            $this->office = $office ;
        }
    }

    public function setPriorConsul ($priorConsul) 
    {
        if ($priorConsul!= $this->priorConsul) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('priorConsul', $this->priorConsul, $priorConsul);
            $this->priorConsul = $priorConsul ;
        }
    }

    public function setCorrupt ($corrupt) 
    {
        if ($corrupt!= $this->corrupt) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('corrupt', $this->corrupt, $corrupt);
            $this->corrupt = $corrupt ;
        }
    }

    public function setMajor ($major) 
    {
        if ($major!= $this->major) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('major', $this->major, $major);
            $this->major = $major ;
        }
    }

    public function setRebel ($rebel) 
    {
        if ($rebel!= $this->rebel) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('rebel', $this->rebel, $rebel);
            $this->rebel = $rebel ;
        }
    }

    public function setCaptive ($captive) 
    {
        if ($captive!= $this->captive) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('captive', $this->captive, $captive);
            $this->captive = $captive ;
        }
    }

    public function setFreeTribune ($freeTribune) 
    {
        if ($freeTribune!= $this->freeTribune) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('freeTribune', $this->freeTribune, $freeTribune);
            $this->freeTribune = $freeTribune ;
        }
    }

    public function setReturningGovernor ($returningGovernor) 
    {
        if ($returningGovernor!= $this->returningGovernor) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('returningGovernor', $this->returningGovernor, $returningGovernor);
            $this->returningGovernor = $returningGovernor ;
        }
    }

    public function setCommanderIn($commanderIn) 
    {
        if ($commanderIn!= $this->commanderIn) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('commanderIn', $this->commanderIn, $commanderIn);
            $this->commanderIn = $commanderIn ;
        }
    }

    public function setLeaderOf($leaderOf) 
    {
        if ($leaderOf!= $this->leaderOf) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('leaderOf', $this->leaderOf, $leaderOf);
            $this->leaderOf = $leaderOf ;
        }
    }

    public function setBiddingFor($biddingFor) 
    {
        if ($biddingFor!= $this->biddingFor) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('biddingFor', $this->biddingFor, $biddingFor);
            $this->biddingFor = $biddingFor ;
        }
    }

    public function setSteppedDown($steppedDown) 
    {
        if ($steppedDown!= $this->steppedDown) 
        {
            /** @todo trace : trying something else. No ónPropertyChanged in Entities, do everything in controllers */
            //$this->onPropertyChanged('steppedDown', $this->steppedDown, $steppedDown);
            $this->steppedDown = $steppedDown ;
        }
    }

    
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
    public function getCommanderIn() { return $this->commanderIn; }
    public function getLeaderOf() { return $this->leaderOf; }
    public function getSteppedDown() { return $this->steppedDown; }
    public function getBiddingFor() { return $this->biddingFor; }

    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    /**
     * Constructors generally set properties directly and don't use setters, since setters invoke trace creation
     * @param array $data
     * @param bool $fromcsv
     */
    public function __construct($data , $fromcsv = TRUE) {
        if ($fromcsv)
        {
            parent::__construct(
                (int)$data[0] ,
                ( is_string($data[1]) ? $data[1] : NULL ) ,
                ( preg_match('/\d{1,2}[a-cA-C]/' , (string)$data[3]) == 0 ? 'Senator' : 'Statesman')
            ) ;
            $this->senatorID = (string)( preg_match('/\d?\d\w?/i',$data[3]) ? $data[3] : NULL) ;
            $this->baseMIL = (int)($data[4]) ;
            $this->baseORA = (int)($data[5]) ;
            $this->baseLOY = (int)($data[6]) ;
            $this->baseINF = (int)($data[7]) ;
            $this->MIL = $this->baseMIL ;
            $this->ORA = $this->baseORA ;
            $this->LOY = $this->baseLOY ;
            $this->INF = $this->baseINF ;
            $this->specialLOY = ( is_string($data[8]) ? $data[8] : NULL ) ; /* A list of senatorID with + or - separated by ,. +X means : only loyal if X exists and is in the same party, -X : means loyalty 0 if in the same party as X*/
            $this->specialAbility = ( is_string($data[9]) ? $data[9] : NULL ) ; /* A list of abilities separated by ,  */
            $this->hasStatesman = (bool)($data[10]) ;
            $this->knights = 0 ;
            $this->treasury = 0 ;
            $this->POP = 0 ;
            $this->loyalLegions = new ArrayCollection() ;
        }
        else
        {
            parent::__construct((int)$data['cardId'], $data['name'] , $data['preciseType'] ) ;
            foreach ($data as $property=>$value)
            {
                $setter = 'set'.ucfirst($property);
                if (method_exists($this, $setter) && $property!='cardId' && $property!='name')
                {
                    $this->$setter($value) ;
                }
            }
        }
    }
    
    public function changeINF($value)
    {
        $this->INF += $value ;
        if ( $this->INF < 0 ) { $this->INF = 0 ; }
    }

    public function changeORA($value)
    {
        $this->ORA += $value ;
        if ( $this->ORA < 0 ) { $this->ORA = 0 ; }
    }

    public function changePOP($value)
    {
        $this->POP += $value ;
        if ( $this->POP < -9 ) { $this->POP = -9 ; }
        if ( $this->POP >  9 ) { $this->POP =  9 ; }
    }
    
    /**
     * Appoints this Senator to an Office
     * @param string $office
     * @throws Exception : Invalid Office , Already has non-Censor Office
     */
    public function appoint($office)
    {
        if (!in_array($office, self::$VALID_OFFICES))
        {
            throw new \Exception(sprintf(_('%s is not a valid office') , $office)) ;
        }
        $currentOffice = $this->getOffice() ;
        if ($currentOffice!=NULL && $currentOffice!='Censor')
        {
            throw new \Exception(sprintf(_('The Senator cannot hold another office while he is %s') , $currentOffice)) ;
        }
        $this->setOffice($office) ;
        switch ($office) 
        {
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
	public function checkCriteria($criteria)
	{
	    if ($criteria===TRUE)
	    {
		return TRUE ;
	    }
	    else
	    {
		switch($criteria) 
		{

		    // In a party & in Rome
		    case 'alignedInRome' :
		        return ( ($this->getDeck()->getInParty() != NULL) && $this->inRome() ) ;

		    // Holding an office & in Rome
		    case 'hasOfficeInRome' :
		        return ( in_array($this->getOffice() , \Entities\Senator::$VALID_OFFICES ) && $this->inRome() ) ;
		        
		    // Possible Consuls : In a party, in Rome, no office except Censor or MoH
		    case 'possibleConsul' :
		        return ( (in_array($this->getOffice() , array('Censor' , 'Master of Horse')) || $this->getOffice() == NULL )&& ($this->getDeck()->getInParty() != NULL) ) ;
		        
		    // Possible Censor
		    case 'possibleCensor' :
		        return $this->getPriorConsul() ;
		        
		    // Possible prosecutors : In a party, in Rome, not the Censor
		    case 'possibleProsecutor' :
		        return ( ($this->getOffice() != 'Censor') && ($this->getDeck()->getInParty() != NULL) && $this->inRome() ) ;

		    // In a party & either Rome Consul, Field Consul or Dictator
		    case 'canAppointDictator' :
		        return (in_array($this->getOffice() , array('Rome Consul' , 'Field Consul')) && ($this->getDeck()->getInParty() != NULL) && $this->inRome()) ;

                    // In a Party, in Rome, not an Official except Censor
		    case 'possibleDictator' :
		    case 'possibleMastersOfHorse' :
		        return ( ($this->getOffice() === 'Censor' || $this->getOffice() === NULL) && ($this->getDeck()->getInParty() != NULL) && $this->inRome() ) ;

		    // In a party & either Rome Consul, Field Consul or Dictator
		    case 'possibleCommanders' :
		        return (in_array($this->getOffice() , array('Rome Consul' , 'Field Consul' , 'Dictator')) && ($this->getDeck()->getInParty() != NULL) && $this->inRome()) ;

		    // In Rome, not necessarily in a party, no office
		    case 'possibleGovernor' :
                        return ( $this->inRome() && $this->getOffice()===NULL) ;

                    // all the offices
                    case 'isDictator' : return ( $this->getOffice() === 'Dictator' ) ;
                    case 'isRome Consul' : return ( $this->getOffice() === 'Rome Consul' ) ;
                    case 'isField Consul' : return ( $this->getOffice() === 'Field Consul' ) ;
                    case 'isCensor' : return ( $this->getOffice() === 'Censor' ) ;
                    case 'isMaster of Horse' : return ( $this->getOffice() === 'Master of Horse' ) ;
                    case 'isPontifex Maximus' : return ( $this->getOffice() === 'Pontifex Maximus' ) ;

		    // Proconsul
		    case 'isProconsul' :
		        if ($this->hasControlledCards())
		        {
		            foreach ($this->getCardsControlled()->getCards() as $card)
		            {
		                if ($card->getPreciseType()=='Conflict')
		                {
		                    return TRUE ;
		                }
		            }
		        }
		        return FALSE ;
		        
		    // In a party, in Rome, & hasn't been assassination target yet
		    case 'assassinationTarget' :
		        return ( ($this->getDeck()->getInParty() != NULL) && !($this->getDeck()->getInParty()->getAssassinationTarget()) && $this->inRome() ) ;

		    case 'isStatesman' :
		        return ($this->getPreciseType()=='Statesman') ;

		    // In the Game's deck with the name $criteria
		    default : 
		        $location = $this->getLocation() ;
		        return ( ($location['type'] == 'game') && ($location['name'] == $criteria ) ) ;
		}
	    }
	}
    
    public function inRome()
    {
        $result = TRUE ;
        // Captive
        if ($this->captive !== FALSE) 
        {
            $result = FALSE ;
        }
        // Governor
        /** @todo Senator->inRome() : How to deal with legates ? */
        if ($this->getCardsControlled()!==NULL) 
        {
            foreach ($this->getCardsControlled()->getCards() as $card) 
            {
                if($card->getPreciseType()=='Province') 
                {
                    $result = FALSE ;
                }
            }
        }
        // Commander of an army & Proconsul
        if ($this->getCommanderIn()!=FALSE)
        {
            $result = FALSE ;
        }
        return $result ;
    }
    
    /**
     * Returns the family number of a Statesman
     * @return String Family number
     */
    public function statesmanFamily () 
    {
        return
        (
            ($this->getPreciseType()!= 'Statesman') ?
            (string)$this->getSenatorID() :
            str_replace ( Array('A' , 'B' , 'C') , Array('' , '' , '') , $this->getSenatorID() )
        ) ;
    }
    
    /**
     * Returns the family ID of the Senator : same for a Family, excluding the letter for a Statesman
     * @return int Family ID
     */
    public function getFamilyID()
    {
        return (int)str_replace( Array('A' , 'B' , 'C') , Array('' , '' , '') , $this->getSenatorID() ) ;
    }
    
    public function getFullName() {
        $result = $this->getName() ;
        $location = $this->getLocation() ;
        switch ($location['type']) {
            case 'game' :
            case 'card' :
                $result.=' [under '.$location['name'].']' ;
                break ;
            case 'party' :
                $partyName = $location['value']->getName() ;
                $user_id = $location['value']->getUser_id() ;
                $result.=' [in '.$partyName.' ([['.$user_id.']]) ]';
                break;
            case 'hand' :
                $partyName = $location['value']->getName() ;
                $user_id = $location['value']->getUser_id() ;
                $result.=' [in the hand of '.$partyName.'] ([['.$user_id.']])';
        }
        return $result ;
    }
    
    public function statesmanPlayable($user_id) {
        if ($this->checkCriteria('isStatesman')===FALSE) {
            return array('flag' => FALSE , 'message' => _('Senator'));
        }
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
            return array('flag' => FALSE , 'message' => _('Statesman is in a party'));
        }
        return array('flag' => TRUE , 'message' => _('The corresponding family card is not in play') );
    }
    
    /**
     * Resets a Senator card to the default
     */
    public function resetSenator()
    {
        $this->setMIL ($this->getBaseMIL()) ;
        $this->setORA ($this->getBaseORA()) ;
        $this->setLOY ($this->getBaseLOY()) ;
        $this->setINF ($this->getBaseINF()) ;
        $this->setKnights(0) ;
        $this->setTreasury(0) ;
        $this->setPOP(0) ;
        $this->setOffice(NULL) ;
        $this->setPriorConsul(FALSE) ;		
        $this->setCorrupt(FALSE) ;
        $this->setMajor(FALSE) ;
        $this->setFreeTribune(0) ;
        $this->setRebel(FALSE) ;
        $this->setCaptive(FALSE) ;
        $this->setReturningGovernor(FALSE) ;
    }

    public function isLeader() 
    {
        return ($this->getLeaderOf()!==NULL) ;
    }

    /**
     * Changes the Senator's treasury by (int)$amount
     * @param int $amount
     */
    public function changeTreasury($amount)
    {
        $this->treasury+=(int)$amount ;
        if ($this->treasury<0)
        {
            $this->treasury=0 ;
        }
    }
    
    /**
     * Changes the Senator's knight by (int)$number
     * @param int $number
     */
    public function changeKnights($number)
    {
        $this->knights+=(int)$number ;
        if ($this->knights < 0)
        {
            $this->knights = 0 ;
        }
    }
    
    /**
     * Returns the actual Loyalty of a Senator as follows :
     * +7 for being in a Party
     * +/- the special LOY effects of being in the same party as an enemy, or a different party than a brother
     * @param \Entities\Game $game
     * @return int
     */
    public function getActualLOY($game)
    {
        $result = $this->getLOY() ;
        $location = $this->getLocation() ;
        // +7 LOY for paty affiliation
        $result += ($location['type']=='party' ? 7 : 0) ;
        if ( ($this->getPreciseType()=='Statesman') && ($this->getSpecialLOY()!=NULL) )
        {
            $list = explode(',', $this->getSpecialLOY()) ;
            foreach ($list as $friendOrFoe) {
                // $effect is + or -
                $effect = substr($friendOrFoe, 0, 1) ;
                $friendOrFoeID = substr($friendOrFoe, 1) ;
                $friendOrFoe = $game->getFilteredCards(array('senatorID'=>$friendOrFoeID))->first() ;
                if ($friendOrFoe!==FALSE && $friendOrFoe->getIsSenatorOrStatesman())
                {
                    $friendOrFoeLocation = $friendOrFoe->getLocation() ;
                    /**
                     * - Effect : Negative
                     * - Both Senator and Foe are in a party
                     * - Senator and Foe are in the same party
                     */
                    if ($effect=='-' && $friendOrFoeLocation['type']=='party' && $location['type']=='party' && $friendOrFoeLocation['value']->getUser_id()===$location['value']->getUser_id())
                    {
                        $result-=$this->getLOY();
                        break;
                    }
                    /**
                     * - Effect : Positive (means Senators must in the same party otherwise LOY is 0)
                     * Either :
                     *    - One of them is not in a party
                     * OR - They are both in a party, but not the same one
                     */
                    if ($effect=='+')
                    {
                        if ($friendOrFoeLocation['type']!=='party' || $location['type']=='party' || $friendOrFoeLocation['value']->getUser_id()!==$location['value']->getUser_id())
                        {
                            $result-=$this->getLOY();
                            break;
                        }
                    }
                }
            }
        }
        return $result ;
    }
}
