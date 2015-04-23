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
            $this->log(_('Phase : %1$s') , 'alert' , array($phase) ) ;
        } else {
            throw new \Exception(_('Invalid phase'));
        }
    }

    public function setSubPhase($subPhase) {
        $this->subPhase = $subPhase ;
        $this->log(_('Sub Phase : %1$s.') , 'alert' , array($subPhase) ) ;
    }

    public function __construct() {
        $createDate = new \DateTime('NOW') ;
        $this->localised = true;
        $this->created = $createDate;
        $this->timezone = $createDate->getTimeZone()->getName();
        $this->parties = new ArrayCollection();
        $this->decks = new ArrayCollection();
        $this->messages = new ArrayCollection();
        foreach (self::$VALID_DECKS as $deckName) {
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
    
    public function saveData() {
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
        foreach($this->getParties() as $key=>$party) {
            $data['parties'][$key] = $party->saveData() ;
        }
        $data['decks'] = array() ;
        foreach($this->getDecks() as $deck) {
            array_push($data['decks'] , $deck->saveData()) ;
        }
        $data['messages'] = array() ;
        foreach($this->getMessages() as $message) {
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
    
    public static function getAllVariants() {
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

    public function addVariant($variant)
    {
        if (in_array($variant, self::$VALID_VARIANTS)) {
            if (!in_array($variant, $this->variants)) {
                $this->variants[] = $variant ;
            } else {
                throw new \Exception(_('Variant already included'));
            }
        } else {
            throw new \Exception(sprintf(_('Invalid variant %1$s') , $variant));
        }
    }

    public function removeVariant($variant)
    {
        $key = array_search($variant, $this->variants) ;
        if ($key !== FALSE)
        {
            unset($this->variants[$key]);
        } else {
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
    public function userAlreadyJoined($user_id) {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->eq('user_id', (int)$user_id)) );
        return ( count($results)==1 ) ;
    }

    /**
     * Checks if a party with this name already exists
     * @param string $name
     * @return boolean 
     */
    public function partyAlreadyExists($name) {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->eq('name', $name)) );
        return ( count($results)==1 ) ;
    }
    
    public function getNumberOfPlayers() {
        return count($this->parties) ;
    }
    
    public function getDeck($deckName) {
        $results = $this->getDecks()->matching( Criteria::create()->where(Criteria::expr()->eq('name', $deckName)) );
        if (count($results)==1) {
            return $results->first() ;
        } else {
            return FALSE ;
        }
    }
    
    /**
     * Returns the current state of the game for a given user_id
     * @param int $user_id
     * @return string JOINED|CAN_JOIN|STARTED|READY|FULL
     */
    public function getGameState($user_id) {
        $parties = $this->getParties() ;
        $result = $this->userAlreadyJoined($user_id) ? 'JOINED' : 'CAN_JOIN' ;
        if ($this->gameStarted()) {
            $result ='STARTED' ;
        } elseif (count($parties) == $this::$MAX_PLAYERS) {
            $result = 'FULL' ;
        } elseif($result == 'JOINED') {
            foreach($parties as $party) {
                if ($party->getUser_id() == $user_id && $party->getReadyToStart()) {
                    $result = 'READY' ;
                }
            }
        }
        return $result ;
    }
    
    public function setPartyToReady($user_id) {
        foreach($this->getParties() as $party) {
            if ($party->getUser_id() == $user_id) {
                $party->setReadyToStart() ;
                if ($this->gameStarted()) {
                    $this->doSetup() ;
                }
                return TRUE ;
            }
        }
        return FALSE ;
    }

    public function gameStarted() {
        if ($this->getNumberOfPlayers()<self::$MIN_PLAYERS) {
                return FALSE ;
        }
        foreach($this->getParties() as $party) {
            if (!$party->getReadyToStart()) {
                return FALSE ;
            }
        }
        return TRUE ;
    }
    
    public function doSetup() {
        $this->setPhase('Setup') ;

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
         * TO DO
         */
        // Then create 4 legions in Rome, the rest of the legions and all the fleets are non-existent (Legions and Fleet objects should never be created during a game)
        // 
        // Give initial senators & cards to parties
        foreach ($this->getParties() as $party) {
            
            // Senators
            $senatorsList = '' ;
            for ($i=1 ; $i<=3 ; $i++) {
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
            while ($cardsLeftToDraw>0) {
                $earlyRepublicDeck->shuffle() ;
                $card = $earlyRepublicDeck->drawFirstCard() ;
                switch ($card->getPreciseType()) {
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
        try {
            $alignedSenators = $this->getAllSenators('alignedInRome') ;
            $temporaryRomeConsul = $alignedSenators[rand(0 , count($alignedSenators)-1)] ;
            $temporaryRomeConsul->appoint('Rome Consul') ;
            $temporaryRomeConsul->setPriorConsul(TRUE) ;
            $this->log(_('%1$s is appointed temporary Rome Consul') , 'log' , array($temporaryRomeConsul->getName())) ;
        } catch (Exception $e) {
            $result[0]->log($e->getMessage() , 'error') ;
        }
        $this->setSubPhase('Pick leaders') ;
    }
    
    public function populateDeckFromFile($fileName , $deck) {
        $filePointer = fopen(dirname(__FILE__).'/../../resources/scenarios/'.$fileName.'.csv', 'r');
        if (!$filePointer) {
            throw new Exception(_('Could not open the file'));
        }
        while (($data = fgetcsv($filePointer, 0, ";")) !== FALSE) {
            if ($data[0]!='') {
                $type = $data[2] ;
                if (\Entities\Card::isValidType($type)) {
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
    public function log($text , $type='log' , $parameters=NULL , $recipients=NULL , $from=NULL) {
        try {
            $message = new \Entities\Message($this, $text, $type, $parameters, $recipients, $from) ;
        } catch (Exception $e) {
            $message = new \Entities\Message($this, 'ERROR creating message', 'error') ;
        }
        $this->getMessages()->add($message) ;
        return $message ;
    }
    
    public function getNewMessages ($user_id) {
        foreach($this->getParties() as $party) {
            if ($party->getUser_id()==$user_id) {
                $messages = array() ;
                foreach ($this->getMessages() as $message) {
                    if ($message->getTime() > $party->getLastUpdate()) {
                        if ( $message->getRecipients()===NULL || count($message->getRecipients()) == 0 || $message->isRecipient($user_id)) {
                            array_push($messages , $message) ;
                        }
                    }
                }
                $party->setLastUpdate(new \DateTime('NOW') ) ;
                return $messages ;
            }
        }
        return FALSE ;
    }
    
    public function getAllMessages ($user_id) {
        $messages = array() ;
        foreach($this->getParties() as $party) {
            if ($party->getUser_id()==$user_id) {
                foreach (array_reverse($this->getMessages()->toArray()) as $message) {
                    if ( $message->getRecipients()===NULL || count($message->getRecipients()) == 0 || $message->isRecipient($user_id)) {
                        array_push($messages , $message) ;
                    }
                }
            }
        }
        return $messages ;
    }
    
    public function getParty ($user_id) {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->eq('user_id', (int)$user_id)) );
        return $results->first() ;
    }
    
    public function getAllPartiesButOne ($user_id) {
        $results = $this->getParties()->matching( Criteria::create()->where(Criteria::expr()->neq('user_id', (int)$user_id)) );
        return $results ;
    }
    
    /**
     * 
     * @return array of [$user_id] => 'party name [user name]'
     */
    public function getPartiesNames() {
        $result=array() ;
        foreach($this->getParties() as $party) {
            $result[$party->getUser_id()] = $party->getFullName() ;
        }
        return $result ;
    }
    
    /**
     * Goes through all decks in the game and returns an ArrayCollection of Senator Entitites satisfying an optional criteria (or all of them if no criteria)
     * @param string $criteria
     * @return ArrayCollection
     * @throws Exception 'Error retrieving senators'
     */
    public function getAllSenators($criteria = TRUE) {

        $result = new ArrayCollection() ;
        try {
            // Senators in Parties
            foreach ($this->getParties() as $party) {
                foreach($party->getSenators()->getCards() as $senator) {
                    if ($senator->checkCriteria($criteria)) {
                        $result->add($senator) ;
                    }
                    // Senators cards controlled by their Statesman
                    if ($senator->getCardsControlled()!=NULL) {
                        foreach ($senator->getCardsControlled()->getCards() as $card) {
                            if($card->getPreciseType()=='Senator') {
                                if ($card->checkCriteria($criteria)) {
                                    $result->add($card) ;
                                }
                            }
                        }
                    }
                }
                // Statesmen in Hand
                foreach($party->getHand()->getCards() as $card) {
                    if($card->getPreciseType()=='Statesman') {
                        if ($card->checkCriteria($criteria)) {
                            $result->add($card) ;
                        }
                    }
                }
            }
            // Game's main decks
            foreach ($this->getDecks() as $deck) {
                foreach($deck->getCards() as $card) {
                    if (in_array($card->getPreciseType() , array('Senator' , 'Statesman'))) {
                        if ($card->checkCriteria($criteria)) {
                            $result->add($card) ;
                        }
                    }
                }
            }
        } catch (Exception $e) {
             throw new \Exception(_('Error retrieving senators'));
        }
        return $result ;
    }
}