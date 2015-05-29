<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="parties")
 **/
class Party
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id;
    
    /** @ManyToOne(targetEntity="Game", inversedBy="parties") **/
    private $game ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    /** @Column(type="integer") @var int */
    protected $user_id ;

    /** @Column(type="string") @var string */
    protected $userName ;
    
    /** @Column(type="boolean") @var int */
    protected $readyToStart = FALSE ;

    /** @Column(type="datetime")  */
    protected $lastUpdate ;

    /** @Column(type="boolean") @var int */
    protected $assassinationAttempt = FALSE ;

    /** @Column(type="boolean") @var int */
    protected $assassinationTarget = FALSE ;

    /** @OneToOne(targetEntity="Deck" , mappedBy="inHand" , cascade={"persist"}) **/
    private $hand ;

    /** @OneToOne(targetEntity="Deck" , mappedBy="inParty" , cascade={"persist"}) **/
    private $senators ;
    
    /** @ManyToMany(targetEntity="Message", mappedBy="recipients" , cascade={"persist"} ) **/
    protected $messages ;

    /** @OneToOne(targetEntity="Senator" , mappedBy="leaderOf" , cascade={"persist"}) **/
    private $leader = NULL ;

    /** @Column(type="integer") @var int */
    protected $treasury = 0 ;
    
    /** @Column(type="boolean") @var boolean */
    protected $isDone = FALSE ;

    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
   
    public function setGame($game) { $this->game = $game ; }
    public function setName($name) { $this->name = $name ; }
    public function setUser_id($user_id) { $this->user_id = $user_id ; }
    public function setUserName($userName) { $this->userName = $userName; }
    public function setReadyToStart() { $this->readyToStart = TRUE ; }
    public function setLastUpdate($lastUpdate) { $this->lastUpdate = $lastUpdate ; }
    public function setAssassinationAttempt($assassinationAttempt) { $this->assassinationAttempt = $assassinationAttempt; }
    public function setAssassinationTarget($assassinationTarget) {  $this->assassinationTarget = $assassinationTarget; }
    public function setLeader($leader)
    {
        if ($this->getLeader()!=NULL)
        {
            $this->getLeader()->setLeaderOf(NULL) ;
        }
        $this->leader = $leader;
        $leader->setLeaderOf($this) ;
    }
    public function setTreasury($treasury) { $this->treasury = $treasury; }
    public function setIsDone($isDone) { $this->isDone = $isDone; }

    public function getId() { return $this->id; }
    public function getGame() { return $this->game ; }
    public function getName() { return $this->name ; }
    public function getUser_id() { return $this->user_id ; }
    public function getUserName() { return $this->userName; }
    public function getReadyToStart() { return $this->readyToStart ; }
    public function getHand() { return $this->hand ; }
    public function getSenators() { return $this->senators ; }
    public function getMessages() { return $this->messages ; }
    public function getLastUpdate() { return $this->lastUpdate ; }
    public function getAssassinationAttempt() { return $this->assassinationAttempt; }
    public function getAssassinationTarget() { return $this->assassinationTarget; }
    public function getLeader() { return $this->leader; }
    public function getTreasury() { return $this->treasury; }
    public function getIsDone() { return $this->isDone; }
 
    public function __construct($user_id , $userName , $name) {
        $this->setName($name) ;
        $this->setUser_id($user_id) ;
        $this->setUserName($userName) ;
        $this->messages = new ArrayCollection();
        $this->hand = new \Entities\Deck($this->getName().' - Cards in hand') ;
        $this->hand->setInHand($this) ;
        $this->senators = new \Entities\Deck($this->getName().' - Senators') ;
        $this->senators->setInParty($this) ;
    }

    public function saveData() {
        $data = array() ;
        $data['name'] = $this->getName() ;
        $data['user_id'] = $this->getUser_id() ;
        $data['readyToStart'] = $this->getReadyToStart() ;
        $data['hand'] = $this->getHand()->saveData() ;
        $data['senators'] = $this->getSenators()->saveData() ;
        return $data ;
    }
    
    public function loadData($data)
    {
        foreach ($data as $key=>$value)
        {
            // Non-arrays should all be treated later
            if (!is_array($value))
            {
                $getter = 'get'.ucfirst($key);
                if (method_exists($this, $getter) && !is_array($value))
                {
                    if ($this->$getter() != $value)
                    {
                        $setter = 'set'.ucfirst($key);
                        // TO DO  : Uncomment once happy
                        // $this->.$setter($value) ;
                        error_log('$party->'.$setter.' ('.$value.')') ;
                    }
                }
            }
            else
            {
                switch($key)
                {
                    case 'hand' :
                        $this->getHand()->loadData($value) ;
                        break ;
                    case 'senators' :
                        $this->getSenators()->loadData($value) ;
                        break ;
                }
            }
        }
    }

    /**
    * ----------------------------------------------------
    * General methods
    * ----------------------------------------------------
    */

    public function addMessage($message) {
        $this->getMessages()->add($message) ;
    }

    public function joinGame(Game $game)
    {
        if ($game->userAlreadyJoined($this->user_id)) {
            throw new \Exception(_('This user has already joined the game.'));
        } elseif ($game->partyAlreadyExists($this->name)) {
            throw new \Exception(sprintf(_('A party with name %1$s already exists in this game.') , $this->name));
        } else {
            $this->setGame($game) ;
            $game->getParties()->add($this) ;
            $this->setLastUpdate(new \DateTime('NOW') ) ;
            $game->log(_('[['.$this->getUser_id().']] {join,joins} the game as "%1$s"') , 'log' , array($this->getName()) ) ;
        }
    }
    
    public function getFullName() {
        return $this->getName().' ['.$this->getUserName().']';
    }

    public function getTotalVotes() {
        $total = 0 ;
        foreach ($this->getSenators()->getCards() as $senator) {
            if ($senator->inRome()) {
                $total+=$senator->getORA() + $senator->getKnights();
            }
        }
        return $total ;
    }
    
    public function changeTreasury($amount)
    {
        $this->treasury+=(int)$amount ;
    }
        
    /**
    * ----------------------------------------------------
    * Setup
    * ----------------------------------------------------
    */

    /**
     * Returns TRUE if this party has cards in hand that can be played during the Setup/Revolution phases :
     * - Concessions
     * - A Playable Statesman
     * @return boolean
     */
    public function hasPlayableCards() {
        try {
            foreach($this->getHand()->getCards() as $card) {
                if (    ($card->getPreciseType()=='Statesman' && $card->statesmanPlayable($this->getUser_id())['flag']==TRUE)
                    ||  ($card->getPreciseType()=='Concession')
                ) {
                    return TRUE ;
                }
            }
        } catch (Exception $e) {
            return FALSE ;
        }
        return FALSE ;
    }
    
    /**
    * ----------------------------------------------------
    * Revenue
    * ----------------------------------------------------
    */
    
    /**
     * Returns a list of the various components of base revenue : senators, leader, knights, concessions, provinces
     * @param ArrayCollection $legions
     * @return array ['total'] = int , ['senators'] = int , ['leader'] = string (his name) , ['knights'] = int ,
     * array ['concessions'] => array(Card , Senator) ,
     * array ['provinces'] => array(Card , Senator) ,
     * array['rebels'] => array (Senator , 'nbLegions' , 'loyal' , 'notLoyal' , 'list')
     * array['flag'] => ['drought'] , ['province'] , ['rebel'] : TRUE if this specific revenue action is relevant to this party
     */
    public function revenue_base($legions)
    {
        $result = array() ;
        $result['total'] = 0 ;
        $result['senators'] = 0 ;
        $result['leader'] = '' ;
        $result['knights'] = 0 ;
        $result['concessions_total'] = 0 ;
        $result['concessions'] = array() ;
        $result['provinces'] = array() ;
        $result['rebels'] = array() ;
        $result['flag'] = array() ;
        $result['flag']['drought'] = FALSE ;
        $result['flag']['province'] = FALSE ;
        $result['flag']['rebel'] = FALSE ;
        foreach ($this->getSenators()->getCards() as $senator)
        {
            if (!$senator->getRebel() && !$senator->getCaptive())
            {
                if ($this->getLeader()->getSenatorID() == $senator->getSenatorID())
                {
                    $result['total']+=3 ;
                    $result['leader']=$senator->getName() ;
                }
                else
                {
                    $result['total']+=1 ;
                    $result['senators']+=1 ;
                }
                $result['total']+=$senator->getKnights() ;
                $result['knights']+=$senator->getKnights() ;
                if ($senator->hasControlledCards())
                {
                    foreach ($senator->getCardsControlled()->getCards() as $card)
                    {
                        if ( $card->getPreciseType() == 'Concession' && $card->getIncome() > 0)
                        {
                            $result['total']+=$card->getIncome() ;
                            $result['concessions_total']+=$card->getIncome() ;
                            if ($card->getSpecial()=='drought')
                            {
                                $result['flag']['drought'] = TRUE ;
                            }
                            array_push($result['concessions'] , array('card' => $card , 'senator' => $senator) );
                        }
                        elseif ( $card->getPreciseType() == 'Province' )
                        {
                            $result['flag']['province'] = TRUE ;
                            array_push($result['provinces'] , array('card' => $card , 'senator' => $senator) );
                        }
                    }
                }
            }
            elseif ($senator->getRebel())
            {
                // Rebel senator's legions
                $nbLegions = 0 ;
                $nbVeteransLoyal = 0 ;
                $nbVeteransNotLoyal = 0 ;
                $legionList = array() ;
                foreach($legions as $legion)
                {
                    if ($legion->getCardLocation()!==NULL && $legion->getCardLocation()->getId() == $senator->getId())
                    {
                        $result['flag']['rebel'] = TRUE ;
                        array_push($legionList , $legion) ;
                        $nbLegions++ ;
                        if ($legion->getVeteran())
                        {
                            if ($legion->getLoyalTo()!==NULL && $legion->getLoyalTo()->getSenatorID() == $senator->getSenatorID())
                            {
                                $nbVeteransLoyal ++ ;
                            }
                            else
                            {
                                $nbVeteransNotLoyal ++ ;
                            }
                        }
                    }
                }
                //  Rebels CAN collect provincial spoils
                if ($senator->hasControlledCards())
                {
                    foreach ($senator->getCardsControlled()->getCards() as $card)
                    {
                        if ( $card->getPreciseType == 'Province' )
                        {
                            array_push($result['provinces'] , array($card , $senator) );
                        }
                    }
                }
                array_push($result['rebels'] , array('senator' => $senator , 'nbLegions' => $nbLegions , 'loyal' => $nbVeteransLoyal , 'notLoyal' => $nbVeteransNotLoyal , 'list' => $legionList) ) ;
            }
        }
        return $result ;
    }


}
