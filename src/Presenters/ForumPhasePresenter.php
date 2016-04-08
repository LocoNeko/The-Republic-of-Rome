<?php
namespace Presenters ;

class ForumPhasePresenter
{
    private $user_id ;
    private $phase ;
    private $subPhase ;
    private $initiative ;
    private $partyWithInitiative ;
    private $persuasionTarget ;
    private $targetList ;
    private $persuaderList ;
    private $persuasionCards ;

    /**
     * 
     * @param \Entities\Game $game
     */
    public function __construct($game , $user_id) {
        $this->setUser_id($user_id) ;
        $this->setPhase($game->getPhase()) ;
        $this->setSubPhase($game->getSubPhase()) ;
        $this->setInitiative($game->getInitiative()) ;
        // PartyWithInitiative is set here
        $this->setPartyWithInitiative(FALSE) ;
        // One of the automatic initiatives
        if ($game->getInitiative() <= $game->getNumberOfPlayers())
        {
            $this->setPartyWithInitiative($game->getOrderOfPlay()[$game->getInitiative()-1]) ;
        }
        else
        // One of the initiatives up for bidding
        {
            foreach ($game->getParties() as $party)
            {
                if ($party->getInitiativeWon())
                {
                    $this->setPartyWithInitiative($party) ;
                }
            }
        }
        $this->setPersuasionTarget($game->getPersuasionTarget()) ;
        $this->setTargetList($game) ;
        $this->setPersuaderList($game) ;
        $this->setPersuasionCards($game) ;
    }
    
    public function setUser_id($user_id) { $this->user_id = $user_id; }
    public function setPhase($phase) { $this->phase = $phase;}
    public function setSubPhase($subPhase) { $this->subPhase = $subPhase;}
    public function setInitiative($initiative) { $this->initiative = $initiative; }
    public function setPartyWithInitiative($partyWithInitiative) { $this->partyWithInitiative = $partyWithInitiative; } 
    public function setPersuasionTarget($persuasionTarget) { $this->persuasionTarget = $persuasionTarget; }

    public function getUser_id() { return $this->user_id; }
    public function getPhase() { return $this->phase; }
    public function getSubPhase() { return $this->subPhase; }
    public function getInitiative() { return $this->initiative; }
    public function getPartyWithInitiative() { return $this->partyWithInitiative; }
    public function getPersuasionTarget() { return $this->persuasionTarget; }
    public function getTargetList() { return $this->targetList; }
    public function getPersuaderList() { return $this->persuaderList; }
    public function getPersuasionCards() { return $this->persuasionCards; }

    public function getHeader()
    {
        $result = array() ;
        $result['list'] = array() ;
        $result['action'] = array () ;
        $result['initiativeDescription'] = _('Initiative #').$this->getInitiative().$this->getInitiativeDescription($this->getUser_id()) ;
        /*
         * Initiative doesn't belong to any Party - bidding
         */
        if ($this->getPartyWithInitiative()===FALSE)
        {
            $result['description'] = _('Bidding for initiative') ;
        }
        else
        /*
         * Initiative belongs to a party
         */
        {
            /*
             * Roll events
             */
            if ($this->getPhase()=='Forum' && $this->getSubPhase()=='RollEvent')
            {
                $result['description'] = _('Rolling event') ;
                // Has the initative - rolls
                if ($this->getPartyWithInitiative()->getUser_id() == $this->getUser_id())
                {
                    $result['list'][] = _('In case a 7 is rolled, another roll will be made on the events table');
                    $result['list'][] = _('In case a 7 is not rolled, a card will be drawn and put in the hand or a deck, depending on its type');
                    $result['action'] = array (
                        'type' => 'button' ,
                        'verb' => 'RollEvent' ,
                        'text' => 'ROLL' ,
                        'user_id' => $this->getUser_id()
                    );
                }
                // Ain't got the initative - waits
                else
                {
                    $result['description'] = _('Waiting for event roll.') ;
                }
            }
            /*
             * Persuasion
             */
            elseif ($this->getPhase()=='Forum' && $this->getSubPhase()=='Persuasion')
            {
                $result['description'] = _('Persuasion') ;
                /*
                 * - If there is no target : the player with the initiative can pick one (with a persuader, an optional bribe, and an optional card) or pass
                 * - If there is a target :
                 *   - If the next available player is not done : Give a chance for the next available player to counter-bribe
                 *   - If all available players are done : The player with the initiative can roll or bribe more
                 */
                if ($this->getPersuasionTarget()===NULL)
                {
                    // Has the initative - Can persuade
                    if ($this->getPartyWithInitiative()->getUser_id() == $this->getUser_id())
                    {
                        $result['action'] = array (
                            'type' => 'button' ,
                            'verb' => 'NoPersuasion' ,
                            'text' => 'NO PERSUASION' ,
                            'user_id' => $this->getUser_id()
                        );
                    }
                    // Ain't got the initative - waits
                    else
                    {
                        $result['description'] = _('Waiting for persuasion') ;
                    }
                }
            }
            else
            {
                $result['description'] = _('Phase : TO DO...') ;
            }
        }

        return $result;
    }

    public function getContent()
    {
        $result = array() ;
        // By default, the interface should not be shown (could replace this by count(content)===0)
        $result['interface'] = FALSE ;
        /*
         * - Phase : Forum
         * - SubPhase : Persuasion
         * - Has initiative
         * - Persuasion target : NULL
         * The player with the initiative can pick one (with a persuader, an optional bribe, and an optional card)
         * Give a list of Persuaders, Persuadees, Cards
         */
        if ($this->getPartyWithInitiative()->getUser_id() == $this->getUser_id() && $this->getPhase()=='Forum' && $this->getSubPhase()=='Persuasion' && $this->getPersuasionTarget()===NULL)
        {
            $result['interface'] = 'pickTarget' ;
            $result['targetList'] = array (
                'type' => 'select' ,
                'class' => 'persuasionTargetList' ,
                'items' => $this->getTargetList()
            ) ;
            $result['persuaderList'] = array (
                'type' => 'select' ,
                'class' => 'persuasionPersuaderList' ,
                'items' => $this->getPersuaderList()
            ) ;
            $result['bribe'] = array (
                'type' => 'select' ,
                'class' => 'persuasionBribe' ,
                'items' => array(
                    array('value'=>0 , 'description' => '0')
                )
            ) ;
            $result['card'] = array (
                'type' => 'select' ,
                'class' => 'persuasionCard' ,
                'items' => $this->getPersuasionCards()
            ) ;
            $result['action'] = array (
                'type' => 'button' ,
                'verb' => 'doPersuasion' ,
                'text' => 'PERSUADE' ,
                'user_id' => $this->getUser_id() ,
                'disabled' => TRUE
            ) ;
        }
        return $result ;
    }

    /**
     * Returns a description of the current state of the initiative
     * @param int $user_id A user_id
     * @return string "Up for bidding"<br>"You"<br>"{{Party->fullName()}}"
     */
    public function getInitiativeDescription($user_id)
    {
        $partyWithInitiative = $this->getPartyWithInitiative() ;
        if ($partyWithInitiative === FALSE)
        {
            return _(' is up for bidding');
        }
        else
        {
            return _(' belongs to ').(($user_id==$partyWithInitiative->getUser_id()) ? _('you') : $partyWithInitiative->getFullName());
        }
    }
    
    /**
     * Initialises $this->targetList[] array<br>
     * Each element is an array : 'senatorID' , 'user_id' , 'LOY' , 'treasury' , 'description'
     * @param \Entities\Game $game
     */
    public function setTargetList($game)
    {
        $this->targetList = array() ;
        $this->targetList[] = array ( 'value' => NULL , 'user_id' => NULL , 'LOY' => NULL , 'treasury' => NULL , 'description' => 'NONE') ;

        // Put all Senators from another party, not leader, in Rome
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() != $this->getUser_id())
            {
                foreach ($party->getSenators()->getCards() as $senator)
                {
                    if ($senator->inRome() && !$senator->isLeader())
                    {
                        $this->targetList[] = array (
                            'value' => $senator->getSenatorID() ,
                            'user_id' => $party->getUser_id() ,
                            'LOY' => $senator->getActualLOY($game) ,
                            'treasury' => $senator->getTreasury() ,
                            'description' => $senator->getName().' in '.$party->getFullName().' - Loyalty: '.$senator->getActualLOY($game)
                        ) ;
                    }
                }
            }
        }
        // Put all Senators in the Forum
        foreach ($game->getDeck('forum')->getCards() as $card)
        {
            if ($card->getIsSenatorOrStatesman())
            {
                $this->targetList[] = array (
                    'value' => $senator->getSenatorID() ,
                    'user_id' => NULL ,
                    'LOY' => $senator->getActualLOY($game) ,
                    'treasury' => $senator->getTreasury() ,
                    'description' => $senator->getName().' in the Forum - Loyalty: '.$senator->getActualLOY($game)
                ) ;
            }
        }
    }

    /**
     * Initialises $this->persuaderList[] array<br>
     * Each element is an array : 'senatorID' , 'ORA' , 'INF' , 'treasury' , 'description'
     * @param \Entities\Game $game
     */
    public function setPersuaderList($game)
    {
        $this->persuaderList = array() ;
        $this->persuaderList [] = array ( 'value' => NULL , 'ORA' => NULL , 'INF' => NULL , 'treasury' => NULL , 'description' => 'NONE') ;

        foreach($game->getParty($this->getUser_id())->getSenators()->getCards() as $senator)
        {
            if ($senator->inRome())
            {
                $ORA = $senator->getORA() ;
                $INF = $senator->getINF() ;
                $treasury = $senator->getTreasury() ;
                $this->persuaderList[] = array (
                    'value' => $senator->getSenatorID() ,
                    'ORA' => $ORA ,
                    'INF' => $INF ,
                    'treasury' => $treasury ,
                    'description' => $senator->getName().' - ORA('.$ORA.') + INF('.$INF.') = '.($ORA+$INF).' ('.($ORA+$INF+$treasury).' with treasury)'
                ) ;
            }
        }
    }

    /**
     * Initialises $this->persuasionCards
     * @param \Entities\Game $game
     */
    public function setPersuasionCards($game)
    {
        $this->persuasionCards = array() ;
        $this->persuasionCards [] = array ( 'value' => NULL , 'description' => 'NONE') ;

        foreach ($game->getParty($this->getUser_id())->getHand()->getCards() as $card)
        {
            if (($card->getName()=='SEDUCTION') || ($card->getName()=='BLACKMAIL')) {
                $this->persuasionCards[] = array (
                    'value' => $card->getId() ,
                    'description' => $card->getName()
                );
            }
        }
    }

}
