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
     * @param type $user_id
     */
    public function getHeader($user_id)
    {
        $result = array() ;
        $result['list'] = array() ;
        $result['action'] = array () ;
        if ($this->game->getPhase()=='Setup' && $this->game->getSubPhase()=='PickLeaders')
        {
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
        return $result ;
    }
}
