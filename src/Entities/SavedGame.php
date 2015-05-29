<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="savedGames")
 **/
class SavedGame
{
    public static $VALID_PHASES = array('Setup','Mortality','Revenue','Forum','Population','Senate','Combat','Revolution','Rome falls') ;

    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $savedGameId ;

    /** @Column(type="integer") @var int */
    protected $game_id ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    /** @Column(type="integer") @var int */
    protected $turn ;
    
    /** @Column(type="string") @var string */
    protected $phase ;
    
    /** @Column(type="string") @var string */
    protected $subPhase ;

    /** @Column(type="datetime")  */
    private $savedTime;

    /** @Column(type="blob") @var resource */
    protected $gameData ;

    public function setGame_id($game_id) { $this->game_id = $game_id; }
    public function setName($name) { $this->name = $name; }
    public function setTurn($turn) { $this->turn = $turn; }
    public function setPhase($phase) { $this->phase = $phase; }
    public function setSubPhase($subPhase) { $this->subPhase = $subPhase; }
    public function setSavedTime($savedTime) { $this->savedTime = $savedTime; }
    public function setGameData($gameData) { $this->gameData = $gameData; }

    public function getSavedGameId() { return $this->savedGameId; }
    public function getGame_id() { return $this->game_id; }
    public function getName() { return $this->name; }
    public function getTurn() { return $this->turn; }
    public function getPhase() { return $this->phase; }
    public function getSubPhase() { return $this->subPhase; }
    public function getSavedTime() { return $this->savedTime; }
    public function getGameData() { return unserialize( gzuncompress(stream_get_contents($this->gameData))) ; } // stream_get_contents must be used, as gameData is a resource, not a string

    public function __construct($game)
    {
        if (in_array($game->getPhase(), self::$VALID_PHASES)) {
            $this->setGame_id($game->getId()) ;
            $this->setName($game->getName()) ;
            $this->setTurn($game->getTurn()) ;
            $this->setPhase($game->getPhase()) ;
            $this->setSubPhase($game->getSubPhase()) ;
            $this->setGameData( gzcompress(serialize($game->saveData()) , 9 ) ) ;
            $this->setSavedTime(new \DateTime('NOW')) ;

        } else {
            throw new \Exception(_('Invalid phase'));
        }
    }

}