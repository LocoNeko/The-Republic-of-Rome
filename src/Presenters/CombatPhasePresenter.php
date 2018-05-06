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
        $this->interface['list'] = $this->getBattleList($game , $user_id) ;
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
     * Get a list of all the battles for this turn and this user_id
     * 
     * @param \Entities\Game $game
     * @param int $user_id
     * @return array    'proposal', 
     *                  'commander' : array ('senatorID' , 'commander_user_id' , 'name' , 'office' , 'MIL' , 'specialAbility') ,<br>
     *                  'MoH' : array ('senatorID' , 'user_id' , 'Name' , 'MIL')<br>
     *                  'conflict' : array( 'cardId' , 'name' , 'multiplier' , 'leaders' , 'fleet' , 'support' , 'land' , 'totalNaval' , 'totalLand') ,<br>
     *                  'disasters' , 'standoffs' , 'fleets' , 'fleetsModified' , 'regulars' , 'regularsModified'  , //'veterans'// , 'battleResult' , 'battleCount' , 'action'<br>
     */
    public function getBattleList($game , $user_id)
    {
        $result = [] ;
        foreach ($game->getProposals() as $proposal)
        {
            /** Only check commander proposals from this turn*/
            if (($proposal->getTurn() == $game->getTurn()) && ($proposal->getType() == 'commander'))
            {
                /** Go through all individual items, as they may be grouped */
                foreach ($proposal->getContent() as $item)
                {
                    /* @var $commander \Entities\Senator */
                    $commander = $game->getFilteredCards(array('senatorID'=>$item['commander']))->first() ;
                    $MIL_total = 0 ;
                    if ($commander->getOffice()=='Dictator')
                    {
                        $MoH = $game->getAllSenators('isMaster of Horse')->first() ;
                        $MoH_array = array (
                            'senatorID' => $MoH->getSenatorID() ,
                            'user_id' => $MoH->getLocation()['value']->getUser_id() ,
                            'name' => $MoH->getName() ,
                            'MIL' => $MoH->getMIL()
                        ) ;
                        $MIL_total += $MoH->getMIL() ;
                    }
                    else
                    {
                        //$MoH_array = NULL ;
                        /** @todo this is a debug thing , delete once happy, replace by line above*/
                        $MoH_array = array (
                            'senatorID' => 2 ,
                            'user_id' => 10 ,
                            'name' => 'FABIUS' ,
                            'MIL' => 4
                        ) ;
                        $MIL_total += 4 ;
                    }
                    $commander_user_id = $commander->getLocation()['value']->getUser_id() ;

                    /* @var $conflict \Entities\Conflict */
                    $conflict = $game->getFilteredCards(array('cardId'=>$item['conflict']))->first() ;
                    $leaders_array = [] ;
                    $disaster_array = [] ;
                    $standoff_array = [] ;
                    /**
                     * Enemy leaders
                     */
                    foreach ($conflict->getCardsControlled()->getCards() as $card)
                    {
                        /* @var $card \Entities\Leader */
                        if ($card->getPreciseType()=='Leader')
                        {
                            $leaders_array[] = array (
                                'name' => $card->getName() ,
                                'description' => $card->getDescription() ,
                                'strength' => $card->getStrength() ,
                                'ability' => $card->getAbility() 
                            ) ;
                            $disaster_array[] = $card->getDisaster() ;
                            $standoff_array[] = $card->getStandoff() ;
                        }
                    }
                    $disaster_array[] = $conflict->getDisaster() ;
                    $standoff_array[] = $conflict->getStandoff() ;
                    $MIL_total += $commander->getMIL() ;
                    /**
                     * Go through all the items in the proposal to check if the same conflict is fought by several commander
                     */
                    $battleCount = 0 ;
                    /**
                     * Action buttons :
                     * - 'battleOrder' choice if the same conflict is fought several time as part fo the same proposal
                     * > The choice will be saved in the 'battleResult' of the Proposal as a number (1 = fight first , 2 = fight second, etc)
                     * > When a commander chooses the order, see if there are no more 'pending' battleResult, in which case :
                     * > Check if the commanders agree on the order, assign randomely if they don't
                     * > Once there are no more battleOrder action button to show, we can show only combatRoll
                     * - 'combatRoll' action button if the commander is in the user's party and the conflicts' order is known
                     */
                    $action = ( ($commander_user_id == $user_id) ? 
                    array(
                        'type'=> 'button' ,
                        'verb' => 'combatRoll' ,
                        'style' => 'danger' ,
                        'text'=> _('VAE VICTIS')
                    ) : FALSE );
                    foreach ($proposal->getContent() as $item2)
                    {
                        $conflict2 = $game->getFilteredCards(array('cardId'=>$item2['conflict']))->first() ;
                        if ($conflict->getCardId() == $conflict2->getCardId())
                        {
                            $battleCount++ ;
                        }
                    }
                    $result[] = array (
                        'proposal' => $proposal->getId() ,
                        'commander' => array(
                            'senatorID' => $commander->getSenatorID() ,
                            'commander_user_id' => $commander_user_id ,
                            'name' => $commander->getName() ,
                            'office'  => $commander->getOffice() ,
                            'MIL' => $commander->getMIL() ,
                            'specialAbility' => $commander->getSpecialAbility()
                        ) ,
                        'MoH' => $MoH_array ,
                        'conflict' => array(
                            'cardId' => $conflict->getCardId() ,
                            'name' => $conflict->getName() ,
                            'multiplier' => $game->getConflictMultiplier($conflict) ,
                            'leaders' => $leaders_array ,
                            'fleet' => $conflict->getFleet() ,
                            'support' => $conflict->getSupport() ,
                            'land'  => $conflict->getLand() ,
                            'totalNaval' => $game->getModifiedConflictStrength($conflict)['fleet'] ,
                            'totalLand' => $game->getModifiedConflictStrength($conflict)['land']
                        ) ,
                        'disasters' => implode(',' , $disaster_array) ,
                        'standoffs' => implode(',' , $standoff_array) ,
                        'fleets' => $item['fleets'] ,
                        'fleetsModified' => ($item['fleets']>0 ? ($item['fleets'] + min($MIL_total,$item['fleets'])) : 0)  ,
                        'regulars' => 0 ,
                        'regularsModified' => 0  ,
                        //'veterans' => $item['veterans'] ,
                        'battleResult' => $item['battleResult'] ,
                        'battleCount' => $battleCount ,
                        'action' => $action
                    ) ;
                }
            }
        }
        return $result ;
    }
}

