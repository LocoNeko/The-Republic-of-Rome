<?php
namespace Presenters ;
use Doctrine\Common\Collections\ArrayCollection;

class CombatPhasePresenter
{
    // Common to all phase presenters
    public $user_id;
    public $gamePresenter;
    public $yourParty;
    public $otherParties = [];
    public $header = [];
    public $interface = [];
    public $sliders = [];
    public $data_json ;
    
    /**
     * @param \Entities\Game $game
     * @param int $user_id
     * @throws \Exception
     */
    public function __construct($game, $user_id)
    {
        /**
         * @todo Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id;
        /** @todo This is awful. Rename to gamePresenter, which means reviewing all my twig templates FFS */
        $this->game = new \Presenters\GamePresenter($game, $user_id);
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id) 
            {
                $this->yourParty = new \Presenters\PartyPresenter($party, $user_id);
            }
            else
            {
                $this->otherParties[$party->getUser_id()] = new \Presenters\PartyPresenter($party, $user_id);
            }
        }
        
        /**
         * Phase Header
         */
        $this->header['list'] = array();
        $this->header['actions'] = array();
        $this->header['description'] = _('Combat') ;
        if ($game->getPhase() != 'Combat') 
        {
            throw new \Exception(_('ERROR - Wrong phase')) ;
        }
        $this->interface['name'] = 'list';
        /**
         * - List all combat for this turn
         * - For battles with more than one army sent against them, commanders agree on the order
         */
        $this->interface['list'] = $game->getBattleList($user_id) ;
    }
}
