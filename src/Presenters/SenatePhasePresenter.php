<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;
// TO DO : Beginning of Senate phase - Initialise free tribunes of all senators

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
        $this->header['description'] = _('Senate') ;
        if ($game->getPhase() != 'Senate') 
        {
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
             * - Check if the user can make a proposal
             * - This will bring up the 'senateMakeProposal' interface
             * - In case he can't, this will bring up a waiting screen
             */
            else
            {
                $this->header['description'] .= _(' - No proposal underway') ;
                $listProposalHow = $this->getProposalHow($game) ; 
                
                /**
                * This user has at least one way of making a proposal
                **/
                if (count($listProposalHow)>0)
                {
                    // Bring up the senateMakeProposal interface
                    $this->interface['name'] = 'senateMakeProposal';
                    
                    /**
                    * How the Proposal is made : : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , FREE TRIBUNE FROM X , TRIBUNE CARD
                    **/
                    $this->interface['listProposalHow'] =  array (
                        'type' => 'select' ,
                        'items' => array()
                    ) ;
                    foreach ($listProposalHow as $proposalHow)
                    {
                        $this->interface['listProposalHow']['items'][] = array (
                            'description' => $proposalHow
                        );
                    }
                    $this->interface['senateMakeProposal'] = array (
                        'type' => 'button' ,
                        'disabled' => TRUE ,
                        'verb' => 'senateMakeProposal' ,
                        'text' => _('MAKE PROPOSAL')
                    ) ;

                    /**
                    * The proposal's content, specific to the subPhase. This should include :
                    * - A description in the header
                    * - An interface proposalType
                    **/
                    
                    $this->setContent($game) ;
                }
                
                /**
                * This user cannot make a proposal at this point in time
                **/
                else
                {
                    $this->header['list'] = array (
                        _('You have no way to make a proposal at the moment') ,
                        _('Waiting for player who do')
                    ) ;
                }
            }
        }
    }
    
    /**
    * Function to add classes and attributes to cards
    * Not used at the moment, as proposals will be done by drop-downs
    **/
    
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
         * Goes through the collection and checks if the target card's ID matches one of the cardsToApplyItTo
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
     * Sets the senateMakeProposal interface based on the subPhase :
     * Consuls , DictatorAppointment , Prosecutions ...
     * @param \Entities\Game $game
     * @return array
     */
    public function setContent($game)
    {
        /**
        * Consuls
        */
        if ($game->getSubPhase()=='Consuls')
        {
            $this->header['list'] = array (
                _('Consuls Election') ,
                _('You must select a pair of Senators') ,
                _('Only Senators aligned in Rome can be proposed') ,
                _('Among officials, only the Censor and Master of Horse can be proposed') ,
                _('Already rejected pairs cannot be proposed again')
            ) ;
            $this->interface['proposalType']= 'Consuls' ;
            $possibleValues = array() ;
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleConsul') as $senator)
            {
                $possibleValues[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'senatorID' => $senator->getSenatorID()
                ) ;
            }
            $this->interface['items']= array
            (
                array (
                    'description' => _('First Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'First Senator',
                        'items' => $possibleValues 
                    )
                ) ,
                array (
                    'description' => _('Second Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'Second Senator',
                        'items' => $possibleValues 
                    )
                )
            ) ;
        }
    }
    
    /**
     * Returns an array of how the current user can make a proposal : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , FREE TRIBUNE FROM X , TRIBUNE CARD
     * @param \Entities\Game $game
     * @return array
     */
    public function getProposalHow($game)
    {
    // President
    // Prosecutions : The Censor can propose
    // Dictator appointment : Only consuls can appoint
    // Tribune Cards
    // Free tribunes from Satesmen
        $result = array() ;
        /**
        * Censor during prosecutions
        * This is exculsive (will be the only result returned)
        **/
        if ($game->getSubPhase() == 'Prosecutions')
        {
            $searchResult = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'isCensor') ;
            if (count($searchResult)==1 && $searchResult->first()->getUser_id() == $this->user_id)
            {
                return array('CENSOR');
            }
            else
            {
                return array() ;
            }
        }
        /**
        * Consuls appointing a Dictator
        * This is exculsive (will be the only result returned)
        **/
        if ($game->getSubPhase() == 'DictatorAppointment')
        {
            $searchResult = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'canAppointDictator') ;
            if (count($searchResult)==1 && $searchResult->first()->getUser_id() == $this->user_id)
            {
                return array('DICTATOR APPOINTMENT');
            }
            else
            {
                return array() ;
            }
        }
        /**
        * Other means of proposals : President, tribune card, free tribune
        * This is not exclusive (several results can be returned)
        **/
        if ($game->getHRAO(TRUE)->getLocation()['value']->getUser_id() == $this->user_id)
        {
            $result[] = 'PRESIDENT' ;
        }
        $result = array_merge($result, $this->getFreeTribunes($game->getParty($this->user_id))) ;
        $result = array_merge($result, $this->getCardTribunes($game->getParty($this->user_id))) ;
        return $result ;
    }
    
    /**
    * Returns a list of free tribunes provided by Satesmen special abilities
    **/
    public function getFreeTribunes($party)
    {
        $result = array() ;
        foreach ($party->getSenators()->getCards() as $senator)
        {
            if ($senator->getFreeTribune() == 1)
            {
                $result[] = 'FREE TRIBUNE FROM '.$senator->getName() ;
            }
        }
        return $result ;
    }

    /**
    * Returns a list of tribune cards for this party
    **/
    public function getCardTribunes($party)
    {
        $result = array() ;
        foreach ($party->getHand()->getCards() as $card)
        {
            if ($card->getName()=='TRIBUNE')
            {
                $result[] = 'TRIBUNE CARD' ;
            }
        }
        return $result ;
    }

}