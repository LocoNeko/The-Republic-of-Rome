<?php
namespace Presenters ;

class PopulationPhasePresenter
{
    // Common to all phase presenters
    public $user_id;
    public $game;
    public $yourParty;
    public $otherParties = [];
    public $header = [];
    public $interface = [];
    public $sliders = [];

    /**
     * @param \Entities\Game $game
     * @param int $user_id
     */
    public function __construct($game, $user_id)
    {
        /**
         * Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id;
        $this->game = new \Presenters\GamePresenter($game, $user_id);
        foreach ($game->getParties() as $party) {
            if ($party->getUser_id() == $user_id) {
                $this->yourParty = new \Presenters\PartyPresenter($party, $user_id);
            } else {
                $this->otherParties[$party->getUser_id()] = new \Presenters\PartyPresenter($party, $user_id);
            }
        }
        /**
         * Phase Header
         */
        $this->header['list'] = array() ;
        $this->header['actions'] = array() ;
        $this->header['description'] = _('State of the Republic - ') ;
        if ($game->getPhase() != 'Population') {
            $this->header['description'] .= _('ERROR - Wrong phase');
        }
        elseif ($game->getHRAO()->getLocation()['value']->getUser_id() == $user_id)
        {
            $this->header['description'] .= _('You are the HRAO and must give a State of the Republic speech') ;
            $this->header['actions'] = array (
                array (
                    'type' => 'button' ,
                    'verb' => 'populationSpeech' ,
                    'text' => 'CARTAGO DELENDA EST'
                )
            ) ;
        }
        else
        {
            $this->header['description'] .= _('You are waiting for the HRAO to give a State of the Republic speech') ;
        }
    }
}