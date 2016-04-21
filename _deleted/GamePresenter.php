<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class GamePresenter
{
    /* @var \Entities\Game */
    private $game ;
    public $realm ;
    public $gameName ;
    public $phase ;
    public $subPhase ;
    public $whoseTurn ;
    public $party = [] ;
    public $deck = [] ;
    public $warDecksNames ;
    
    /**
     * Creates a Game Presenter for this $game
     * @param \Entities\Game $game
     */
    public function __construct($game) {
        $this->game = $game ;
        $this->realm = $game->getId() ;
        $this->gameName = $game->getName() ;
        $this->phase = $game->getPhase() ;
        $this->subPhase = $game->getSubPhase() ;
        $this->whoseTurn = $game->whoseTurn() ;
        foreach ($game->getParties() as $party)
        {
            $this->party[$party->getUser_id()] = $party ;
        }
        foreach ($game->getDecks() as $deck)
        {
            $this->deck[$deck->getName()] = $deck ;
        }
        $this->warDecksNames = array ( 'inactiveWars' , 'activeWars' , 'imminentWars' , 'unprosecutedWars') ;
    }

    /**
     * Returns the name of the current HRAO, including his Party or "you", based on the user_id
     * @param int $user_id
     * @return string
     */
    public function getNameOfHRAO($user_id)
    {
        $HRAO = $this->game->getHRAO() ;
        return $this->displayContextualName($HRAO->getFullName() , $user_id) ;
    }
    
    /**
     * returns array of user_id from HRAO, clockwise in the same order as the array $this->party (order of joining game)
     * @return array An array of arrays with keys 'user_id' , 'name' , 'playingNow'
     */
    public function getOrderOfPlay($user_id)
    {
        $result = array() ;
        foreach($this->game->getParties() as $party)
        {
            array_push($result , array (
                'user_id' => $party->getUser_id() ,
                'name' => $party->getName() ,
                'playingNow' => ($user_id == $this->game->whoseTurn()->getUser_id())
            ));
        }
        $partyOfHRAO = $this->game->getHRAO()->getLocation()['value'] ;
        if (!is_null($partyOfHRAO))
        {
            while ((int)$result[0]['user_id'] !== $partyOfHRAO->getUser_id())
            {
                array_push($result , array_shift($result) );
            }
        }
        return $result ;
    }
    
    // Function that displays who has the initiative, or "currently bidding"
    // Function that displays who is currently playing (example : bidding for initiative , bribing during persuasion, etc)
    
    /**
     * Replaces all instances of [[X]] by the name of party X or 'you' if party X is played by $user_id
     * @param string $input
     * @param int $user_id
     * @return string
     */
    public function displayContextualName($input , $user_id)
    {
        $output = $input ;
        foreach($this->game->getParties() as $party) 
        {
            $party_userId = $party->getUser_id() ;
            if (strpos($output, '[['.$party_userId.']]') !==FALSE) 
            {
                $output = str_replace('[['.$party_userId.']]' , ( ($party_userId==$user_id) ? _('you') : $party->getUserName() ) , $output);
            }
        }
        return $output ;
    }
    
    /**
     * Gets all parties except the party of $user_id
     * @param int $user_id
     * @return ArrayCollection ArrayCollection of Parties
     */
    public function getAllPartiesButOne ($user_id)
    {
        $result = $this->game->getParties()->matching( Criteria::create()->where(Criteria::expr()->neq('user_id', (int)$user_id)) );
        return $result ;
    }
    }