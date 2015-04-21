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
    private $leader ;

    /** @Column(type="integer") @var int */
    protected $treasury = 0 ;
    
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
    public function setLeader($leader) { $this->leader = $leader; }
    public function setTreasury($treasury) { $this->treasury = $treasury; }

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
        $data['messages'] = array() ;
        foreach($this->getMessages() as $message) {
            array_push($data['messages'] , $message->saveData()) ;
        }
        return $data ;
    }

    /**
    * ----------------------------------------------------
    * Other methods
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
}
