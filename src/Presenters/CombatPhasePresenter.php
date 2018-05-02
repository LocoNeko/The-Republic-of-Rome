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
        $this->header['description'] = print_r($this->getBattleList($game),TRUE) ;
        /**
         * - List all combat for this turn
         * - The party of the commander of the first combat has a "NAVAL BATTLE" or "LAND BATTLE" button
         * - For battles with a naval and land component, check which one to roll for
         * - For battles with more than one army sent against them, commanders agree on the order
         * - If a naval battle is won, the commander MAY choose to fight the land battle
         * - Rolls are impacted by events
         * - Handle results : Defeat, Disaster, Standoff, Stalemate , 
         * - Handle Victory : Spoils, provinces, INF and POP gain for commander
         * - Create veterans after Standoff, Stalemate , Victory
         * - Losses (Legions, Fleets), including commander loss of POP when legions die
         * - Commander death (including MoH)
         * - Commander capture
         * - Proconsuls
         * - Move wars to unprosecuted if applicable
         * 
         */
    }
    
    /**
     * 
     * @param \Entities\Game $game
     */
    public function getBattleList($game)
    {
        $result = [] ;
        foreach ($game->getProposals() as $proposal)
        {
            /** Only check commander proposals from this turn*/
            if (($proposal->getTurn() == $game->getTurn()) && ($proposal->getType() == 'commander'))
            {
                foreach ($proposal->getContent() as $item)
                {
                    /* @var $commander \Entities\Senator */
                    $commander = $game->getFilteredCards(array('senatorID'=>$item['commander']))->first() ;
                    if ($commander->getOffice()=='Dictator')
                    {
                        $MoH = $game->getAllSenators('isMaster of Horse')->first() ;
                        $MoH_array = array (
                            'senatorID' => $MoH->getSenatorID() ,
                            'user_id' => $MoH->getLocation()['value']->getUser_id() ,
                            'Name' => $MoH->getName() ,
                            'MIL' => $MoH->getMIL()
                        ) ;
                    }
                    else
                    {
                        $MoH_array = NULL ;
                    }
                    $user_id = $commander->getLocation()['value']->getUser_id() ;

                    /* @var $conflict \Entities\Conflict */
                    $conflict = $game->getFilteredCards(array('cardId'=>$item['conflict']))->first() ;
                    $leaders_array = [] ;
                    foreach ($conflict->getCardsControlled()->getCards() as $card)
                    {
                        /* @var $card \Entities\Leader */
                        if ($card->getPreciseType()=='Leader')
                        {
                            $leaders_array[] = array (
                                'description' => $card->getDescription() ,
                                'strength' => $card->getStrength() ,
                                'disaster' => $card->getDisaster() ,
                                'standoff' => $card->getStandoff() ,
                                'ability' => $card->getAbility() 
                            ) ;
                        }
                    }
                    $result[] = array (
                        'commander' => array(
                            'senatorID' => $commander->getSenatorID() ,
                            'user_id' => $user_id ,
                            'Name' => $commander->getName() ,
                            'Office'  => $commander->getOffice() ,
                            'MIL' => $commander->getMIL() ,
                            'specialAbility' => $commander->getSpecialAbility()
                        ) ,
                        'Moh' => $MoH_array ,
                        'conflict' => array(
                            'cardId' => $conflict->getCardId() ,
                            'multiplier' => $game->getConflictMultiplier($conflict) ,
                            'leaders' => $leaders_array ,
                            'fleet' => $conflict->getFleet() ,
                            'support' => $conflict->getSupport() ,
                            'land'  => $conflict->getLand() ,
                            'totalNaval' => $game->getModifiedConflictStrength($conflict)['fleet'] ,
                            'totalLand' => $game->getModifiedConflictStrength($conflict)['land'] ,
                            'disaster' => $conflict->getDisaster() ,
                            'standoff' => $conflict->getStandoff()
                        ) ,
                        'fleets' => $item['fleets'] ,
                        //'regulars' => $item['regulars'] ,
                        //'veterans' => $item['veterans'] ,
                        'battleResult' => $item['battleResult']
                    ) ;
                }
            }
        }
        return $result ;
    }
}

