<?php
namespace Presenters ;

class MortalityPhasePresenter
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
        if ($this->game->getPhase()=='Mortality')
        {
            if ($this->game->getParty($user_id)->getIsDone())
            {
                $result['description'] = _('You are ready for the Mortality roll. Waiting for :') ;
                foreach ($this->game->getAllPartiesButOne($user_id) as $party)
                {
                    if ($party->getIsDone() === FALSE)
                    {
                        $result['list'][] = $party->getFullName();
                    }
                }

            }
            else
            {
            $result['description'] = _('Click READY when you are ready for the Mortality roll.') ;
                $result['action'] = array (
                    'type' => 'button' ,
                    'verb' => 'MortalityReady' ,
                    'text' => 'READY' ,
                    'user_id' => $user_id
                );
            }
        }
        else
        {
            $result['description'] = _('ERROR - Should be Mortality phase') ;
        }
        return $result;
    }
}