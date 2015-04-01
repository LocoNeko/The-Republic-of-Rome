<?php
namespace Entities ;
/**
 * @Entity @Table(name="parties")
 **/
class Party
{
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $id;
    /**
     * @ManyToOne(targetEntity="Game", inversedBy="parties")
     **/
    private $game ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $name ;
    /**
    * @Column(type="boolean")
    * @var int
    */
    protected $readyToStart = FALSE ;
    /**
    * @Column(type="integer")
    * @var int
    */
    protected $user_id ;

    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
    public function getName()
    {
        return $this->name ;
    }

    public function setName($name)
    {
        $this->name = $name ;
    }

    public function getGame()
    {
        return $this->game ;
    }

    public function setGame($game)
    {
        $this->game = $game ;
    }

    public function getReadyToStart()
    {
        return $this->readyToStart ;
    }

    public function setReadyToStart()
    {
        $this->readyToStart = TRUE ;
    }
    
    public function getUser_id()
    {
        return $this->user_id ;
    }

    public function setUser_id($user_id)
    {
        $this->user_id = $user_id ;
    }

    public function __construct($user_id , $name) {
        $this->setName($name) ;
        $this->setUser_id($user_id) ;
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
        }
    }

}
