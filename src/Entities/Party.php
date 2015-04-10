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

    /** @OneToOne(targetEntity="Deck" , cascade={"persist"}) @JoinColumn(name="hand_deck_id", referencedColumnName="id") **/
    private $hand ;

    /** @OneToOne(targetEntity="Deck" , cascade={"persist"}) @JoinColumn(name="senators_deck_id", referencedColumnName="id") **/
    private $senators ;
    
    /** @ManyToMany(targetEntity="Message", mappedBy="recipients" ) **/
    protected $messages ;

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

     public function __construct($user_id , $userName , $name) {
        $this->setName($name) ;
        $this->setUser_id($user_id) ;
        $this->setUserName($userName) ;
        $this->messages = new ArrayCollection();
        $this->hand = new \Entities\Deck($this->getName().' - Cards in hand') ;
        $this->senators = new \Entities\Deck($this->getName().' - Senators') ;
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
            $game->log(_('%1$s joins the game as "%2$s"') , 'log' , array($this->getUserName() , $this->getName()) ) ;
        }
    }

}
