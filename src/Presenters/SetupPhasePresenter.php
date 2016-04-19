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
                    'caption' => 'Drag and drop on top of a Senator to make him Party leader'
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
            $this->header['description'] = _('You have set your party\'s leader and are currently waiting for other parties to do the same');
        }
        // TO DO : refactor hasPlayableCards
        elseif ($game->getSubPhase() == 'PlayCards' && $game->getParty($user_id)->hasPlayableCards() && $game->getParty($user_id)->getIsDone() == FALSE)
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
            // TO DO : draggable on concessions
            foreach ($this->yourParty->hand->cards as $card)
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
        elseif ($game->getSubPhase() == 'PlayCards' && $game->getParty($user_id)->getIsDone())
        {
            $this->header['description'] = _('You are done playing cards and are waiting for :');
            foreach ($game->getParties() as $party)
            {
                if ($party->getId() != $user_id && $party->hasPlayableCards() && $party->getIsDone() == FALSE)
                {
                    $this->header['list'][] = $party->getFullName();
                }
            }
        }
        elseif ($game->getSubPhase() == 'PlayCards')
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
