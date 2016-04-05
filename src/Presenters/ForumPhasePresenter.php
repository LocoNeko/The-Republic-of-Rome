<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class ForumPhasePresenter
{
    private $game ;
    /**
     * 
     * @param \Entities\Game $game
     */
    public function __construct($game) {
        $this->game = $game ;
    }
    
    public function getHeader($user_id)
    {
        $result = array() ;
        $result['list'] = array() ;
        $result['action'] = array () ;
        if ($this->game->getPhase()=='Forum' && $this->game->getSubPhase()=='RollEvent')
        {
            
        }
        $result['initiativeDescription'] = 'Initiative - '.$this->getInitiativeDescription($user_id) ;
        // Header for RollEvent sub phase
        if ($this->game->getPhase()=='Forum' && $this->game->getSubPhase()=='RollEvent')
        {
            $result['description'] = _('Rolling event') ;
            $result['list'][] = _('In case a 7 is rolled, another roll will be made on the events table');
            $result['list'][] = _('In case a 7 is not rolled, a card will be drawn and put in the hand or a deck, depending on its type');
            $result['action'] = array (
                'type' => 'button' ,
                'verb' => 'RollEvent' ,
                'text' => 'ROLL' ,
                'user_id' => $user_id
            );

        }
        else
        {
            $result['description'] = _('Phase : TO DO...') ;
        }
        return $result;
    }
    
    /**
     * Returns the party that currently has the initiative or FALSE if none
     * @return \Entities\Party | boolean
     */
    public function getPartyWithInitiative()
    {
        // One of the automatic initiatives
        if ($this->game->getInitiative() <= $this->game->getNumberOfPlayers())
        {
            return $this->game->getOrderOfPlay()[$this->game->getInitiative()-1] ;
        }
        else
        // One of the initiatives up for bidding
        {
            foreach ($this->game->getParties() as $party)
            {
                if ($party->getInitiativeWon())
                {
                    return $party ;
                }
            }
        }
        return FALSE ;
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
            return _('Up for bidding');
        }
        else
        {
            return (($user_id==$partyWithInitiative->getUser_id()) ? _('You') : $partyWithInitiative->getFullName());
        }
    }
}
