<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
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
    /** @OneToMany(targetEntity="Party", mappedBy="game") **/
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
    private $localized = false;
    
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
    public function setSubPhase($subPhase) { $this->subPhase = $subPhase ; }
    public function setCensorIsDone($flag) { $this->censorIsDone = $flag ; }
    public function setSenateAdjourned($flag) { $this->senateAdjourned = $flag ; }
    public function setUnrest($unrest) { $this->unrest = $unrest ; }
    public function setTreasury($treasury) { $this->treasury = $treasury ; }
    public function setInitiative($i) { $this->initiative = $i ; }
    private function setCreated($created) { $this->created = $created ; }
    public function setCurrentBidder($currentBidder) { $this->currentBidder = $currentBidder; }
    public function setPersuasionTarget($persuasionTarget) { $this->persuasionTarget = $persuasionTarget; }

 
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
        } else {
            throw new \Exception(_('Invalid phase'));
        }
    }

    public function __construct() {
        $createDate = new \DateTime('NOW') ;
        $this->localized = true;
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
        if (!$this->localized) { $this->created->setTimeZone(new \DateTimeZone($this->timezone)); }
        return $this->created;
    }
    
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['created'] = $this->getCreated() ;
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
        $data['currentBidder'] = $this->getCurrentBidder() ;
        $data['persuasionTarget'] = $this->getPersuasionTarget() ;
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
        return $this->parties->exists(
            function($key, Party $element) use ($user_id) {
                return $element->getUser_id() == $user_id ;
            }
        );
    }

    /**
     * Checks if a party with this name already exists
     * @param string $name
     * @return boolean 
     */
    public function partyAlreadyExists($name) {
        return $this->parties->exists(
            function($key, Party $element) use ($name) {
                return $element->getName() == $name ;
            }
        );
    }
    
    public function getNumberOfPlayers() {
        return count($this->parties) ;
    }
    
    public function getDeck($deckName) {
        foreach ($this->getDecks() as $deck) {
            if ($deck->getName() == $deckName) {
                return $deck ;
            }
        }
        return FALSE ;
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
    
    public function populateDeckFromFile($entityManager , $fileName , $deck) {
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
                    $entityManager->persist($card) ;
                    $deck->putCardOnTop($card) ;
                }
            }
        }
        fclose($filePointer);
        $entityManager->persist($deck) ;
    }
    
    public function log($text , $type='log' , $parameters=NULL , $recipients=NULL , $from=NULL) {
        try {
            $message = new \Entities\Message($this, $text, $type, $parameters, $recipients, $from) ;
            $this->getMessages()->add($message) ;
        } catch (Exception $e) {
            throw new \Exception($e->getMessage()) ;
        }
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
                return $messages ;
            }
        }
        return FALSE ;
    }
}