<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="games")
 **/
class Game
{
    public static $VALID_PHASES = array('Setup','Mortality','Revenue','Forum','Population','Senate','Combat','Revolution','Rome falls') ;
    public static $VALID_SCENARIOS = array('EarlyRepublic') ;
    public static $VALID_VARIANTS = array('Pontifex Maximus' , 'Provincial Wars' , 'Rebel governors' , 'Legionary disbandment' , 'Advocates' , 'Passing Laws' , 'Hide odds') ;
    public static $VALID_DECKS = array('drawDeck' , 'earlyRepublic' , 'middleRepublic' , 'lateRepublic' , 'discard' , 'unplayedProvinces' , 'inactiveWars' , 'activeWars' , 'imminentWars' , 'unprosecutedWars' , 'forum' , 'curia') ;
    public static $GAMES_TABLE = array (
       7 => array ('name' => 'Slice & dice' , 'effect' => 1) , 
       13 => array ('name' => 'Blood fest' , 'effect' => 2) , 
       18 => array ('name' => 'Gladiator Gala' , 'effect' => 3) , 
    ) ;
    public static $MIN_PLAYERS = 3 ;
    public static $MAX_PLAYERS = 6 ;

    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    /** @Column(type="integer") @var int */
    protected $turn = 0 ;
    
    /** @Column(type="string") @var string */
    protected $phase = 'Setup';
    
    /** @Column(type="string") @var string */
    protected $subPhase = 'PickLeaders';
    
    /** @Column(type="boolean") @var int */
    protected $censorIsDone = FALSE ;
    
    /** @Column(type="boolean") @var int */
    protected $senateAdjourned = FALSE ;
    
    /** @Column(type="string") @var string */
    protected $scenario ;
    
    /** @Column(type="array") @var array */
    protected $variants = array() ;
    
    /** @Column(type="array") @var array */
    protected $eventTable = array() ;
    
    /** @Column(type="array") @var array */
    protected $events = array() ;
    
    /** @Column(type="array") @var array */
    protected $appealTable = array() ;

    /** @Column(type="array") @var array */
    protected $landBillsTable = array() ;

    /** @Column(type="array") @var array */
    protected $populationTable = array() ;

    /** @Column(type="integer") @var int */
    protected $unrest ;
    
    /** @Column(type="integer") @var int */
    protected $treasury ;
    
    // A Game has many parties
    /** @OneToMany(targetEntity="Party", mappedBy="game", cascade={"persist"} ) **/
    private $parties ;
    
    // A Game has many decks
    /** @OneToMany(targetEntity="Deck", mappedBy="game", cascade={"persist"} ) **/
    private $decks ;

    // A Game has many legions
    /** @OneToMany(targetEntity="Legion", mappedBy="game", cascade={"persist"} ) **/
    private $legions ;

    // A Game has many fleets
    /** @OneToMany(targetEntity="Fleet", mappedBy="game", cascade={"persist"} ) **/
    private $fleets ;

    // A Game has many proposals
    /** @OneToMany(targetEntity="Proposal", mappedBy="game", cascade={"persist"} ) **/
    private $proposals ;
    
    // A game can have one ongoing assassination attempt
    /** @Column(type="array") @var array */
    private $assassination = array() ;

    // A Game has many messages
    /** @OneToMany(targetEntity="Message", mappedBy="game", cascade={"persist"} ) **/
    private $messages ;
    
    /** @Column(type="datetime")  */
    private $created;
    
    /** @Column(type="string") */
    private $timezone;
    
    /** @Column(type="boolean") @var bool */
    private $localised = false;
    
    /******************************************************
     * Forum related
     ******************************************************/
    
    /** @OneToOne(targetEntity="Party") **/
    private $currentBidder ;
    
    /** @Column(type="integer") @var int */
    protected $initiative = 0 ;

    /** @OneToOne(targetEntity="Senator") @JoinColumn(name="persuasionTarget_id", referencedColumnName="internalId" , nullable=true) **/
    private $persuasionTarget ;

    /**
     * ----------------------------------------------------
     * Setters
     * ----------------------------------------------------
     */

    public function setName($name) { $this->name = $name ; }
    public function setCensorIsDone($flag) { $this->censorIsDone = $flag ; }
    public function setSenateAdjourned($flag) { $this->senateAdjourned = $flag ; }
    public function setUnrest($unrest) { $this->unrest = $unrest ; }
    public function setTreasury($treasury) { $this->treasury = $treasury ; }
    public function setInitiative($i) { $this->initiative = $i ; }
    private function setCreated($created) { $this->created = $created ; }
    public function setCurrentBidder($currentBidder) { $this->currentBidder = $currentBidder; }
    public function setPersuasionTarget($persuasionTarget) { $this->persuasionTarget = $persuasionTarget; }
    public function setVariants($variants) { $this->variants = $variants; }
    public function setEvent($eventNumber , $eventData) { $this->events[(int)$eventNumber] = $eventData; }
    public function setEventTable($i , $data) { $this->eventTable[$i]['EarlyRepublic'] = $data[0] ; $this->eventTable[$i]['MiddleRepublic'] = $data[1] ; $this->eventTable[$i]['LateRepublic'] = $data[2] ; }
    public function setAppealTable($data) { $this->appealTable[$data[0]] = array('votes' => $data[1] , 'special' => (isset($data[2]) ? $data[2] : NULL)); }
    public function setLandBillsTable ($data)
    {
        $this->landBillsTable[$data[0]] = array(
            'cost' => $data[1] ,
            'duration' => $data[2] ,
            'sponsor' => $data[3] ,
            'cosponsor' => $data[4] ,
            'against' => $data[5] ,
            'unrest' => $data[6] ,
            'repeal sponsor' => $data[7] ,
            'repeal vote' => $data[8] ,
            'repeal unrest' => $data[9] ,
            'inPlay' => 0
        );
    }

    /**
     * @param string $scenario
     * @throws \Exception
     */
    public function setScenario($scenario)
    {
        if (in_array($scenario, self::$VALID_SCENARIOS)) {
            $this->scenario = $scenario ;
        } else {
            throw new \Exception(sprintf(_('Invalid scenario %1$s') , $scenario));
        }
    }

    /**
     * @param string $phase
     * @throws \Exception
     */
    public function setPhase($phase)
    {
        if (in_array($phase, self::$VALID_PHASES)) {
            $this->phase = $phase ;
            $this->subPhase = '' ;
            $this->log(_('Phase : %1$s') , 'alert' , array($phase) ) ;
        } else {
            throw new \Exception(_('Invalid phase'));
        }
    }

    public function setSubPhase($subPhase)
    {
        $this->subPhase = $subPhase ;
        $this->log(_('Sub Phase : %1$s.') , 'alert' , array($subPhase) ) ;
    }

    public function updatePopulationTable($key , $value)
    {
        $this->populationTable[$key] = $value ;
    }

    public function __construct()
    {
        $createDate = new \DateTime('NOW') ;
        $this->localised = true;
        $this->created = $createDate;
        $this->timezone = $createDate->getTimeZone()->getName();
        $this->parties = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->legions = new ArrayCollection();
        $this->fleets = new ArrayCollection();
        $this->proposals = new ArrayCollection();
        $this->assassination = new ArrayCollection();
        $this->messages = new ArrayCollection();
        foreach (self::$VALID_DECKS as $deckName)
        {
            $deck = new \Entities\Deck($deckName) ;
            $deck->attachToGame($this) ;
        }
    }
    
    /**
    * ----------------------------------------------------
    * Getters
    * ----------------------------------------------------
    */

    public function getId() { return $this->id ; }
    public function getName() { return $this->name ; }
    public function getTurn() { return $this->turn ; }
    public function getPhase() { return $this->phase ; }
    public function getSubPhase() { return $this->subPhase ; }
    public function getInitiative() { return $this->initiative ; }
    public function getCensorIsDone() { return $this->censorIsDone ; }
    public function getSenateAdjourned() { return $this->senateAdjourned ; }
    public function getScenario() { return $this->scenario ; }
    public function getVariants() { return $this->variants ; }
    public function getUnrest() { return $this->unrest ; }
    public function getTreasury() { return $this->treasury ; }
    /** @return \Entities\Party[] */
    public function getParties() { return $this->parties ; }
    public function getDecks() { return $this->decks ; }
    public function getLegions() { return $this->legions; }
    public function getFleets() { return $this->fleets; }
    public function getProposals() { return $this->proposals; }
    public function getAssassination() { return $this->assassination; }
    public function getMessages() { return $this->messages; }
    public function getTimezone() { return $this->timezone ; }
    public function getCurrentBidder() { return $this->currentBidder; }
    /** @return \Entities\Senator | null */
    public function getPersuasionTarget() { return $this->persuasionTarget; }
    public function getEventTable() { return $this->eventTable; }
    public function getEvents() { return $this->events; }
    public function getAppealTable() { return $this->appealTable; }
    public function getLandBillsTable() { return $this->landBillsTable; }

    public function getCreated()
    {
        if ($this->timezone==NULL) { $this->timezone = 'UTC' ; }
        if (!$this->localised) { $this->created->setTimeZone(new \DateTimeZone($this->timezone)); }
        return $this->created;
    }
    
    public function saveData()
    {
        $data = array() ;
        foreach (get_object_vars($this) as $name=>$property)
        {
            $getter = 'get'.ucfirst($name);
            if (method_exists($this, $getter))
            {
                // Get the item and its class
                $item = $this->$getter() ;
                $dataType = gettype($item) ;
                if ($dataType=='object')
                {
                    $dataType=get_class($item);
                }
                // One-to-many relations - Array collections : parties, decks, legions, fleets but messages are not saved are they are sequentially created, so can be re-loaded based on time
                // Call to each object's saveData() methods
                if ($dataType=='Doctrine\\ORM\\PersistentCollection' && $name!='messages')
                {
                        foreach($item as $key=>$value)
                        {
                            $data[$name][$key] = $value->saveData() ;
                        }
                }
                // For one-to-one relations, just save the id
                elseif ($name=='currentBidder' || $name=='persuasionTarget')
                {
                    $data[$name] = (is_null($item) ? NULL : $item->getId() ) ;
                }
                // Scalar properties
                elseif ($name!='messages')
                {
                    $data[$name] = $item ;
                }
            }
        }
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
                if (method_exists($this, $getter))
                {
                    if ($this->$getter() != $value)
                    {
                        $setter = 'set'.ucfirst($key);
                        // TO DO  : Uncomment once happy
                        // $this->.$setter($value) ;
                        error_log('LOAD - $this->'.$setter.' ('.$value.')') ;
                    }
                }
            }
            else
            {
                foreach ($value as $key2=>$value2)
                {
                    switch($key)
                    {
                        case 'parties' :
                            $this->getParties()[$key2]->loadData($value2) ;
                            break ;
                        case 'decks' :
                            $this->getDecks()[$key2]->loadData($value2) ;
                            break ;
                    }
                }
            }
        }
    }

    /**
     * ----------------------------------------------------
     * General Functions
     * ----------------------------------------------------
     */
    
    public static function getAllVariants()
    {
        return self::$VALID_VARIANTS ;
    }
       
    public function nextTurn()
    {
        $this->turn++;
    }

    /**
     * Sets all parties isDone to FALSE
     */
    public function resetAllIsDone()
    {
        foreach($this->getParties() as $party)
        {
            $party->setIsDone(FALSE) ;
            $party->setBid(0) ;
            $party->setInitiativeWon(FALSE) ;
        }
    }

    /**
     * @return boolean TRUE if isDone() is TRUE for all parties | FALSE otherwise
     */
    public function isEveryoneDone()
    {
        foreach($this->getParties() as $party)
        {
            if ($party->getIsDone()===FALSE)
            {
                return FALSE ;
            }
        }
        return TRUE ;
    }

    public function getPopulationEffects($key)
    {
        return $this->populationTable[$key] ;
    }

    public function changeUnrest($value)
    {
        $this->unrest = max($this->getUnrest() + (int)$value , 0) ;
    }

    public function changeTreasury($amount)
    {
        $this->treasury+=(int)$amount ;
        // TO DO : Check game over
    }

    /**
     * Checks if a party with this user_id already exists
     * @param int $user_id
     * @return boolean 
     */
    public function userAlreadyJoined($user_id)
    {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->eq('user_id', (int)$user_id)) );
        return ( count($results)==1 ) ;
    }

    /**
     * Checks if a party with this name already exists
     * @param string $name
     * @return boolean 
     */
    public function partyAlreadyExists($name)
    {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->eq('name', $name)) );
        return ( count($results)==1 ) ;
    }
    
    public function getNumberOfPlayers()
    {
        return count($this->parties) ;
    }

    /**
     * Checks whether a variant is in play
     * @param string $variant
     * @return boolean
     */
    public function getVariantFlag($variant)
    {
        return (in_array($variant,$this->getVariants())) ;
    }

    /**
     * @param string $deckName
     * @return \Entities\Deck|boolean
     */
    public function getDeck($deckName)
    {
        $results = $this->getDecks()->matching( Criteria::create()->where(Criteria::expr()->eq('name', $deckName)) );
        return ( (count($results)==1) ? $results->first() : FALSE ) ;
    }
    
    /**
     * Returns the current state of the game for a given user_id
     * @param int $user_id
     * @return string JOINED|CAN_JOIN|STARTED|READY|FULL
     */
    public function getGameState($user_id)
    {
        $parties = $this->getParties() ;
        $result = $this->userAlreadyJoined($user_id) ? 'JOINED' : 'CAN_JOIN' ;
        if ($this->gameStarted())
        {
            $result ='STARTED' ;
        }
        elseif($result == 'JOINED')
        {
            foreach($parties as $party)
            {
                if ($party->getUser_id() == $user_id && $party->getReadyToStart())
                {
                    $result = 'READY' ;
                }
            }
        }
        elseif (count($parties) == $this::$MAX_PLAYERS)
        {
            $result = 'FULL' ;
        }
        return $result ;
    }

    public function gameStarted()
    {
        if ($this->getNumberOfPlayers()<self::$MIN_PLAYERS)
        {
                return FALSE ;
        }
        foreach($this->getParties() as $party)
        {
            if (!$party->getReadyToStart())
            {
                return FALSE ;
            }
        }
        return TRUE ;
    }
    
    /**
     * Logs a message
     * @param string $text A string with a sprintf format, including the order of parameters (%1$s , %2$d , etc) to handle possible mixing because of i18n
     * @param string $type message|alert|error|chat
     * @param mixed|NULL $parameters An array of values to be used in the text or NULL if the text has no parameters. If $parameters is not an array and not NULL, it's cast as array($parameters)
     * @param Entity\Party array|NULL $recipients An array of all the recipients parties or NULL if everyone
     * @param Entity\Party array|NULL $from Entity\Party of the sender or NULL if this is not a chat message
     * @throws Exception
     * @return \Entities\Message
     */
    public function log($text , $type='log' , $parameters=NULL , $recipients=NULL , $from=NULL)
    {
        try
        {
            $message = new \Entities\Message($this, $text, $type, $parameters, $recipients, $from) ;
        }
        catch (Exception $e) 
        {
            $message = new \Entities\Message($this, 'ERROR creating message', 'error') ;
        }
        $this->getMessages()->add($message) ;
        return $message ;
    }
    
    public function getNewMessages ($user_id) 
    {
        foreach($this->getParties() as $party) 
        {
            if ($party->getUser_id()==$user_id) 
            {
                $messages = array() ;
                foreach ($this->getMessages() as $message) 
                {
                    if ($message->getTime() > $party->getLastUpdate()) 
                    {
                        if ( $message->getRecipients()===NULL || count($message->getRecipients()) == 0 || $message->isRecipient($user_id)) 
                        {
                            array_push($messages , $message) ;
                        }
                    }
                }
                return $messages ;
            }
        }
        return array() ;
    }
    
    /**
     * Returns all the messages in this Game which have $user_id or NULL as a recipient (NULL means everybody)
     * @param int $user_id
     * @return array of messages
     */
    public function getAllMessages ($user_id) 
    {
        $messages = array() ;
        foreach($this->getParties() as $party) 
        {
            if ($party->getUser_id()==(int)$user_id) 
            {
                foreach (array_reverse($this->getMessages()->toArray()) as $message) 
                {
                    if ( $message->getRecipients()===NULL || count($message->getRecipients()) == 0 || $message->isRecipient($user_id)) 
                    {
                        array_push($messages , $message) ;
                    }
                }
            }
        }
        return $messages ;
    }
    
    /**
     * Gets a specific Party by its user_id
     * @param int $user_id
     * @return \Entities\Party []
     */
    public function getParty ($user_id)
    {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->eq('user_id', (int)$user_id)) );
        return $results->first() ;
    }
    
    /**
     * Gets all parties except the party of $user_id
     * @param int $user_id
     * @return ArrayCollection of Parties
     */
    public function getAllPartiesButOne ($user_id)
    {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->neq('user_id', (int)$user_id)) );
        return $results ;
    }
    
    /**
     * Get all parties names
     * @return array of [$user_id] => '[user name]'
     */
    public function getPartiesNames()
    {
        $result=array() ;
        foreach($this->getParties() as $party)
        {
            $result[$party->getUser_id()] = $party->getUserName() ;
        }
        return $result ;
    }

    public function getFilteredDecks($filters=array())
    {
        $result = new ArrayCollection() ;
        foreach ($this->getParties() as $party) 
        {
            $result->add($party->getSenators()) ;
            $result->add($party->getHand()) ;
            foreach($party->getSenators()->getCards() as $senator) 
            {
                if ($senator->hasControlledCards()) 
                {
                    $result->add($senator->getCardsControlled()) ;
                }
            }
        }

        // Game's main decks
        foreach ($this->getDecks() as $deck) 
        {
            $result->add($deck) ;
            foreach($deck->getCards() as $card) 
            {
                if ($card->hasControlledCards()) 
                {
                    $result->add($card->getCardsControlled()) ;
                }
            }
        }
        
        // Filter $results based on $filters
        foreach ($filters as $filterKey=>$filter)
        {
            $result = $result->filter(
                function (\Entities\Deck $deck) use ($filterKey , $filter) {
                    return $deck->checkValue($filterKey , $filter) ;
                }
            );
        }

        return $result ;
    }
    
    /**
     * @param array $filters Format array('property1' => 'value1' , 'property2' => 'value2')
     * @param string|boolean $criteria A criteria as defined in the checkCriteria function of the Senator entity
     * @return ArrayCollection
     */
    public function getFilteredCards($filters = array() , $criteria = NULL)
    {
        $result = new ArrayCollection() ;
        // First, put all cards (from parties, hands, and main decks) in $result
        // Parties
        foreach ($this->getParties() as $party) 
        {
            foreach($party->getSenators()->getCards() as $senator) 
            {
                $result->add($senator) ;

                // Senator cards controlled by their Statesman
                if ($senator->hasControlledCards()) 
                {
                    foreach ($senator->getCardsControlled()->getCards() as $card) 
                    {
                        $result->add($card) ;
                    }
                }
            }

            // Cards in Hand
            foreach($party->getHand()->getCards() as $card) 
            {
                $result->add($card) ;
            }
        }

        // Game's main decks
        foreach ($this->getDecks() as $deck) 
        {
            foreach($deck->getCards() as $card) 
            {
                $result->add($card) ;
                if ($card->hasControlledCards()) 
                {
                    foreach ($card->getCardsControlled()->getCards() as $subCard) 
                    {
                        $result->add($subCard) ;
                    }
                }
            }
        }

        // Second, filter $results based on $filters
        foreach ($filters as $filterKey=>$filter)
        {
            $result = $result->filter(
                function (\Entities\Card $card) use ($filterKey , $filter) {
                    return $card->checkValue($filterKey , $filter) ;
                }
            );
        }

        // Third, criteria based filter (only for Senators)
        // Removes all non-Senators as well
        if ($criteria!==NULL)
        {
            $result = $result->filter(
                function (\Entities\Senator $card) use ($criteria) {
                    return ( $card->getIsSenatorOrStatesman() && $card->checkCriteria($criteria) );
                }
            );
        }
        return $result ;
    }
    
    /**
     * Goes through all decks in the game and returns an ArrayCollection of Senator Entitites satisfying an optional criteria (or all of them if no criteria)
     * @param string|boolean $criteria
     * @return ArrayCollection
     * @throws Exception 'Error retrieving senators'
     */
    public function getAllSenators($criteria = TRUE)
    {
        return $this->getFilteredCards( array( 'isSenatorOrStatesman' => TRUE ) , $criteria ) ;
    }
    
    /**
     * 
     * @param boolean $presiding
     * @return \Entities\Senator
     */
    function getHRAO($presiding=FALSE)
    {
        // We rank all senators in Rome with an Office by order of VALID_OFFICES key (Dictator is before Rome Consul which is before Field Consul, etc)
        // Reminder : $VALID_OFFICES = array('Dictator', 'Rome Consul' , 'Field Consul' , 'Censor' , 'Master of Horse' , 'Pontifex Maximus');
        $rankedSenators = Array() ;
        foreach($this->getAllSenators('hasOfficeInRome') as $senator) 
        {
            if (!$presiding || $senator->getSteppedDown()===FALSE) 
            {
                $rankedSenators[array_search($senator->getOffice(), \Entities\Senator::$VALID_OFFICES)] = $senator ;
            }
        }
        ksort($rankedSenators) ;
        
        // We found at least one ranked Senator
        if (count($rankedSenators)>0) 
        {
            // If we are looking for the presiding magistrate, The Censor must be returned during the Senate phase if the sub phase is Prosecutions
            // TO DO : what if the all thing was interupted by a Special Assassin Prosecution ?
            if ( $presiding && $this->getPhase()=='Senate' && $this->getSubPhase()=='Prosecutions' && isset($rankedSenators[3]) ) 
            {
                return $rankedSenators[3] ;
            // Otherwise, the HRAO
            }
            else
            {
                return array_shift($rankedSenators) ;
            }
        }
            
        /* If we reach this part, the HRAO couldn't be determined because there is no Official present in Rome
         * So we check highest INF, break ties with Oratory then lowest ID
         * I'm very proud of this function ! ;-)
         */
        $allSenators = $this->getAllSenators('alignedInRome')->toArray() ;
        usort ($allSenators, function($a, $b)
        {
            if (($a->getINF()) != ($b->getINF())) 
            {
                return (($a->getINF()) < ($b->getINF())) ;
            }
            elseif (($a->getORA()) != ($b->getORA())) 
            {
                return (($a->getORA()) < ($b->getORA())) ;
            }
            else
            {
                return strcmp($a->getSenatorID() , $b->getSenatorID());
            }
        });
        
        // If we are looking for the Presiding Magistrate, we must ignore senators who have stepped down
        // The censor for prosecutions is completely irrelevant, since if we are here, there is no Censor, therefore no prosecutions...
        if ($presiding) 
        {
            while ($allSenators[0]->getSteppedDown()) 
            {
                array_shift($allSenators) ;
            }
        }
        // Return the first senator
        if (count($allSenators)> 0)
        {
            return $allSenators[0] ;
        }
        // We could not find any senator aligned in Rome, return anyone (this is so unlikely it's not even considered in the rules)
        else
        {
            return $this->getAllSenators()->toArray()[0] ;
        }
    }
    
    /**
     * 
     * @param int $user_id The user_id of the player playing the Statesman
     * @param string $statesmanId The senator ID of the Statesman being played
     * @return boolean Success or Failure
     * @throws \Exception
     */
    public function playStatesman($user_id , $statesmanId) 
    {
        $party=$this->getParty($user_id) ;
        $statesman = $this->getParty($user_id)->getHand()->getFirstCardByProperty('senatorID', $statesmanId) ;
        if ($statesman->getPreciseType()!=='Statesman') 
        {
            throw new \Exception(sprintf(_('ERROR - %1$s is not a Statesman') , array($statesman->getName()))) ;
        }
        $location = $statesman->getLocation() ;
        if ($location['type']!=='hand' || $location['value']->getUser_id()!=$party->getUser_id()) 
        {
            throw new \Exception(sprintf(_('ERROR - %1$s is not in [['.$party->getUser_id().']]\'s hand') , array($statesman->getName()))) ;
        }
        // Put the Statesman in the party
        $party->getHand()->getFirstCardByProperty('id', $statesman->getId() , $party->getSenators()) ;

        // Handle the family
        $familyMessage='' ;
        $familyID = $statesman->statesmanFamily() ;
        foreach($this->getAllSenators() as $aSenator) 
        {
            if ($aSenator->getSenatorID()==$familyID) 
            {
                $family=$aSenator ;
                break ;
            }
        }
        
        if (isset($family)) {
            $familyLocation = $family->getLocation() ;

            // The family was found in the player's party - Play the Statesman and make him control the Family
            if ( ($familyLocation['type']=='party') && $familyLocation['value']->getUser_id()==$party->getUser_id())
            {
                $familyLocation['value']->getSenators()->getFirstCardByProperty('id', $family->getId() , $statesman->getCardsControlled()) ;
                // Adjust Statesman's value that are below the Family's
                $statesman->setPriorConsul($family->getPriorConsul()) ;
                if ($family->getINF() > $statesman->getINF()) { $statesman->setINF($family->getINF()) ; }
                if ($family->getPOP() > $statesman->getPOP()) {$statesman->setPOP($family->getPOP()) ; }
                $statesman->setTreasury($family->getTreasury()) ;
                $statesman->setKnights($family->getKnights()) ;
                $statesman->setOffice($family->getOffice()) ;
                $family->resetSenator() ;
                // The family was the party's leader
                if ($party->getLeader()->getSenatorID() == $family->getSenatorID()) { $party->setLeader($statesman); }
                $familyMessage=_(' {You have,He has} the Family and {put,puts} it under the Statesman.');
                
            // The Family was found in the forum - Play the Statesman and make him control the Family
            }
            elseif (($familyLocation['type']=='game') && ($familyLocation['name']=='forum') ) 
            {
                $this->getDeck('forum')->getFirstCardByProperty('id', $family->getId() , $statesman->getCardsControlled()) ;
                $familyMessage=_(' {You take,He takes} the Family from the forum and {put,puts} it under the Statesman.');
            }
            // Move any card controlled by the Family on the Statesman
            if ($family->hasControlledCards())
            {
                while($family->getCardsControlled()->getNumberOfCards()>0)
                {
                    $statesman->getCardsControlled()->putCardOnTop($family->getCardsControlled()->drawFirstCard()) ;
                }
            }
        }
        $this->log(_('[['.$user_id.']]'.' {play,plays} Statesman %1$s.'.$familyMessage) , 'log' , array($statesman->getName()));
        return $statesman ;
    }

    /**
     * Returns the specific property ('level' by default) of an event, or the event itself ('ALL') found through its name or its number
     * @param string $type 'name' | 'number'
     * @param mixed $search The name or number of the event to look for
     * @param string $property 'level' (default) , 'name' , 'description' , 'max_level' , 'ALL'
     * @return mixed The event's property, the event itslef, or FALSE if the type was wrong or the event not found
     */
    public function getEventProperty ($type , $search , $property = 'level')
    {
        $event = NULL ;
        if ($type=='name')
        {
            foreach ($this->getEvents() as $anEvent)
            {
               if ($anEvent['name'] == $search)
                {
                    $event = $anEvent ;
                }
            }
        }
        elseif ($type=='number')
        {
            $event = $this->getEvents()[$search] ;
        }
        if ($event == NULL)
        {
            return FALSE ;
        }
        else
        {
            switch($property)
            {
                // name
                case 'name' :
                    return $event[($event['level']<=1 ? 'name' : 'increased_name')] ;
                // description
                case 'description' :
                    return $event[($event['level']<=1 ? 'description' : 'increased_description')] ;
                // max_level
                case 'max_level':
                    return $event['max_level'] ;
                // level
                case 'level':
                    return $event['level'] ;
                case 'ALL' :
                default :
                    return $event ;
            }
        }
    }

    /**
     * Set the level of an event, by its name or its number
     * @param string $type
     * @param string|int $search
     * @param int $level
     * @return boolean
     */
    public function setEventLevel ($type , $search , $level) {
        if ($type=='name')
        {
            foreach ($this->getEvents() as $key=>$event)
            {
               if ($event['name'] == $search)
                {
                    $this->getEvents()[$key]['level'] = (int)$level ;
                }
            }
            return FALSE ;
        }
        elseif ($type=='number')
        {
            $this->getEvents()[$search]['level'] = (int)$level ;
        }
        return FALSE ;
    }

    /**
     * Returns the number of Legions in garrison in the province
     * @param type $province
     * @return int
     */
    public function getProvinceGarrisons($province)
    {
        $result = 0 ;
        if ($province->getPreciseType() == 'Province')
        {
            $id = $province->getId();
            foreach ($this->getLegions() as $legion)
            {
                if ($legion->getCardLocation()!==NULL && $legion->getCardLocation()->getId() == $id)
                {
                    $result++;
                }
            }
        }
        else
        {
            return 0 ;
        }
        return $result;
    }

    /**
     * 
     * @param int $nb = Number of dice to roll (1 to 3)
     * @param int $evilOmensEffectPassed = Whether evil omens affect the roll by -1 , +1 or 0
     * @return array|boolean 'total' => the total roll , 'x' => value of die X so we can obtain 1 white die & 2 black dice
     */
    public function rollDice($nb , $evilOmensEffectPassed)
    {
        $nb = (int)$nb;
        if ($nb<1) { $nb = 1 ; }
        if ($nb>3) { $nb = 3 ; }
        // Sanitise $evilOmensEffect to be -1 ,+1 or 0
        $evilOmensEffect = (int)$evilOmensEffectPassed ;
        if ( ($evilOmensEffect!=-1) && ($evilOmensEffect!=1) )
        {
            $evilOmensEffect = 0 ;
        }
        $result = array() ;
        $result['total'] = 0 ;
        for ($i=0 ; $i<$nb ; $i++)
        {
            $result[$i]=mt_rand(1,6);
            $result['total']+=$result[$i];
        }
        // Add evil omens effects to the roll
        $result['total'] += $evilOmensEffect * $this->getEventProperty('name' , 'Evil Omens');
        return $result ;
    }
    
    /**
    * Convenience function to get a straight 1 die roll
    * @param type $evilOmensEffect
    * @return int The result
    */
    public function rollOneDie($evilOmensEffect)
    {
        $result = $this->rollDice(1 , $evilOmensEffect) ;
        if ($result!==FALSE)
        {
            return $result['total'];
        }
        else
        {
            return FALSE ;
        }
    }

    /**
    * Returns a description of the effects of evil omens on a die/dice roll. Empty if current evil omens level is 0
    * @param type $effect -1|+1
    * @return string description
    */
    public function getEvilOmensMessage($effect)
    {
        $evilOmensLevel = $this->getEventProperty('name' , 'Evil Omens') ;
        return ($evilOmensLevel==0 ? '' : sprintf(_(' (including %1$d from evil Omens)') , $effect*$evilOmensLevel) ) ;
    }

    /**
     * returns array of Parties from HRAO, clockwise in the same order as the array $this->party (order of joining game)
     * @return \Entities\Party[] An array of Parties
     */
    public function getOrderOfPlay()
    {
        $result = array() ;
        foreach($this->getParties() as $party)
        {
            array_push($result , $party );
        }
        $partyOfHRAO = $this->getHRAO()->getLocation()['value'] ;
        if (!is_null($partyOfHRAO))
        {
            $user_idHRAO = (int)$partyOfHRAO->getUser_id() ;
            while ((int)$result[0]->getUser_id() != $user_idHRAO)
            {
                array_push($result , array_shift($result) );
            }
        }
        return $result ;
    }

    /**
     * ----------------------------------------------------
     * Mortality
     * ----------------------------------------------------
     */
    
    /**
     * 
     * @param integer $qty
     * @return array
     */
    public function mortality_chits( $qty )
    {
        $result = array() ;
        $chits = array() ;
        for ($i=1 ; $i<=30 ; $i++) { $chits[$i] = $i ; }
        for ($i=31 ; $i<=34 ; $i++) { $chits[$i] = 0 ; }
        $chits [35] = -1 ; $chits [36] = -1 ;
        for ($i=(int)$qty ; $i>0 ; $i--)
        {
            $pick = array_rand($chits) ;
            if ($chits[$pick]==-1)
            {
                $i+=2;
                array_push($result , "DRAW 2");
            }
            else
            {
                if (($key = array_search($chits[$pick], $chits)) !== false)
                {
                    if ($chits[$pick]!=0)
                    {
                        array_push($result , $chits[$pick]);
                    }
                    else
                    {
                        array_push($result , "NONE");
                    }
                    unset($chits[$key]);
                }
            }
            if (count($chits)==2)
            {
                break;
            }
        }
        return $result;
    }
    
    /**
     * Kills the senator with $senatorID. This function handles :<br>
     * - Brothers<br>
     * - Statemen<br>
     * - Party leader<br>
     * - Where senator and controlled cards go (forum, curia, discard)<br>
     * @param string $senatorID The SenatorID of the dead senator
     * @param specificID TRUE if the Senator with this specific ID should be killed,<br>FALSE if the ID is a family, and Statesmen must be tested (default)
     * @param specificParty FALSE or equal to the $user_id of the party to which the dead Senator must belong<br>
     * @param POPThreshold FALSE or equal to the level of POP at which a Senator is safe<br>
     * @param epidemic FALSE or equal to either 'domestic' or 'foreign'<br>
     * @param mob FALSE or equal to an array with SenatorIDs that can be killed (Censor & Prosecutor during popular appeal, or whole party during assassination prosecution) <br>
     * senators from other parties will not be killed.<br>
     * FALSE (default)
     * @return array Just a one message-array, not an array of messages
     */
    public function killSenator($senatorID , $specificID=FALSE , $specificParty_UserId=FALSE , $POPThreshold=FALSE , $epidemic=FALSE , $popularAppealMob=FALSE)
    {
        $message = '' ;
        // Case of a random mortality chit
        if (!$specificID)
        {
            // Creates an array of potentially dead senators, to handle both Statesmen & Families
            // TO DO : Differentiate between Senators that were not in play, and senator that were in play but with the wrong paramters (POP threshold / in Rome / not in Rome)
            $deadSenators = array() ;
            foreach($this->getParties() as $party)
            {
                // If no party is targetted put any senator in the array, otherwise only put senators belonging to that party
                if ($specificParty_UserId===FALSE || ($specificParty_UserId!=FALSE && $specificParty_UserId==$party->getUser_id()))
                {
                    foreach ($party->getSenators()->getCards() as $senator)
                    {
                        // On top of that, if the $specificParty_UserId flag is set, we only consider senators in Rome
                        // And if the POPThreshold is set, only kill senators with POP below that
                        if 
                        (
                            ( $specificParty_UserId===FALSE || ($specificParty_UserId && $senator->inRome()) )
                            &&
                            ( $POPThreshold===FALSE || ($senator->getPOP()<$POPThreshold && $senator->inRome()) )
                            &&
                            ( $epidemic===FALSE || (($epidemic='domestic' && $senator->inRome()) || ($epidemic='foreign' && !$senator->inRome())) )
                            &&
                            ( $popularAppealMob===FALSE || (in_array($senator->getSenatorID() , $popularAppealMob)) )
                        )
                        {
                            if ( $senator->getFamilyID() == $senatorID )
                            {
                                array_push($deadSenators , $senator) ;
                            }
                        } 
                    }
                }
            }
            
            // Returns either no dead (Senator not in play), 1 dead (found just 1 senator matching the chit), or pick 1 of two brothers if they are both legally in play
            if (count($deadSenators)==0 && $specificID===FALSE && $specificParty_UserId===FALSE)
            {
                return array(_('This senator is not in Play, nobody dies.') , 'log') ;
            }
            elseif (count($deadSenators)>1)
            {
                // Pick one of two brothers
                $deadSenator = array_rand($deadSenators) ;
                $senatorID=$deadSenator->getSenatorID() ;
                $message.=_(' The two brothers are in play. ') ;
            }
            else
            {
                $deadSenator = $deadSenators[0];
            }
        }
        // Case of a specific Senator being targeted
        else
        {
            foreach($this->getAllSenators() as $senator)
            {
                if ($senator->getSenatorID()==$senatorID)
                {
                    $deadSenator = $senator ;
                }
            }
        }

        // Now that the dead Senator has been determined, kill him dead
        if (isset($deadSenator))
        {
            $location = $deadSenator->getLocation() ;
            
            if ($location['type']!== 'party')
            {
                return array(_('ERROR retrieving the party of the dead Senator') , 'error' );
            }
            else
            {
                $party = $location['value'] ;
            }

            // Death of a Statesman
            if ($deadSenator->getPreciseType() == 'Statesman')
            {
                $deadStatesman = $party->getSenators()->getFirstCardByProperty('senatorID' , $deadSenator->getSenatorID() , $this->getDeck('Discard')) ;
                $deadStatesman->resetSenator();
                $message.=sprintf(_('%s of party [['.$party->getUser_id()._(']] dies. The card is discarded. ')) , $deadStatesman->getName()) ;
            }
            
            // Death of a normal Senator
            else
            {
                $deadSenator->resetSenator() ;
                if ($party->getLeader()->getSenatorID() == $senatorID)
                {
                    $message.=sprintf(_('%s of party [['.$party->getUser_id()._(']] dies. This senator was party leader, the family stays in the party. ')) , $deadSenator->getName() );
                }
                else
                {
                    $party->getSenators()->getFirstCardByProperty('senatorID' , $senatorID, $this->getDeck('Curia') ) ;
                    $message.=sprintf(_('%1$s of party [[').$party->getUser_id()._(']] dies. The family goes to the curia. ') , $deadSenator->getName() );
                }
            }
    
            // Handle dead senators' controlled cards : Concessions, Provinces, Senators
            if ($deadSenator->hasControlledCards())
            {
                foreach($deadSenator->getCardsControlled()->getCards() as $card)
                {
                    
                    // Concession -> Curia
                    if ($card->getPreciseType()=='Concession')
                    {
                        $deadSenator->getCardsControlled()->getFirstCardByProperty('id' , $card->getId() , $this->getDeck('curia')) ;
                        $message.=sprintf(_('%s goes to the curia. ') , $card->getName());
                    }
                    
                    // Province -> Forum
                    elseif ($card->getPreciseType()=='Province')
                    {
                        $deadSenator->getCardsControlled()->getFirstCardByProperty('id' , $card->getId() , $this->getDeck('forum')) ;
                        $message.=sprintf(_('%s goes to the forum. ') , $card->getName());
                    }
                    
                    // Senator
                    elseif ($card->getPreciseType()=='Senator')
                    {
                        
                        // Was leader -> Party
                        if ($party->getLeader()->getSenatorID() == $deadStatesman->getSenatorID() )
                        {
                            // Now that the Satesman is dead, the family is the party leader
                            $party->setLeader($card) ;
                            $deadSenator->getCardsControlled()->getFirstCardByProperty('id' , $card->getId() , $party->getSenators()) ;
                            $message.=sprintf(_('%s stays in the party and is now leader. ') , $card->getName());
                        }
                        
                        // Was not leader -> Curia
                        else
                        {
                            $deadSenator->getCardsControlled()->getFirstCardByProperty('id' , $card->getId() , $this->getDeck('curia')) ;
                            $message.=sprintf(_('%s goes to the curia. ') , $card->getName());
                        }
                    }
                    
                    // Strange Card
                    else
                    {
                        return array(_('Error - A card controlled by the dead Senator was neither a Family, a Concession nor a Province.') , 'error');
                    }
                }
                
            }
        }
        else
        {
            return array(_('ERROR retrieving dead Senator data') , 'error' );
        }
        return array($message , 'log') ;
    }

    /**
     * ----------------------------------------------------
     * Revenue
     * ----------------------------------------------------
     */
    
    /**
     * Initialises the revenu phase by performing BarbarianRaids & InternalDisorder events if needed
     */
    public function revenue_init() 
    {
        $barbarianRaids = $this->getEventProperty('name', 'Barbarian Raids');
        $internalDisorder = $this->getEventProperty('name', 'Internal Disorder');
        $barbarianRaidsTargets = array() ;
        $internalDisorderTargets = array() ;
        foreach($this->getParties() as $party)
        {
            foreach ($party->getSenators()->getCards() as $senator)
            {
                if ($senator->hasControlledCards()) 
                {
                    foreach ($senator->getCardsControlled()->getCards() as $card)
                    {
                        if ($card->getPreciseType()=='Province')
                        {
                            $card->setOverrun(FALSE) ;
                            // Only for frontier provinces
                            if ($barbarianRaids>0 && $card->getFrontier())
                            {
                                array_push($barbarianRaidsTargets , array('province' => $card , 'senator' => $senator)) ;
                            }
                            // Only for undeveloped provinces
                            if ($internalDisorder>0 && $card->getDeveloped()===FALSE) {
                                array_push($internalDisorderTargets , array('province' => $card , 'senator' => $senator)) ;
                            }
                        }
                    }
                }
            }
        }
        foreach ($barbarianRaidsTargets as $target)
        {
            $this->doBarbarianRaids($barbarianRaids , $target['province'] , $target['senator']) ;
        }
        foreach ($internalDisorderTargets as $target)
        {
            $this->doInternalDisorder($internalDisorder , $target['province'] , $target['senator']) ;
        }
    }
    
    /**
     * Function that handles the Barabarian raid event first step (the rolls, and the immediate effects of overrunning)
     * Later, the overrun flag will be used to prevent income and development
     * @param integer $level the raids level : 1|2
     * @param Province $province The provincial Province being governingly governed by the governing Governor
     * @param Senator $senator The governing Governor governing the provincial Province being governinglly governed
     * @return array messages
     */
    public function doBarbarianRaids($level , $province , $senator )
    {
        $messages = array() ;
        $garrisons = $this->getProvinceGarrisons($province) ;
        $roll = $this->rollDice(2, -1) ;
        $total = $writtenForce + 2 * $garrisons + $governorMIL + $roll['total'];
        $message = sprintf(
            _('Province %s is attacked by %s Barabarian raids. Military force is %d (written force) + %d (for %d legions) + %d (%s\'s MIL), a %d (white die %d, black die %d) is rolled for a total of %d%s ') ,
            $province->getName() ,
            ($level==2 ? 'increased ' : '') ,
            $province->getLand() ,
            2*$garrisons ,
            $garrisons ,
            $senator->getMIL() ,
            $senator->getName() ,
            $roll['total'] ,
            $roll[0] ,
            $roll[1] ,
            $total ,
            $this->getEvilOmensMessage(-1)
        );
        if ($total>( $barbarianRaids==1 ? 15 : 17))
        {
            $message.= sprintf(_(' which is greater than %d, the province is safe.') , ($barbarianRaids==1 ? 15 : 17)) ;
            array_push($messages , array($message) ) ;
        }
        else
        {
            $province->setOverrun(TRUE) ;
            $message.= sprintf(_(' which is not greater than %d, the province is overrun.') , ($barbarianRaids==1 ? 15 : 17)) ;
            array_push($messages , array($message , 'alert') ) ;
            if ($province->getDeveloped())
            {
                $province->setDeveloped(FALSE) ;
                array_push($messages , array(_('The Province reverts to undeveloped status'),'alert'));
            }
            $mortalityChits = $this->mortality_chits($roll[1]) ;
            $message = sprintf(_('The black die was a %d, so %d mortality chits are drawn : ') , $roll[1]);
            $outcome = 'safe' ;
            $i=1 ;
            foreach($mortalityChits as $chit)
            {
                $message.=$chit.', ';
                if (    ($senator->getPreciseType()=='Senator' && $senator->getSenatorID()==$chit)
                    ||  ($senator->getPreciseType()=='Statesman' && $senator->statesmanFamily() ==$chit)
                )
                {
                    // The outcome is based on whether or not the chit drawn was the last (which means capture)
                    $outcome = ($i++==$roll[1] ? _('captured') : _('killed')) ;
                }
            }
            $message=substr($message, 0, -2);
            array_push($messages , array($message));
            switch($outcome)
            {
                case 'killed' :
                    $killMessage = $this->killSenator($senator->getSenatorID(), TRUE) ;
                    array_push($messages , $killMessage[0] , $killMessage[1]);
                    array_push($messages , array(sprintf(_('%s is killed by the barbaric barbarians.') , 'alert' , array($senator->getName()))));
                    break ;
                case 'captured' :
                    $senator->setCaptive('barbarians') ;
                    array_push($messages , array(sprintf(_('%s is captured by the barbaric barbarians. Ransom must be paid before next Forum phase or he\'s BBQ.') , 'alert' , array($senator->getName()))));
                    break ; 
                default :
                    array_push($messages , array(sprintf(_('%s is safe.') , 'log' , array($senator->getName())) ));
            }
        }
        foreach($messages as $message)
        {
            $this->log($message[0] , ( isset($message[1]) ? $message[1] : NULL ) , ( isset($message[2]) ? $message[2] : NULL ) ) ;
        }
    }

    /**
     * Function that handles the internal disorder events first step (order rolls and immediate effects of failure)
     * @param integer $level 1|2
     * @param Province $province The provincial Province being governingly governed by the governing Governor
     * @param Senator $senator The governing Governor governing the provincial Province being governinglly governed
     * @return array messages
     */
    public function doInternalDisorder($level , $province , $senator)
    {
        // TO DO : Refactor the messages parameters so they take advantage of the log(message , type, parameters) function
        $garrisons = $this->getProvinceGarrisons($province) ;
        $roll = $this->rollOneDie(-1);
        $message = sprintf(
            _('Province %s faces internal disorder, %s rolls a %d%s + %d garrisons for a total of %d') ,
            $province->getName() ,
            $senator->getName() ,
            $roll ,
            $this->getEvilOmensMessage(-1) ,
            $garrisons ,
            ($roll+$garrisons)
        );
        if (($roll+$garrisons) > ($level == 1 ? 4 : 5))
        {
            $message.sprintf(_(' which is greater than %d. The province will not generate revenue and cannot be improved this turn.') , ($level == 1 ? '4' : '5'));
            // Using the overrun property both for Barbarian raids & Internal Disorder
            $province->setOverrun(TRUE) ;
            $this->log($message) ;
        }
        else
        {
            // Revolt : Kill Senator, garrisons, and move Province to the Active War deck
            $message.=sprintf(_(' which is not greater than %d') , ($level == 1 ? '4' : '5')) ;
            $killMessage = $this->killSenator($senator->getSenatorID(), TRUE) ;
            $this->log($killMessage[0] , $killMessage[1]);

            // Note : The war is now in the forum, because of the killSenator function, hence the $this->getDeck('Forum')
            $this->getDeck('Forum')->getFirstCardByProperty('id', $province->getId() , $this->getDeck('activeWars'));
            $message.=sprintf(_('%s is killed %s and %s becomes an active war') , $senator->getName() , ($garrisons>0 ? _(' with all ').$garrisons._(' garrisons, ') : '') , $province->getName() ) ;
            $this->log($message , 'alert') ;
        }
    }
    
    /**
     * 
     * @return boolean Whether or not one or more legion->otherLocation is 'Released' 
     */
    public function areThereReleasedLegions()
    {
        foreach($this->getLegions() as $legion)
        {
            if ($legion->getOtherLocation() == 'Released')
            {
                return TRUE ;
            }
        }
        return FALSE ;
    }
    
    /**
     * Returns the total cost of land bills and a descriptive message
     * @return array ['total'] = (int) , ['message']
     */
    public function getLandBillsTotalCost()
    {
        $result ['total'] = 0 ;
        $result ['message'] = ',' ;
        foreach ($this->getLandBillsTable() as $level=>$details)
        {
            $result ['message'].=sprintf(
                _(' %1$d level %2$s bill%3$s,') ,
                $details['inPlay'] ,
                ($level==1 ? 'I' : ($level==2 ? 'II' : 'III' ) ) ,
                ($level>1 ? 's' : '')
            ) ;
            $result ['total'] += $details['cost']*$details['inPlay'] ;
        }
        $result ['message'] = substr($result ['message'], 0 , -1) ;
        return $result ;
    }

    /**
     * Returns the party whose turn it is to play based on the current phase
     * @return boolean|\Entities\Party
     */
    public function whoseTurn()
    {
        // FORUM phase - whoseTurn depends on the initiative
        if ($this->getPhase()=='Forum')
        {
            // If the current initiative is <= nbPlayers, we don't need to bid. Initiative number X belongs to player number X in the order of play
            if ($this->getInitiative() <= $this->getNumberOfPlayers())
            {
                $currentOrder = $this->getOrderOfPlay() ;
                return $currentOrder[$this->initiative-1] ;
            }
            else
            {
                // This initiative was up for bidding, the winner has the initiative. The winner is the one with initiativeWon===TRUE
                foreach ($this->getParties() as $party)
                {
                    if ($party->getInitiativeWon()===TRUE)
                    {
                        return $party;
                    }
                }
                return FALSE;
            }
        }
        // Other phases - return the party of the HRAO
        else
        {
            return $this->getHRAO()->getLocation()['value'] ;
        }
    }
    

    /**
     * ----------------------------------------------------
     * Forum
     * ----------------------------------------------------
     */

    /**
     * 
     * @param string $type 'number'|'name|
     * @param type $parameter
     * @return boolean success or failure
     */
    public function putEventInPlay($type , $parameter)
    {
        $eventNumber = NULL ;
        if ($type == 'number')
        {
            $eventNumber = (int)$parameter ;
        }
        elseif ($type == 'name')
        {
            foreach ($this->events() as $key=>$eventArray)
            {
                if ($eventArray['name'] == $parameter)
                {
                    $eventNumber = $key ;
                }
            }
        }
        // $type was wrong
        else
        {
            $this->log(_('Error finding event.'), 'error') ;
            return FALSE;
        }
        // $eventNumber couldn't be determined
        if ($eventNumber==NULL)
        {
            $this->log(_('Error retrieving event.'), 'error') ;
            return FALSE;
        }
        else
        {
            // The event is currently in play at maximum level & CANNOT increase
            if ($this->events[$eventNumber]['level'] > 0 && $this->events[$eventNumber]['level'] == $this->events[$eventNumber]['max_level'])
            {
                $this->log(_('Event %1$s is already in play at its maximum level (%2$d).') , 'alert' ,
                    array (
                        $this->events[$eventNumber][( $this->events[$eventNumber]['level'] > 1 ? 'increased_' : '' ).'name'] ,
                        $this->events[$eventNumber]['max_level']
                    )
                );
            }
            else
            // The event is not in play or not at its maximum level, therefore it can increase
            {
                $this->events[$eventNumber]['level']++ ;
                $this->log(_('Event %1$s %2$s') , 'alert' ,
                    array (
                        $this->events[$eventNumber]['name'] ,
                        ($this->events[$eventNumber]['level'] == 1 ?
                            _('is now in play.') :
                            sprintf(
                                _('has its level increased to %1$s (level %2$d)') ,
                                $this->events[$eventNumber]['increased_name'] ,
                                $this->events[$eventNumber]['level']
                            )
                        )
                    )
                );
                // Events with an immediate effect
                $level = $this->events[$eventNumber]['level'] ;
                // TO DO : Implement "Mob" event from the Population speech phase
                switch ($eventNumber)
                {
                    // Epidemic
                    case 167 :
                        $nbOfMortalityChits = $this->rollOneDie(1) ;
                        $this->log(_('Number of mortality chit%1$s : %2$d'), 'log' ,
                            array(
                                ($nbOfMortalityChits>1 ? 's' : ''),
                                $nbOfMortalityChits
                            )
                        ) ;
                        foreach ($this->mortality_chits($nbOfMortalityChits) as $chit)
                        {
                            $this->log(_('Chit drawn : %1$s'), 'log' , array($chit)) ;
                            if ($chit!='NONE' && $chit!='DRAW 2')
                            {
                                $message = $this->killSenator((string)$chit, FALSE , FALSE , FALSE , ($level==1 ? 'domestic' : 'foreign')) ;
                                $this->log($message[0] , $message[1]);
                            }
                        }
                        break ;
                    // Mob Violence
                    case 171 :
                        $roll = $this->rollOneDie(1) ;
                        $nbOfMortalityChits = $this->getUnrest() + ($level==1 ? 0 : $roll ) ;
                        $POPThreshold = $this->getUnrest() + ($level==1 ? 0 : 1 );
                        $this->log (
                            _('The unrest level is %1$d%2$s%3$s, so %4$d mortality chit%5$s are drawn. Senators in Rome with a POP below %6$d will be killed.') ,
                            'alert' ,
                            array (
                                $this->getUnrest() ,
                                ($level>1 ? '+'.$roll : '') ,
                                ($level>1 ? $this->getEvilOmensMessage(1) : '') ,
                                $nbOfMortalityChits ,
                                ($nbOfMortalityChits == 1 ? '' : 's'),
                                $POPThreshold
                            )
                        );
                        foreach ($this->mortality_chits($nbOfMortalityChits) as $chit)
                        {
                            $this->log(_('Chit drawn : %1$s'), 'log' , array($chit)) ;
                            if ($chit!='NONE' && $chit!='DRAW 2')
                            {
                                $message = $this->killSenator((string)$chit, FALSE , FALSE , $POPThreshold) ;
                                $this->log($message[0] , $message[1]);
                            }
                        }
                        break ;
                    // Natural Disaster
                    case 172 :
                        // First : Pay 50T the first time the Event is played
                        if ($level==1)
                        {
                            $this->changeTreasury(-50) ;
                            $this->log(_('Rome must pay 50T.') , 'alert') ;
                        }
                        // Then : Ruin some stuff
                        $roll = $this->rollOneDie(0) ;
                        $ruin = '' ;
                        switch($roll)
                        {
                            case 1:
                            case 2:
                                $ruin = 'MINING' ;
                                break ;
                            case 3:
                            case 4:
                                $ruin = 'HARBOR FEES' ;
                                break ;
                            case 5 :
                                $ruin = 'ARMAMENTS' ;
                                break ;
                            case 6:
                                $ruin = 'SHIP BUILDING' ;
                                break ;
                        }
                        $ruinresult = reset( $this->getFilteredCards( array ( 'name' => $ruin ) )->toArray() ) ;
                        // Card was in the Forum
                        if ($ruinresult->getLocation()['name']=='forum')
                        {
                            $this->log(_('Rolled a %1$d. The %2$s concession was in the forum. It is destroyed and moved to the curia.'), 'log' , array($roll , $ruin)) ;
                            $this->getDeck('forum')->getFirstCardByProperty('id', $ruinresult->getId() , $this->getDeck('curia')) ;
                        }
                        // Card was on a Senator
                        elseif ($ruinresult->getLocation()['type']=='card' && $ruinresult->getLocation()['value']->getIsSenatorOrStatesman())
                        {
                            $this->log(_('Rolled a %1$d. The %2$s concession was controlled by %3$s. It is destroyed and moved to the curia.') , 'log' , array($roll , $ruin , $ruinresult->getLocation()['name'])) ;
                            $ruinresult->getLocation()['value']->getCardsControlled()->getFirstCardByProperty('id', $ruinresult->getId() , $this->getDeck('curia')) ;
                        }
                        // Card was not in play
                        else
                        {
                            $this->log(_('Rolled a %1$d. The %2$s concession was not in play.') , 'log' , array($roll , $ruin)  ) ;
                        }
                }
            }
        }
    }
    
    /**
    * ----------------------------------------------------
    * Senate
    * ----------------------------------------------------
    */

    /**
     * Given a string ('Legion'|'Fleet') returns the cost per unit
     * @param string $type Legion|Fleet
     * @return int
     */
    public function getUnitCost($type)
    {
        switch ($type)
        {
            case 'Legion' :
            case 'Fleet' :
                return 10 * ( 1 + $this->getEventProperty('name' , 'Manpower Shortage') ) ;
            default :
                return 0 ;
        }
    }
    
    /**
     * Returns a complete Fleet status, ready to be used for recruitment
     * 'canBeRecruited' => int  , 'inRome' => int , 'onCards' => array('cardId' => int) , 'cost' => int
     * @return array
     */
    public function getFleetStatus()
    {
        $result = array('canBeRecruited' => 0 , 'inRome' => 0 , 'onCards' => array() , 'cost' => $this->getUnitCost('Fleet')) ;
        foreach($this->getFleets() as $fleet) 
        {
            $result['canBeRecruited'] += ($fleet->canBeRecruited() ? 1 : 0) ;
            $result['inRome'] += ($fleet->inRome() ? 1 : 0) ;
            // If the fleet is on a Card, add 1 to the $fleetsOnCards array with this cardID as key
            if ($fleet->isAway())
            {
                $cardId = $fleet->getLocation()->getId() ;
                if (array_key_exists ( $cardId , $result['onCards'] ))  { $result['onCards'][$cardId]++   ; }
                else                                                    { $result['onCards'][$cardId] = 1 ; }
            }
        }
        return $result ;
    }
    
    public function getLegionsStatus()
    {
        $result = array(
            'regularsCanBeRecruited'    => 0 ,
            'regularsCanBeDisbanded'    => 0 ,
            'regularsInRome'            => 0 ,
            'regularsOnCards'           => array() ,
            'veterans'                  => array() ,
            'cost'                      => $this->getUnitCost('Legion')
        ) ;
        
        // Regular legions : how many in Rome, in the pool, with a commander ->addAttribute('regulars' , X)
        // Veteran legion : for each - its allegiance & if it's in Rome or with a commander (X , Location) where X is the allegiance
        foreach($this->getLegions() as $legion)
        {
            $card = $legion->getCardLocation() ;
            // regular
            if (!$legion->getVeteran())
            {
                $result['regularsCanBeRecruited'] += ($legion->canBeRecruited() ? 1 : 0) ;
                $result['regularsCanBeDisbanded'] += ($legion->canBeDisbanded() ? 1 : 0) ;
                $result['regularsInRome'] += ($legion->isRegularInRome() ? 1 : 0) ;

                if ($card!==NULL)
                {
                    $cardId = $card->getId() ;
                    if (array_key_exists ( $cardId , $result['regularsOnCards'] ))  { $result['regularsOnCards'][$cardId]++   ; }
                    else                                                            { $result['regularsOnCards'][$cardId] = 1 ; }
                }
            }
            // veteran
            else
            {
                $result['veterans'][$legion->getId()] = array (
                    'name' => $legion->getName() ,
                    'loyalTo' => $legion->getLoyalToSenatorID() ,
                    'otherLocation' => $legion->getOtherLocation() ,
                    'cardLocation' => $legion->getCardLocationCardId()
                ) ;
            }
        }
        return $result ;
    }
            
    /**
     * Sets the latest proposal
     * @param \Entities\Proposal $proposal
     */
    public function setNewProposal($proposal)
    {
        $index = $this->proposals->count() + 1 ; 
        $this->proposals->set($index, $proposal) ;
    }

    /**
     * Returns TRUE if there are 3+ active/unprosecuted conflicts
     * @return bool
     */
    public function getDictatorPossible()
    {
        $activeWars=$this->getDeck('activeWars')->getNumberOfCards() + $this->getDeck('unprosecutedWars')->getNumberOfCards();
        /**
         * TO DO : must also check Conflicts with more than 20 strength
         */
        return ($activeWars>=3) ;
    }
    
    /**
     * 
     * @param type $conflict
     * @return \Entities\array('land'
     * @throws \ExceptionReturns the modified land & fleet strength of a conflict based on active matched conflicts and leaders
     * @return array('land' , 'fleet')
     */
    public function getModifiedConflictStrength($conflict)
    {
        if ($conflict->getPreciseType() !== 'Conflict')
        {
            throw new \Exception(_('ERROR - Must be a conflict card.'));
        }
        // Matched Wars in the áctiveWars' or 'unprosecutedWars' decks
        $multiplier=0;
        $matchedConflicts = $this->getFilteredCards(array('matches' => $conflict->getMatches())) ;
        foreach ($matchedConflicts as $matchedConflict)
        {
            $location = $matchedConflict->getLocation() ;
            if ($location['type']==game && ($location['name'] == 'activeWars' || $location['name'] == 'unprosecutedWars'))
            {
                $multiplier++ ;
            }
        }
        $result = array('land' => $conflict->getLand()*$multiplier , 'fleet' => $conflict->getFleet()*$multiplier) ;
        // Leaders on the card
        foreach ($conflict->getCardsControlled() as $card)
        {
            if ($card->getPreciseType()=='Leader')
            {
                $result['land'] = $result['land'] + $card->getStrength() ;
                $result['fleet'] = $result['fleet'] + $card->getStrength() ;
            }
        }
        return $result ;
    }
    
    /**
     * @return 'Major'|'Minor'|'none' 
     */
    public function getWhichProsecutionPossible()
    {
        $nbOfProsecutions = 0 ;
        foreach ($this->getProposals() as $proposal)
        {
            if ($proposal->getType()=='Prosecutions')
            {
                $nbOfProsecutions++ ;
                if ($proposal->getContent()['Type']=='Major')
                {
                    return 'none' ;
                }
            }
        }
        if ($nbOfProsecutions==0)
        {
            return 'Major' ;
        }
        return (($nbOfProsecutions==1) ? 'Minor' : 'none') ;
    }
    
    /**
     * List of available Province Cards for Governors proposals
     * @return array of elements that are array('description', 'recall', 'cardID')
     */
    public function getListOfAvailableProvinces()
    {
        $result=[] ;
        foreach ($this->getFilteredCards(array('preciseType' => 'Province' , 'isProvinceInPlay' => TRUE)) as $province)
        {
            // If the card is not in the Forum, this is a recall
            $recall = $province->getDeck()->getName() !== 'Forum' ;
            $result[] = array (
                'description' => $province->getName().($recall ? sprintf(_(' (recall of %1$s)') , $province->getDeck()->getControlled_by()->getName()) : '') ,
                'recall' => $recall ,
                'cardID' => $province->getId()
            ) ;
        }
        return $result ;
    }
}