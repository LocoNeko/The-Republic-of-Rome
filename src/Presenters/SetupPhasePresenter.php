<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

class SetupPhasePresenter
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
        $this->game = new GamePresenter($game, $user_id);
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
        if ($game->getPhase() != 'Setup')
        {
            $this->header['description'] .= _('ERROR - Wrong phase');
        }
        elseif ($game->getSubPhase() == 'PickLeaders' && $game->getParty($user_id)->getLeader() === NULL )
        {
            $this->header['description'] = _('Drag and drop the leader token on one of your Senators to make him party leader.');
            $this->header['actions'] = array(
                array (
                    'type' => 'icon',
                    'verb' => 'PickLeader',
                    'text' => ' Leader',
                    'caption' => 'Drag and drop on top of a Senator to make him Party leader' ,
                    'class' => 'glyphicon glyphicon-star' ,
                    'data_json' => '{"verb":"PickLeader"}'
                )
            );
            // droppable : add the droppable to all Senators
            foreach ($this->yourParty->senators as $senatorID => $senator)
            {
                $senator->addClass('droppable');
            }
        }
        // Leader already picked, waiting for others
        elseif ($game->getSubPhase() == 'PickLeaders')
        {
            $this->header['description'] = _('You have set your party\'s leader and are currently waiting for :');
            foreach ($game->getParties() as $party)
            {
                if ($party->getUser_id()!==$user_id && $party->getLeader() === NULL)
                {
                    $this->header['list'][] = $party->getName() ;
                }
            }
        }
        elseif ($game->getSubPhase() == 'PlayCards')
        {
            $playableCards = $this->getPlayableCards($game, $user_id) ;
            error_log(count($playableCards));
            if ( count($playableCards)>0 && $game->getParty($user_id)->getIsDone() == FALSE )
            {
                $this->header['description'] = _('You have playable cards :');
                $this->header['list'] = array(
                    'Drag and drop Concessions on Senators',
                    'Play Statesmen if they are playable',
                    'Click DONE when finished'
                );
                $this->header['actions'] = array (
                    array(
                        'type' => 'button',
                        'verb' => 'DonePlayingCards',
                        'text' => 'DONE'
                    )
                );
                // droppable : add the droppable to all Senators
                foreach ($this->yourParty->senators as $senatorID=>$senator)
                {
                    $senator->addClass('droppable') ;
                }
                /*
                 * - Playable Concessions get class 'draggable' and verb 'setupPlayConcession'
                 * - Playable Statesmen get the 'setupPlayStatesman' menu item
                 */
                foreach ($this->yourParty->hand->cards as $card)
                {
                    if (isset($playableCards[$card->getAttribute('card_id')]))
                    {
                        /** @var \Presenters\CardPresenter */
                        if ($card->preciseType=='Concession')
                        {
                            $card->addClass('draggable') ;
                            $card->addAttribute('verb' , 'setupPlayConcession') ;
                        }
                        // Menu on Statesman
                        elseif ($card->preciseType=='Statesman')
                        {
                            $card->addMenuItem (
                                array (
                                    'style' => 'primary' ,
                                    'verb' => 'setupPlayStatesman' ,
                                    'text' => _('Play Statesman')
                                )
                            ) ;
                        }
                    }
                }
            }
            elseif ($game->getParty($user_id)->getIsDone())
            {
                $this->header['description'] = _('You are done playing cards and are waiting for :');
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
                $this->header['description'] = _('You don\'t have any playable card');
                $this->header['actions'] = array (
                    array(
                        'type' => 'button',
                        'verb' => 'DonePlayingCards',
                        'text' => 'DONE'
                    )
                );
            }
        }
    }
    
    /**
     * Returns an array of playable cards in hand
     * @param \Entities\Game $game
     * @param int $user_id
     * @return array 
     */
    public function getPlayableCards($game , $user_id)
    {
        $result = array() ;
        foreach ($game->getParty($user_id)->getHand()->getCards() as $card)
        {
            if ( $card->getPreciseType()=='Statesman' && $card->statesmanPlayable($user_id)['flag'])
            {
                $result[$card->getId()]=$card ;
            }
            elseif ($card->getPreciseType()=='Concession')
            {
                if (($card->getSpecial()=='land bill') && ($game->getLandBillsTotalCost()['total']==0) )
                {
                }
                else
                {
                    $result[$card->getId()]=$card ;
                }
            }
        }
        return $result ;
    }
}
