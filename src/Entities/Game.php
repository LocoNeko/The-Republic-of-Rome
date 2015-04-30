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
    public static $VALID_VARIANTS = array('Pontifex Maximus' , 'Provincial Wars' , 'Rebel governors' , 'Legionary disbandment' , 'Advocates' , 'Passing Laws') ;
    public static $VALID_DECKS = array('drawDeck' , 'earlyRepublic' , 'middleRepublic' , 'lateRepublic' , 'discard' , 'unplayedProvinces' , 'inactiveWars' , 'activeWars' , 'imminentWars' , 'unprosecutedWars' , 'forum' , 'curia') ;
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

    /**
     * @param string $scenario
     * @throws Exception
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
     * @throws Exception
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

    public function __construct()
    {
        $createDate = new \DateTime('NOW') ;
        $this->localised = true;
        $this->created = $createDate;
        $this->timezone = $createDate->getTimeZone()->getName();
        $this->parties = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->legions = new ArrayCollection();
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
    public function getParties() { return $this->parties ; }
    public function getDecks() { return $this->decks ; }
    public function getLegions() { return $this->legions; }
    public function getMessages() { return $this->messages; }
    public function getTimezone() { return $this->timezone ; }
    public function getCurrentBidder() { return $this->currentBidder; }
    public function getPersuasionTarget() { return $this->persuasionTarget; }

    public function getCreated()
    {
        if ($this->timezone==NULL) { $this->timezone = 'UTC' ; }
        if (!$this->localised) { $this->created->setTimeZone(new \DateTimeZone($this->timezone)); }
        return $this->created;
    }
    
    public function saveData()
    {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['created'] = $this->getCreated() ;
        $data['timezone'] = $this->timezone ;
        $data['localised'] = $this->localised;
        $data['name'] = $this->getName() ;
        $data['turn'] = $this->getTurn() ;
        $data['phase'] = $this->getPhase() ;
        $data['subPhase'] = $this->getSubPhase() ;
        $data['initiative'] = $this->getInitiative() ;
        $data['censorIsDone'] = $this->getCensorIsDone() ;
        $data['senateAdjourned'] = $this->getSenateAdjourned() ;
        $data['scenario'] = $this->getScenario() ;
        $data['variants'] = $this->getVariants() ;
        $data['unrest'] = $this->getUnrest() ;
        $data['treasury'] = $this->getTreasury() ;
        $data['parties'] = array() ;
        foreach($this->getParties() as $key=>$party)
        {
            $data['parties'][$key] = $party->saveData() ;
        }
        $data['decks'] = array() ;
        foreach($this->getDecks() as $deck)
        {
            array_push($data['decks'] , $deck->saveData()) ;
        }
        $data['messages'] = array() ;
        foreach($this->getMessages() as $message)
        {
            array_push($data['messages'] , $message->saveData()) ;
        }
        $data['currentBidder_id'] = ($this->getCurrentBidder() === NULL ? NULL : $this->getCurrentBidder()->getId() ) ; // NOTE : This is a Party id
        $data['persuasionTarget_id'] = ($this->getPersuasionTarget() === NULL ? NULL : $this->getPersuasionTarget()->getId() ) ; // NOTE : This is a Card id
        return $data ;
    }

    /**
     * ----------------------------------------------------
     * Functions
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

    public function nextInitiative()
    {
        $this->initiative++;
    }

    public function resetAllIsDone()
    {
        foreach($this->getParties() as $party)
        {
            $party->setIsDone(FALSE) ;
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

    public function addVariant($variant)
    {
        if (in_array($variant, self::$VALID_VARIANTS))
        {
            if (!in_array($variant, $this->variants))
            {
                $this->variants[] = $variant ;
            }
            else
            {
                throw new \Exception(_('Variant already included'));
            }
        }
        else
        {
            throw new \Exception(sprintf(_('Invalid variant %1$s') , $variant));
        }
    }

    public function removeVariant($variant)
    {
        $key = array_search($variant, $this->variants) ;
        if ($key !== FALSE)
        {
            unset($this->variants[$key]);
        }
        else
        {
            throw new \Exception(sprintf(_('Variant %1$s was not found.') , $variant));
        }
    }

    public function changeUnrest($value)
    {
        $this->unrest+=(int)$value ;
    }

    public function changeTreasury($amount)
    {
        $this->treasury+=(int)$amount ;
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
        elseif (count($parties) == $this::$MAX_PLAYERS)
        {
            $result = 'FULL' ;
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
        return $result ;
    }
    
    public function setPartyToReady($user_id)
    {
        foreach($this->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id)
            {
                $party->setReadyToStart() ;
                if ($this->gameStarted())
                {
                    $this->doSetup() ;
                }
                return TRUE ;
            }
        }
        return FALSE ;
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
     * Looks for a file in resources/scenarios and creates a Deck based on it, by creating Cards as long as they have a valid type
     * @param string $fileName
     * @param Deck $deck
     * @throws Exception When couldn't open file
     */
    public function populateDeckFromFile($fileName , $deck)
    {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/scenarios/'.$fileName.'.csv', 'r');
        if (!$filePointer)
        {
            throw new Exception(_('Could not open the file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE)
        {
            if ($data[0]!='')
            {
                $type = $data[2] ;
                if (\Entities\Card::isValidType($type))
                {
                    $class = __NAMESPACE__.'\\'.$type ;
                    $card = new $class ($data);
                    $deck->putCardOnTop($card) ;
                }
            }
        }
        fclose($filePointer);
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
                $party->setLastUpdate(new \DateTime('NOW') ) ;
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
     * @return Party
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
     * @return array of [$user_id] => 'party name [user name]'
     */
    public function getPartiesNames()
    {
        $result=array() ;
        foreach($this->getParties() as $party)
        {
            $result[$party->getUser_id()] = $party->getFullName() ;
        }
        return $result ;
    }

    /**
     * Replaces all instances of [[X]] by the name of party X or 'you' if party X is played by $user_id
     * @param string $input
     * @param int $user_id
     * @return string
     */
    public function displayContextualName($input , $user_id)
    {
        $output = $input ;
        foreach($this->getParties() as $party) 
        {
            $party_userId = $party->getUser_id() ;
            if (strpos($output, '[['.$party_userId.']]') !==FALSE) 
            {
                $output = str_replace('[['.$party_userId.']]' , ( ($party_userId==$user_id) ? 'you' : $party->getUserName() ) , $output);
            }
        }
        return $output ;
    }
    
    /**
     * Goes through all decks in the game and returns an ArrayCollection of Senator Entitites satisfying an optional criteria (or all of them if no criteria)
     * @param string $criteria
     * @return ArrayCollection
     * @throws Exception 'Error retrieving senators'
     */
    public function getAllSenators($criteria = TRUE)
    {

        $result = new ArrayCollection() ;
        try
        {
            // Senators in Parties
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
                            if($card->getPreciseType()=='Senator') 
                            {
                                $result->add($card) ;
                            }
                        }
                    }

                }
            
                // Statesmen in Hand
                foreach($party->getHand()->getCards() as $card) 
                {
                    if($card->getPreciseType()=='Statesman') 
                    {
                        $result->add($card) ;
                    }
                }
            }
            
            // Game's main decks
            foreach ($this->getDecks() as $deck) 
            {
                foreach($deck->getCards() as $card) 
                {
                    if (in_array($card->getPreciseType() , array('Senator' , 'Statesman'))) 
                    {
                        $result->add($card) ;
                    }
                }
            }
            
            // Filters the results based on criteria
            $result = $result->filter(
                function ($card) use ($criteria) {
                    return $card->checkCriteria($criteria) ;
                }
            );
        }
        catch (Exception $e) 
        {
             throw new \Exception(_('Error retrieving senators'));
        }
        return $result ;
    }
    
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
            // If we are looking for the presiding magistrate, The Censor must be returned during the Senate phase if the latest proposal was a prosecution
            // TO DO : what if the all thing was interupted by a Special Assassin Prosecution ?
            if ( $presiding && $this->getPhase()=='Senate' && count($this->getProposals())>0 && end($this->getProposals()->last())->getType()=='Prosecutions' && isset($rankedSenators[3]) ) 
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
        $allSenators = $this->getAllSenators()->toArray() ;
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
        return $allSenators[0] ;
    }
    
    /**
     * 
     * @param int $user_id The user_id of the player playing the Statesman
     * @param string $statesmanId The card id of the Statesman being played
     * @return boolean Sucess or Failure
     */
    public function playStatesman($user_id , $statesmanId) 
    {
        $party=$this->getParty($user_id) ;
        $statesman = $this->getParty($user_id)->getHand()->getFirstCardByProperty('id', $statesmanId) ;
        if ($statesman->getPreciseType()!=='Statesman') 
        {
            $this->log(sprintf(_('ERROR - %1$s is not a Statesman') , 'error' , array($statesman->getName()))) ;
            return FALSE ;
        }
        $location = $statesman->getLocation() ;
        if ($location['type']!=='hand' || $location['value']->getUser_id()!=$party->getUser_id()) 
        {
            $this->log(sprintf(_('ERROR - %1$s is not in [['.$party->getUser_id().']]\'s hand') , 'error', array($statesman->getName()) )) ;
            return FALSE ;
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
                $familyMessage=_(' He has the Family and puts it under the Statesman.');
                
            // The Family was found in the forum - Play the Statesman and make him control the Family
            } elseif (($familyLocation['type']=='game') && ($familyLocation['name']=='forum') ) 
            {
                $this->getDeck('forum')->getFirstCardByProperty('id', $family->getId() , $statesman->getCardsControlled()) ;
                $familyMessage=_(' He takes the Family from the forum and puts it under the Statesman.');

            }
        }
        $this->log(_('[['.$user_id.']]'.' {play,plays} Statesman %1$s.'.$familyMessage) , 'log' , array($statesman->getName()));
        return $statesman ;
    }

    /**
     * ----------------------------------------------------
     * Setup
     * ----------------------------------------------------
     */

    public function doSetup()
    {
        $this->setPhase('Setup') ;
        $this->nextTurn() ;

        // Create all tables : Events, Appeal, Land bill, Population
        $this->createEvents() ;
        $this->createAppealTable() ;
        $this->createLandBillsTable() ;
        $this->createPopulationTable() ;
        
        // Early Republic deck
        $earlyRepublicDeck = $this->getDeck('earlyRepublic') ;
        $this->populateDeckFromFile($this->getScenario() , $earlyRepublicDeck) ;

        // Unplayed provinces deck
        $provinceDeck = $this->getDeck('unplayedProvinces') ;
        $this->populateDeckFromFile('Provinces' , $provinceDeck) ;
        
        // Handle special cards : The First Punic war & Era ends
        $this->log(_('The First Punic War goes to the "Inactive" Wars deck.') , 'alert' ) ;
        $earlyRepublicDeck->getFirstCardByProperty('id' , 1 , $this->getDeck('inactiveWars')) ;
        $this->log(_('The "Era Ends" card goes to the discard. (MUST FIX)') , 'error' ) ;
        $earlyRepublicDeck->getFirstCardByProperty('id' , 65 , $this->getDeck('discard')) ;

        /*
         * TO DO : Fleets
         */
        // Then create 4 legions in Rome, the rest of the legions and all the fleets are non-existent (Legions and Fleet objects should never be created during a game)
        for($i=1;$i<=25;$i++) 
        {
            $legion = new \Entities\Legion($this,$i) ;
            $this->getLegions()->add($legion) ;
            if ($i<=4) 
            {
                $legion->setOtherLocation('Rome') ;
            }
        }
        $this->log(_('Rome starts with 4 regular Legions.') ) ;
        
        // Give initial senators & cards to parties
        foreach ($this->getParties() as $party)
        {
            
            // Senators
            $senatorsList = '' ;
            for ($i=1 ; $i<=3 ; $i++)
            {
                $earlyRepublicDeck->shuffle() ;
                $card = $earlyRepublicDeck->getFirstCardByProperty('preciseType' , 'Senator' , $party->getSenators()) ;
                $senatorsList.=$card->getName() ;
                switch($i) {
                    case 1  : $senatorsList.= ' , '   ; break ;
                    case 2  : $senatorsList.= ' and ' ; break ;
                    default : $senatorsList.= '.'     ;
                }
            }
            $this->log(_('[['.$party->getUser_id().']] {receive,receives} the following Senators : %1$s') , 'log' , array($senatorsList) ) ;
            
            //Cards
            $cardsList = '' ;
            $cardsLeftToDraw = 3 ;
            while ($cardsLeftToDraw>0)
            {
                $earlyRepublicDeck->shuffle() ;
                $card = $earlyRepublicDeck->drawFirstCard() ;
                switch ($card->getPreciseType())
            {
                    case 'Faction card' :
                    case 'Statesman' :
                    case 'Concession' :
                        $party->getHand()->putCardOnTop($card);
                        $cardsList.= $card->getName().' , ' ;
                        $cardsLeftToDraw--;
                        break ;
                    default :
                        $earlyRepublicDeck->putCardOnTop($card);
                }
            }
            $this->log(_('[['.$party->getUser_id().']] receives three cards') , 'log' , NULL , $this->getAllPartiesButOne($party->getUser_id()) ) ;
            $this->log(_('You receive the following cards in hand : %1$s') , 'log' , array($cardsList) , new ArrayCollection(array($party)) ) ;
        }
        // Temporary Rome Consul
        try
        {
            $alignedSenators = $this->getAllSenators('alignedInRome') ;
            $temporaryRomeConsul = $alignedSenators[rand(0 , count($alignedSenators)-1)] ;
            $temporaryRomeConsul->appoint('Rome Consul') ;
            $temporaryRomeConsul->setPriorConsul(TRUE) ;
            $this->log(_('%1$s is appointed temporary Rome Consul') , 'log' , array($temporaryRomeConsul->getName())) ;
        }
        catch (Exception $e)
        {
            $result[0]->log($e->getMessage() , 'error') ;
        }
        $this->setSubPhase('Pick leaders') ;
    }
    
    /*
     * Convenience function (could be inside doSetup)
     * The event file should have 4 columns :
     * Event number (should be VG card number) ; event name ; increased event name ; description ; increased event description ; maximum level of the event (0 if none)
     * The event table file should have 3 columns :
     * event number for Early Republic ; Middle Republic ; Late Republic 
     */
    public function createEvents() {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/events.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the events file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            $this->events[(int)$data[0]] = array( 'name' => $data[1] , 'increased_name' => $data[2] , 'description' => $data[3] , 'increased_description' => $data[4] , 'max_level' => $data[5] , 'level' => 0);
        }
        fclose($filePointer);
        $filePointer2 = fopen(dirname(__FILE__).'/../../resources/tables/eventTable.csv', 'r');
        if (!$filePointer2) {
            throw new Exception(_('Could not open the event table file'));
        }
        $i=3;
        while (($data = fgetcsv($filePointer2, 0, ";")) !== FALSE) {
            $this->eventTable[$i]['EarlyRepublic'] = $data[0] ;
            $this->eventTable[$i]['MiddleRepublic'] = $data[1] ;
            $this->eventTable[$i]['LateRepublic'] = $data[2] ;
            $i++;
        }
        fclose($filePointer2);
    }

    /**
     * Reads the appealTable csv file and creates an array appealTable : keys = roll , values = array ('votes' => +/- votes , 'special' => NULL|'killed'|'freed' )
     * @throws Exception
     */
    public function createAppealTable() {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/appealTable.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the Appeal table file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            $this->appealTable[$data[0]] = array('votes' => $data[1] , 'special' => (isset($data[2]) ? $data[2] : NULL));
        }
        fclose($filePointer);
    }

    /**
     * Reads the landBills csv file and creates the landBillsTable array
     * @throws Exception
     */
    public function createLandBillsTable() {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/landBills.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the Land Bills table file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            if (substr($data[0],0,1)!='#') {
                $this->landBillsTable[$data[0]] = array();
                array_push($this->landBillsTable[$data[0]] , array(
                        'cost' => $data[1] ,
                        'duration' => $data[2] ,
                        'sponsor' => $data[3] ,
                        'cosponsor' => $data[4] ,
                        'against' => $data[5] ,
                        'unrest' => $data[6] ,
                        'repeal sponsor' => $data[7] ,
                        'repeal vote' => $data[8] ,
                        'repeal unrest' => $data[9]
                    )
                );
            }
        }
        fclose($filePointer);
    }

    /**
     * Reads the populationTable csv file and creates an array Unrest level => array of effects
     * Effects are : +# increase unrest by # , -# decrease unrest by # , MS manpower shortage , NR no recruitment , Mob
     * @throws Exception
     */
    public function createPopulationTable() {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/tables/populationTable.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the Population table file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            $this->populationTable[$data[0]] = array();
            $effects = explode(',', $data[1]);
            foreach($effects as $effect) {
                array_push($this->populationTable[$data[0]] , $effect);
            }
        }
        fclose($filePointer);
    }

    /**
     * ----------------------------------------------------
     * Mortality
     * ----------------------------------------------------
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
     * @param POPThreshold FALSE or equal to the level of POP at which a Senator is safe
     * @param epidemic FALSE or equal to either 'domestic' or 'foreign'
     * senators from other parties will not be killed.<br>
     * FALSE (default)
     * @return array Just a one message-array, not an array of messages
     */
    public function killSenator($senatorID , $specificID=FALSE , $specificParty_UserId=FALSE , $POPThreshold=FALSE , $epidemic=FALSE) {
        $message = '' ;
        
        // Case of a random mortality chit
        if (!$specificID)
        {
            // Creates an array of potentially dead senators, to handle both Statesmen & Families
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
                        )
                        {
                            if ( ($senator->getPreciseType() == 'Statesman') && ($senator->statesmanFamily() == $senatorID ) )
                            {
                                array_push($deadSenators , $senator) ;
                            }
                            elseif ( ($senator->getPreciseType() == 'Senator') && ($senator->getSenatorID() == $senatorID) )
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
                return array(_('This senator is not in Play, nobody dies.')) ;
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

            // Death of a Statesman
            if ($deadSenator->getPreciseType() == 'Statesman')
            {
                $deadStatesman = $party->getSenators()->getFirstCardByProperty('senatorID' , $deadSenator->getSenatorID() , $this->getDeck('Discard')) ;
                $deadStatesman->resetSenator();
                $message.=sprintf(_('%s of party {%s} dies. The card is discarded. ') , $deadStatesman->getName() , $party->getUser_id()) ;
            }
            
            // Death of a normal Senator
            else
            {
                $deadSenator->resetSenator() ;
                if ($party->getLeader()->getSenatorID() == $senatorID)
                {
                    $message.=sprintf(_('%s of party {%s} dies. This senator was party leader, the family stays in the party. ') , $deadSenator->getName() , $party->getUser_id());
                }
                else
                {
                    $deadSenator = $party->getSenators()->getFirstCardByProperty('senatorID' , $senatorID , $this->getDeck('Curia')) ;
                    $message.=sprintf(_('%s of party {%s} dies. The family goes to the curia. ') , $deadSenator->getName() , $party->getUser_id());
                }
            }
            
            // Handle dead senators' controlled cards : Concessions, Provinces, Senators
            if ($deadSenator->hasControlledCards())
            {
                foreach($deadSenator->getControlledCards()->getCards() as $card)
                {
                    
                    // Concession -> Curia
                    if ($card->getPreciseType()=='Concession')
                    {
                        $deadSenator->getControlledCards()->getFirstCardByProperty('id' , $card->getId() , $this->getDeck('curia')) ;
                        $message.=sprintf(_('%s goes to the curia. ') , $card->getName());
                    }
                    
                    // Province -> Forum
                    elseif ($card->getPreciseType()=='Province')
                    {
                        $deadSenator->getControlledCards()->getFirstCardByProperty('id' , $card->getId() , $this->getDeck('forum')) ;
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
                            $deadSenator->getControlledCards()->getFirstCardByProperty('id' , $card->getId() , $party->getSenators()) ;
                            $message.=sprintf(_('%s stays in the party and is now leader. ') , $card->getName());
                        }
                        
                        // Was not leader -> Curia
                        else
                        {
                            $deadSenator->getControlledCards()->getFirstCardByProperty('id' , $card->getId() , $this->getDeck('curia')) ;
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
        return array($message) ;
    }


}