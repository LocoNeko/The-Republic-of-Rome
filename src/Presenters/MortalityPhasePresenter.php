<?php
namespace Presenters ;

class MortalityPhasePresenter
{
    // Common to all phase presenters
    public $user_id ;
    public $game ;
    public $yourParty ;
    public $otherParties = [] ;
    public $header = [] ;
    public $interface = [] ;
    public $sliders = [] ;

    /**
     * @param \Entities\Game $game
     * @param int $user_id
     */
    public function __construct($game , $user_id)
    {
        /**
         * Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id;
        $this->game = new GamePresenterNew($game, $user_id);
        foreach ($game->getParties() as $party) {
            if ($party->getUser_id() == $user_id) {
                $this->yourParty = new PartyPresenter($party, $user_id);
            } else {
                $this->otherParties[$party->getUser_id()] = new PartyPresenter($party, $user_id);
            }
        }
        
        /**
         * Phase Header
         */
        $this->header['list'] = array();
        $this->header['actions'] = array();
        if ($game->getPhase() != 'Mortality')
        {
            $this->header['description'] .= _('ERROR - Wrong phase');
        }
        else
        {
            if ($game->getParty($user_id)->getIsDone())
            {
                $this->header['description'] = _('You are ready for the Mortality roll. Waiting for :') ;
                foreach ($game->getParties() as $party)
                {
                    if ($party->getId() != $user_id && $party->getIsDone() === FALSE)
                    {
                        $this->header['list'][] = $party->getFullName();
                    }
                }

            }
            else
            {
            $this->header['description'] = _('Click when you are ready for the Mortality roll :') ;
                $this->header['actions'] = array (
                    array(
                    'type' => 'button' ,
                    'verb' => 'MortalityReady' ,
                    'text' => 'READY'
                    )
                );
            }
            
        }
    }
}