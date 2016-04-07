<?php
namespace Presenters ;

class ForumPhasePresenter
{
    private $user_id ;
    private $phase ;
    private $subPhase ;
    private $initiative ;
    private $partyWithInitiative ;
    private $persuasionTarget ;

    /**
     * 
     * @param \Entities\Game $game
     */
    public function __construct($game , $user_id) {
        $this->setUser_id($user_id) ;
        $this->setPhase($game->getPhase()) ;
        $this->setSubPhase($game->getSubPhase()) ;
        $this->setInitiative($game->getInitiative()) ;
        // PartyWithInitiative is set here
        $this->setPartyWithInitiative(FALSE) ;
        // One of the automatic initiatives
        if ($game->getInitiative() <= $game->getNumberOfPlayers())
        {
            $this->setPartyWithInitiative($game->getOrderOfPlay()[$game->getInitiative()-1]) ;
        }
        else
        // One of the initiatives up for bidding
        {
            foreach ($game->getParties() as $party)
            {
                if ($party->getInitiativeWon())
                {
                    $this->setPartyWithInitiative($party) ;
                }
            }
        }
        $this->setPersuasionTarget($game->getPersuasionTarget()) ;
    }
    
    public function setUser_id($user_id) { $this->user_id = $user_id; }
    public function setPhase($phase) { $this->phase = $phase;}
    public function setSubPhase($subPhase) { $this->subPhase = $subPhase;}
    public function setInitiative($initiative) { $this->initiative = $initiative; }
    public function setPartyWithInitiative($partyWithInitiative) { $this->partyWithInitiative = $partyWithInitiative; } 
    public function setPersuasionTarget($persuasionTarget) { $this->persuasionTarget = $persuasionTarget; }

    public function getUser_id() { return $this->user_id; }
    public function getPhase() { return $this->phase; }
    public function getSubPhase() { return $this->subPhase; }
    public function getInitiative() { return $this->initiative; }
    public function getPartyWithInitiative() { return $this->partyWithInitiative; }
    public function getPersuasionTarget() { return $this->persuasionTarget; }

    public function getHeader()
    {
        $result = array() ;
        $result['list'] = array() ;
        $result['action'] = array () ;
        $result['initiativeDescription'] = _('Initiative #').$this->getInitiative().$this->getInitiativeDescription($this->getUser_id()) ;
        /*
         * Initiative doesn't belong to any Party - bidding
         */
        if ($this->getPartyWithInitiative()===FALSE)
        {
            $result['description'] = _('Bidding for initiative') ;
        }
        else
        /*
         * Initiative belongs to a party
         */
        {
            /*
             * Roll events
             */
            if ($this->getPhase()=='Forum' && $this->getSubPhase()=='RollEvent')
            {
                $result['description'] = _('Rolling event') ;
                // Has the initative - rolls
                if ($this->getPartyWithInitiative()->getUser_id() == $this->getUser_id())
                {
                    $result['list'][] = _('In case a 7 is rolled, another roll will be made on the events table');
                    $result['list'][] = _('In case a 7 is not rolled, a card will be drawn and put in the hand or a deck, depending on its type');
                    $result['action'] = array (
                        'type' => 'button' ,
                        'verb' => 'RollEvent' ,
                        'text' => 'ROLL' ,
                        'user_id' => $this->getUser_id()
                    );
                }
                // Ain't got the initative - waits
                else
                {
                    $result['description'] = _('Waiting for event roll.') ;
                }
            }
            /*
             * Persuasion
             */
            elseif ($this->getPhase()=='Forum' && $this->getSubPhase()=='Persuasion')
            {
                $result['description'] = _('Persuasion') ;
                /*
                 * - If there is no target : the player with the initiative can pick one (with a persuader, an optional bribe, and an optional card) or pass
                 * - If there is a target :
                 *   - If the next available player is not done : Give a chance for the next available player to counter-bribe
                 *   - If all available players are done : The player with the initiative can roll or bribe more
                 */
                if ($this->getPersuasionTarget()===NULL)
                {
                    // Has the initative - Can persuade
                    if ($this->getPartyWithInitiative()->getUser_id() == $this->getUser_id())
                    {
                        $result['action'] = array (
                            'type' => 'button' ,
                            'verb' => 'NoPersuasion' ,
                            'text' => 'NO PERSUASION' ,
                            'user_id' => $this->getUser_id()
                        );
                    }
                    // Ain't got the initative - waits
                    else
                    {
                        $result['description'] = _('Waiting for persuasion.') ;
                    }
                }
            }
            else
            {
                $result['description'] = _('Phase : TO DO...') ;
            }
        }

        return $result;
    }

    public function getContent()
    {
        $result= array() ;
        /*
         * - Phase : Forum
         * - SubPhase : Persuasion
         * - Has initiative
         * - Persuasion target : NULL
         * The player with the initiative can pick one (with a persuader, an optional bribe, and an optional card)
         * Give a list of Persuaders, Persuadees, Cards
         */
        if ($this->getPartyWithInitiative()->getUser_id() == $this->getUser_id() && $this->getPhase()=='Forum' && $this->getSubPhase()=='Persuasion' && $this->getPersuasionTarget()===NULL)
        {
            
        }
        return $result ;
    }

    /**
     * Returns a description of the current state of the initiative
     * @param int $user_id A user_id
     * @return string "Up for bidding"<br>"You"<br>"{{Party->fullName()}}"
     */
    public function getInitiativeDescription($user_id)
    {
        $partyWithInitiative = $this->getPartyWithInitiative() ;
        if ($partyWithInitiative === FALSE)
        {
            return _(' is up for bidding');
        }
        else
        {
            return _(' belongs to ').(($user_id==$partyWithInitiative->getUser_id()) ? _('you') : $partyWithInitiative->getFullName());
        }
    }
}
