<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;

class SenatePhasePresenter
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
        $this->header['list'] = array();
        $this->header['actions'] = array();
        $this->header['description'] = _('Senate') ;
        if ($game->getPhase() != 'Senate') {
            $this->header['description'] .= _('ERROR - Wrong phase');
        }
        else
        {
            $currentProposal = $game->getProposals()->last() ;
            /**
             * There is a proposal underway
             */
            if ($currentProposal!==FALSE && $currentProposal->getOutcome()!=='underway')
            {
                $this->header['description'] .= _(' - Proposal underway');
            }
            /**
             * No proposal underway
             */
            else
            {
                $this->header['description'] .= _(' - No proposal underway') ;
                //Returns all required content
                $content = $this->getRequiredContent($game) ;
                foreach ($content as $key => $value)
                {
                    /**
                     * Description of what must be chosen
                     */
                    if ($key == 'description' )
                    {
                        foreach ($value as $line)
                        {
                            $this->header['list'][] = $line ;
                        }
                    }
                    elseif ($key == 'items' )
                    {
                        foreach ($value as $item)
                        {
                            //$item['description']
                            //$item['type'] ;
                            //$item['values'] ;
                            if ($item['type']=='senator')
                            {
                                $this->addClassToCards(
                                    array(
                                        array ('type'=>'class' , 'name' =>'draggable') ,
                                        array ('type'=>'attribute' , 'name' =>'action' , 'value' => array("noSubmit") )
                                    ) ,
                                    $item['values']
                                ) ;
                                $this->header['actions'][] = array(
                                    'type' => 'cardSlot' ,
                                    'description' => $item['description']
                                ) ;
                            }
                        }
                        $this->header['actions'][] = array(
                            'type' => 'button' ,
                            'verb' => 'youpla' ,
                            'text' => 'Youpli'
                        ) ;
                    }
                }
            }
        }
    }
    
    public function addClassToCards($toAdd , $cardsToApplyItTo)
    {
        /**
         * Create a collection of all cards from game decks & parties
         * Hands and not included
         */
        $allCards = new ArrayCollection() ;
        foreach ($this->game->deck as $deck)
        {
            foreach ($deck->cards as $card)
            {
                $allCards->add($card) ;
            }
        }
        foreach ($this->otherParties as $party)
        {
            foreach ($party->senators as $senator)
            {
                $allCards->add($senator) ;
            }
        }
        foreach ($this->yourParty->senators as $senator)
        {
            $allCards->add($senator) ;
        }
        /**
         * Goes through the collection and checks if
         */
        foreach ($allCards as $card)
        {
            foreach ($cardsToApplyItTo as $targetCard)
            {
                if ($targetCard->getId() == $card->id)
                {
                    foreach ($toAdd as $value)
                    {
                        if ($value['type'] == 'class')
                        {
                            $card->addClass($value['name']) ;
                        }
                        elseif ($value['type'] == 'attribute')
                        {
                            $card->addAttribute($value['name'] , $value['value']) ;
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns what is needed to create the content of this proposal
     * @param \Entities\Game $game
     * @return array
     */
    public static function getRequiredContent($game)
    {
        if ($game->getSubPhase()=='Consuls')
        {
            return array
            (
                'description' => array (
                    _('You must select a pair of Senators') ,
                    _('Only Senators aligned in Rome can be proposed') ,
                    _('The only possible candidates already holding an office are the Censor and Master of Horse') ,
                    _('Already rejected pairs cannot be proposed again')
                ) ,
                'items' => array
                (
                    array (
                        'type' => 'senator' ,
                        'description' => 'First Senator'  ,
                        'values' => $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleConsul')
                    ) ,
                    array (
                        'type' => 'senator' ,
                        'description' => 'Second Senator'  ,
                        'values' => $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleConsul')
                    )
                )
            ) ;
        }
    }
}