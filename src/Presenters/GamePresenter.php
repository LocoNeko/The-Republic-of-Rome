<?php
namespace Presenters ;

class GamePresenter
{
    public $user_id ;
    public $game_id;
    public $name ;
    public $phase ;
    public $subPhase ;
    public $treasury ;
    public $unrest ;
    public $legionsDescription = [] ;
    public $HRAO_name ;
    public $orderOfPlay = [] ;
    public $deck = [] ;
    public $warDecksNames ;
    public $eventsInPlay = [] ;
    public $messages ;
    public $partiesNames ;
    public $variants ;
    
    /**
     * Creates a Game Presenter for this $game
     * @param \Entities\Game $game
     * @param int $user_id
    */
    public function __construct($game , $user_id) {
        $this->user_id = $user_id ;
        $this->game_id = $game->getId() ;
        $this->name = $game->getName() ;
        $this->phase = $game->getPhase() ;
        $this->subPhase = $game->getSubPhase() ;
        $this->treasury = $game->getTreasury() ;
        $this->unrest = $game->getUnrest() ;
        $this->legions = $game->getLegions() ;
        $this->fleets = $game->getFleets() ;
        $this->legionsDescription['Rome'] = '' ;
        foreach ($game->getLegions() as $legion)
        {
            if ($legion->getLocation()=='Rome')
            {
                $this->legionsDescription['Rome'].=$legion->getName().',';
            }
        }
        $this->partiesNames = $game->getPartiesNames() ;
        $this->HRAO_name = $this->displayContextualName($game->getHRAO(TRUE)->getFullName()) ;
        $this->orderOfPlay = $this->getOrderOfPlay($game, $user_id) ;
        foreach ($game->getDecks() as $deck)
        {
            $this->deck[$deck->getName()] = new \Presenters\DeckPresenter($deck , $user_id) ;
        }
        $this->warDecksNames = array ( 'inactiveWars' , 'activeWars' , 'imminentWars' , 'unprosecutedWars') ;
        foreach ($game->getEvents() as $number => $event)
        {
            if ($event['level']>0)
            {
                $this->eventsInPlay[] = array (
                    'number' => $number ,
                    'name' => $game->getEventProperty('number', $number, 'name') ,
                    'level' => $event['level'] ,
                    'description' => $game->getEventProperty('number', $number, 'description')
                ) ;
            }
        }
        /*
         * Returns all the messages in this Game which have $user_id or NULL as a recipient (NULL means everybody)
         * The Presenter gets the following from the Message, using $message->getLogVersion(...) :
         * - Time
         * - Colour
         * - Text
         * - Trace operation
         * - Trace description (FALSE if no trace)
         * - ProposalId (or -1 if not a proposal)
         * - Proposal is underway (or FALSE if not a proposal)
         */
        $messages = array() ;
        /* @var $message \Entities\Message */
        $currentUndo = 'none' ;
        foreach (array_reverse($game->getMessages()->toArray()) as $message) 
        {
            if ( $message->isRecipient($user_id) )
            {
                $messageLogVersion = $message->getLogVersion($user_id, $this->partiesNames) ;
                // 'undoTrace' is TRUE only when we need to display the UNDO icon
                $messageLogVersion['undoTrace'] = FALSE ;
                if ($currentUndo=='none' && $messageLogVersion['traceDescription'])
                {
                    $currentUndo = $messageLogVersion['traceOperation'] ;
                    $messageLogVersion['undoTrace'] = TRUE ;
                }
                if ($currentUndo=='proposal')
                {
                    
                }
                // If we don't have a trace yet, we check the $message for the trace's operation
                // If the operation is a proposal, we only display an UNDO if the proposal's outcome is not 'underway'
                // If we have set an UNDO, remember the proposal
                // Display a tiny icon on all other actions of the same proposal
                // If the operation is not a proposal, display an UNDO, and set the current undo to 'operation'
                array_push($messages , $messageLogVersion) ;
            }
        }
        $this->messages = $messages ;
        $this->variants = $game->getVariants() ;
    }

    /**
     * returns array of user_id from HRAO, clockwise in the same order as the array $this->party (order of joining game)
     * @param \Entities\Game $game
     * @param int $user_id
     * @return array An array of arrays with keys 'user_id' , 'name' , 'playingNow'
     */
    public function getOrderOfPlay($game , $user_id)
    {
        $result = array() ;
        foreach($game->getParties() as $party)
        {
            array_push($result , array (
                'user_id' => $party->getUser_id() ,
                'name' => $party->getName() ,
                'playingNow' => ($game->whoseTurn()!==FALSE && ($party->getUser_id() == $game->whoseTurn()->getUser_id()) )
            ));
        }
        $partyOfHRAO = $game->getHRAO()->getLocation()['value'] ;
        if (!is_null($partyOfHRAO))
        {
            while ((int)$result[0]['user_id'] !== $partyOfHRAO->getUser_id())
            {
                array_push($result , array_shift($result) );
            }
        }
        return $result ;
    }

    /**
     * @param string $input
     * @return string
     */
    public function displayContextualName($input)
    {
        $output = $input ;
        foreach($this->partiesNames as $party_userId => $partyName) 
        {
            if (strpos($output, '[['.$party_userId.']]') !==FALSE) 
            {
                $output = str_replace('[['.$party_userId.']]' , ( ($party_userId==$this->user_id) ? _('you') : $partyName ) , $output);
            }
        }
        return $output ;
    }

    /**
     * Checks if a variant is in play - Convenience function for presenters
     * @param string $variant
     * @return boolean
     */
    public function getVariantFlag($variant)
    {
        return (in_array($variant,$this->variants)) ;
    }

}