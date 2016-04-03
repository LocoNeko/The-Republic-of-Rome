<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class SetupPhasePresenter
{
    private $game ;
    /**
     * 
     * @param \Entities\Game $game
     */
    public function __construct($game) {
        $this->game = $game ;
    }
    
    /**
     * The function provides an array with :
     * 'description' : Example 'You have playbale cards' , 'You have no playbale cards and are waiting'
     * 'list' : Example 'list1' => 'item 1' , 'item 2' ,...
     * 'action' : Used to provide an action in the header. Example : 'type' => 'button' , 'verb' => 'DonePlayingCards' , 'text' => 'DONE' , 'user_id' => a user_id
     * 
     * @param int $user_id
     * @return array
     */
    public function getHeader($user_id)
    {
        $result = array() ;
        $result['list'] = array() ;
        $result['action'] = array () ;
        // Header for PickLeaders sub phase
        if ($this->game->getPhase()=='Setup' && $this->game->getSubPhase()=='PickLeaders')
        {
            // Leader not yet picked
            if ($this->game->getParty($user_id)->getLeader() === NULL)
            {
                $result['description'] = _('Drag and drop the leader token on one of your Senators to make him party leader.') ;
                $result['action'] = array (
                    'type' => 'icon' ,
                    'verb' => 'PickLeader' ,
                    'text' => ' Leader' ,
                    'caption' => 'Drag and drop on top of a Senator to make him Party leader'
                );
            }
            // Leader already picked, waiting for others
            else
            {
                $result['description'] = _('You have set your party\'s leader and are currently waiting for :');
                foreach ($this->game->getAllPartiesButOne($user_id) as $party)
                {
                    if ($party->getLeader() === NULL)
                    {
                        $result['list'][] = $party->getFullName();
                    }
                }
            }
        }
        // Header for Play cards sub phase
        elseif ($this->game->getPhase()=='Setup' && $this->game->getSubPhase()=='Play cards')
        {
            // Has cards and is not done yet
            if ( ($this->game->getParty($user_id)->hasPlayableCards()) && ($this->game->getParty($user_id)->getIsDone() == FALSE))
            {
                $result['description'] = _('You have playable cards :') ;
                $result['list'] = array (
                    'Drag and drop Concessions on Senators' ,
                    'Play Statesmen if they are playable' ,
                    'Click DONE when finished'
                ) ;
                $result['action'] = array (
                    'type' => 'button' ,
                    'verb' => 'DonePlayingCards' ,
                    'text' => 'DONE' ,
                    'user_id' => $user_id
                );
            }
            elseif ($this->game->getParty($user_id)->getIsDone())
            {
                $result['description'] = _('You are done playing cards and are waiting for :') ;
                foreach ($this->game->getParties() as $party)
                {
                    if ($party->getId() != $user_id && $party->hasPlayableCards() && $party->getIsDone() == FALSE)
                    {
                        $result['list'][] = $party->getFullName();
                    }
                }
            }
            else
            {
                $result['description'] = _('You don\'t have any playable card') ;
                $waiting = FALSE ;
                foreach ($this->game->getParties() as $party)
                {
                    if ( ($party->getId() != $user_id) && ($party->hasPlayableCards() && $party->getIsDone() == FALSE) )
                    {
                        $result['list'][] = $party->getFullName();
                        $waiting = TRUE ;
                    }
                }
                if ($waiting)
                {
                    $result['description'].=_(' and are waiting for :');
                }
                $result['action'] = array (
                    'type' => 'button' ,
                    'verb' => 'DonePlayingCards' ,
                    'text' => 'DONE' ,
                    'user_id' => $user_id
                );

            }
        }
        return $result ;
    }
}
