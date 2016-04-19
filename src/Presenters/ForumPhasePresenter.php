<?php
namespace Presenters ;

class ForumPhasePresenter
{
    // Common to all phase presenters
    public $user_id ;
    public $game ;
    public $yourParty ;
    public $otherParties = [] ;
    public $header = [] ;
    public $interface = [] ;
    public $sliders = [] ;

    // Specific to Forum
    public $initiative ;
    public $idWithInitiative ;
    public $hasInitiative ;
    
    /**
     * 
     * @param \Entities\Game $game
     * @param int $user_id
     */
    public function __construct($game , $user_id) {
        /**
         * Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id ;
        $this->game =  new \Presenters\GamePresenterNew($game , $user_id) ;
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id)
            {
                $this->yourParty = new \Presenters\PartyPresenter($party, $user_id) ;
            }
            else
            {
                $this->otherParties[$party->getUser_id()] = new \Presenters\PartyPresenter($party, $user_id) ;
            }
        }
        /**
         * Phase -specific
         */
        $this->initiative = $game->getInitiative() ;
        $this->setIdWithInitiative($game) ;
        $this->hasInitiative = ($this->idWithInitiative===$user_id) ;
        /**
         * Phase Header
         */
        $this->header['list'] = array() ;
        $this->header['actions'] = array () ;
        $this->header['description'] = _('Initiative #').$this->initiative.$this->getInitiativeDescription($user_id , $game).' - ' ;
        if ($game->getPhase()!='Forum')
        {
            $this->header['description'] .= _('ERROR - Wrong phase') ;
        }
        /**
         * Initiative bidding
         */
        elseif ($this->idWithInitiative===FALSE)
        {
            $this->setInitiativeBidding() ;
        }
        /**
         * Roll event
         */
        elseif ($game->getSubPhase()=='RollEvent' && $this->hasInitiative)
        {
            $this->header['description'] .= _('Rolling event') ;
            $this->setRollEventInitiative() ;
        }
        elseif ($game->getSubPhase()=='RollEvent' && !$this->hasInitiative)
        {
            $this->header['description'] .= _('Waiting for event roll.') ;
        }
        /**
         * Persuasion - Pick Target
         */
        elseif ($game->getSubPhase()=='Persuasion' && $game->getPersuasionTarget()===NULL && $this->hasInitiative)
        {
            $this->header['description'] .= _('Persuasion') ;
            $this->setPersuasionPickTargetInitiative($game) ;
        }
        elseif ($game->getSubPhase()=='Persuasion' && $game->getPersuasionTarget()===NULL && !$this->hasInitiative)
        {
            $this->header['description'] .= _('Waiting for persuasion') ;
        }
        /**
         * Persuasion - Bribe, Counter-bribe or Roll
         */
        elseif ($game->getSubPhase()=='Persuasion' && $game->getPersuasionTarget()!==NULL && $this->hasInitiative && $this->getFirstIsNotDoneUserId($game)==FALSE)
        {
            $this->header['description'] .= _('Bribe or Roll') ;
            $this->header['details'] = $this->getPersuasionDescription($game) ;
            $this->setPersuasionBribeOrRoll($game) ;
        }
        elseif ($game->getSubPhase()=='Persuasion' && $game->getPersuasionTarget()!==NULL && !$this->hasInitiative && $this->getFirstIsNotDoneUserId($game)==$user_id)
        {
            $this->header['description'] .= _('Counter-bribe') ;
            $this->header['details'] = $this->getPersuasionDescription($game) ;
            $this->setPersuasionCounterBribe($game) ;
        }
        /**
         * Persuasion - Wait for Bribe, Counter-bribe or Roll
         */
        elseif ($game->getSubPhase()=='Persuasion' && $game->getPersuasionTarget()!==NULL && ($this->hasInitiative || (!$this->hasInitiative && $this->getFirstIsNotDoneUserId($game)!==$user_id)))
        {
            $this->header['description'] .= _('Waiting for Counter-bribe')  ;
            $this->header['details'] = $this->getPersuasionDescription($game) ;
        }
        elseif ($game->getSubPhase()=='Persuasion' && $game->getPersuasionTarget()!==NULL)
        {
            $this->header['description'] .= _('Waiting for persuader to roll or bribe more') ;
            $this->header['details'] = $this->getPersuasionDescription($game) ;
        }
        /**
         * Knights
         */
        elseif ($game->getSubPhase()=='Knights' && $this->hasInitiative)
        {
            $this->header['description'] .= _('Attracting or pressuring Knights') ;
            $this->setKnightsInitiative($game) ;
        }
        elseif ($game->getSubPhase()=='Knights' && !$this->hasInitiative)
        {
            $this->header['description'] .= _('Waiting for Knights') ;
        }
        /**
         * Games
         */
        elseif ($game->getSubPhase()=='Games' && $this->hasInitiative)
        {
            $this->header['description'] .= _('Sponsoring Games') ;
            $this->setGamesInitiative($game) ;
        }
        elseif ($game->getSubPhase()=='Games' && !$this->hasInitiative)
        {
            $this->header['description'] .= _('Waiting for sponsoring of Games') ;
        }
        /**
         * Change Leader
         */
        elseif ($game->getSubPhase()=='ChangeLeader' && $this->hasInitiative)
        {
            $this->header['description'] .= _('Change leader') ;
            $this->setChangeLeaderInitiative($game) ;
        }
        elseif ($game->getSubPhase()=='ChangeLeader' && !$this->hasInitiative)
        {
            $this->header['description'] .= _('Waiting for leader change') ;
        }
    }

    /**
     * Set the idWithInitiatve to a user_id or FALSE if the initiative is up for bidding
     * @param \Entities\Game $game
     */
    public function setIdWithInitiative($game)
    {
        $this->idWithInitiative = FALSE ;
        // One of the automatic initiatives
        if ($game->getInitiative() <= $game->getNumberOfPlayers())
        {
            $this->idWithInitiative = $game->getOrderOfPlay()[$game->getInitiative()-1]->getUser_id() ;
        }
        else
        // One of the initiatives up for bidding
        {
            foreach ($game->getParties() as $party)
            {
                if ($party->getInitiativeWon())
                {
                    $this->idWithInitiative = $party->getUser_id();
                }
            }
        }
    }
    
    /**
     * Returns a description of the current state of the initiative
     * @param int $user_id A user_id
     * @param \Entities\Game $game
     * @return string "Up for bidding"<br>"You"<br>"{{Party->fullName()}}"
     */
    public function getInitiativeDescription($user_id , $game)
    {
        if ($this->idWithInitiative === FALSE)
        {
            return _(' is up for bidding');
        }
        else
        {
            return _(' belongs to ').(($user_id==$this->idWithInitiative) ? _('you') : $game->getParty($this->idWithInitiative)->getFullName());
        }
    }
    
    /**
     *
     * Functions taken out of the constructor for readibility
     * Setting of optional elements :
     * - Header list
     * - Header action
     * - Interface
     *  
     */
    
    public function setInitiativeBidding()
    {
        
    }
    
    public function setRollEventInitiative()
    {
        $this->header['list'][] = _('In case a 7 is rolled, another roll will be made on the events table');
        $this->header['list'][] = _('In case a 7 is not rolled, a card will be drawn and put in the hand or a deck, depending on its type');
        $this->header['actions'] = array (
            array (
                'type' => 'button' ,
                'verb' => 'RollEvent' ,
                'text' => 'ROLL' ,
                'user_id' => $this->user_id
            )
        );
    }

    /**
     * - Persuasion
     * - Pick Target
     * @param \Entities\Game $game
     */
    public function setPersuasionPickTargetInitiative($game)
    {
        $this->interface['name'] = 'persuasionPickTarget';
        $this->interface['targetList'] = array (
            'type' => 'select' ,
            'class' => 'persuasionTargetList' ,
            'items' => $this->getTargetList($game)
        ) ;
        $this->interface['persuaderList'] = array (
            'type' => 'select' ,
            'class' => 'persuasionPersuaderList' ,
            'items' => $this->getPersuaderList($game)
        ) ;
        $this->interface['bribe'] = array (
            'type' => 'select' ,
            'class' => 'persuasionBribe' ,
            'items' => array(
                array('value'=>0 , 'description' => '0')
            )
        ) ;
        $this->interface['card'] = array (
            'type' => 'select' ,
            'class' => 'persuasionCard' ,
            'items' => $this->getPersuasionCards($game)
        ) ;
        $this->interface['noPersuasion'] = array (
            'type' => 'submitWithVerb' ,
            'verb' => 'noPersuasion' ,
            'text' => _('NO PERSUASION') ,
            'style' => 'danger' ,
            'user_id' => $this->user_id
        );
        $this->interface['persuade'] = array (
            'type' => 'submitWithVerb' ,
            'verb' => 'persuasionPickTarget' ,
            'text' => _('PERSUADE') ,
            'disabled' => TRUE ,
            'user_id' => $this->user_id
        );
        $this->interface['hideOdds'] = $this->game->getVariantFlag('Hide odds') ;
    }
    
    /**
     * - Persuasion
     * - Bribe or Roll
     * @param \Entities\Game $game
     */
    public function setPersuasionBribeOrRoll($game)
    {
        $this->interface['name'] = 'persuasionBribeOrRoll' ;
        $items = array() ;
        for ($i=0 ; $i<=$this->getPersuaderAvailableTreasury($game) ; $i++)
        {
            $items[] = array('value'=>$i , 'description' => $i) ;
        }
        $this->interface['persuasionAddedBribe'] = array (
            'type' => 'select' ,
            'class' => 'persuasionAddedBribe' ,
            'items' => $items
        ) ;
        $this->interface['persuasionBribeMore'] = array (
            'type' => 'submitWithVerb' ,
            'verb' => 'persuasionBribeMore' ,
            'text' => _('BRIBE MORE') ,
            'disabled' => TRUE ,
            'user_id' => $this->user_id
        );
        $this->interface['persuasionRoll'] = array (
            'type' => 'submitWithVerb' ,
            'verb' => 'persuasionRoll' ,
            'text' => _('ROLL') ,
            'style' => 'danger' ,
            'user_id' => $this->user_id
        );
    }
    
    /**
     * - Persuasion
     * - Counter-bribe
     * @param \Entities\Game $game
     */
    public function setPersuasionCounterBribe($game)
    {
        $this->interface['name'] = 'persuasionCounterBribe' ;
        $items = array() ;
        for ($i=0 ; $i<=$this->getNonPersuaderAvailableTreasury($game) ; $i++)
        {
            $items[] = array('value'=>$i , 'description' => $i) ;
        }
        $this->interface['counterBribe'] = array (
            'type' => 'select' ,
            'class' => 'persuasionCounterBribeAmount' ,
            'items' => $items
        ) ;
        $this->interface['persuasionNoCounterBribe'] = array (
            'type' => 'submitWithVerb' ,
            'verb' => 'persuasionNoCounterBribe' ,
            'text' => _('NO COUNTER BRIBE') ,
            'style' => 'danger' ,
            'user_id' => $this->user_id
        );
        $this->interface['persuasionCounterBribe'] = array (
            'type' => 'submitWithVerb' ,
            'verb' => 'persuasionCounterBribe' ,
            'text' => _('COUNTER BRIBE') ,
            'style' => 'success' ,
            'disabled' => TRUE ,
            'user_id' => $this->user_id
        );
    }

    /**
     * - Knights
     * - Set interface on cards :
     * - If the user has already pressured knights, only set 'pressure'
     * - If the user has not yet pressured knights, set both 'pressure' and 'attract' 
     * @param \Entities\Game $game
     */
    public function setKnightsInitiative($game)
    {
        // Only offer the option to attract knights if none have been pressured yet
        if ($game->getParty($this->idWithInitiative)->getIsDone() === FALSE)
        {
            $this->header['list'][] = _('You can attract knights by rolling 6 on one die, adding 1 for every talent spent');
        }
        $this->header['list'][] = _('You can pressure existing knights to gain 1d talent per knight pressured');
        $this->header['actions'] = array (
            array (
                'type' => 'button' ,
                'verb' => 'noKnights' ,
                'text' => 'DONE'
            )
        );
        $this->sliders[] = array (
            'ID' => 'KnightsAttractModal' ,
            'title' => _('Attract knights'),
            'verb' => 'forumKnightsAttract',
            'text' => 'ATTRACT KNIGHT'
        ) ;
        $this->sliders[] = array (
            'ID' => 'KnightsPressureModal' ,
            'title' => _('Pressure knights'),
            'verb' => 'forumKnightsPressure',
            'text' => 'PRESSURE KNIGHTS'
        ) ;
        foreach ($this->yourParty->senators as $senatorID=>$senator)
        {
            /**
             * Get the corresponding Senator Model (entity)
             * @var \Entities\Senator $senatorModel
             */
            $senatorModel = $game->getFilteredCards(array('SenatorID' => $senatorID))->first() ;
            /**
             * Add treasury & knights attributes to the Senators
             * Those attributes are needed for the modal slider
             */
            $senator->addAttribute('treasury' , $senatorModel->getTreasury()) ;
            $senator->addAttribute('knights' , $senatorModel->getKnights()) ;
            /**
             * Can't attract knights if some have already been pressured
             */
            if (!$game->getParty($this->user_id)->getIsDone()) {
                $senator->addMenuItem(
                    array (
                        'style' => 'primary' ,
                        'disabled' => FALSE ,
                        'verb' => 'forumKnightsAttract' ,
                        'text' => _('Attract a knight') ,
                        'classes' => array (
                            'forumKnightsAttract'
                        ) ,
                        'attributes' => array (
                            'data-json'=> '{"action":["slider" , "KnightsAttractModal" , "'.$senatorModel->getName().' spends talents" , "0" , "'.$senatorModel->getTreasury().'" , "T." ]}'
                        )
                    )
                );
            }
            /**
             * Can't pressure knights if there isn't any
             */
            if ($senatorModel->getKnights() > 0)
            {
                $senator->addMenuItem(
                    array (
                        'style' => 'danger' ,
                        'disabled' => FALSE ,
                        'verb' => 'forumKnightsPressure' ,
                        'text' => _('Pressure Knights') ,
                        'classes' => array (
                            'forumKnightsPressure'
                        )
                    )
                );
            }
        }
    }
    
    /**
     * - Games
     * Set interface on cards
     * @param \Entities\Game $game
     */
    public function setGamesInitiative($game)
    {
        $this->header['list'][] = _('Slice & Dice : 7T , +1 POP , -1 Unrest') ;
        $this->header['list'][] = _('Blood fest : 13T , +2 POP , -2 Unrest') ;
        $this->header['list'][] = _('Gladiator gala : 18T , +3 POP , -3 Unrest') ;
        $this->header['actions'] = array (
            array (
                'type' => 'button' ,
                'verb' => 'noGames' ,
                'text' => 'DONE'
            )
        );
        foreach ($this->yourParty->senators as $senatorID=>$senator)
        {
            /**
             * Get the corresponding Senator Model (entity)
             * @var \Entities\Senator $senatorModel
             */
            $senatorModel = $game->getFilteredCards(array('SenatorID' => $senatorID))->first() ;
            if ($senatorModel->getTreasury()>=7)
            {
                $senator->addMenuItem(
                    array (
                        'style' => 'primary' ,
                        'disabled' => FALSE ,
                        'verb' => 'forumGames' ,
                        'text' => _('Slice & Dice') ,
                        'attributes' => array (
                            'data-json'=> '{"action":["fixedAmount" , "7"]}'
                        )
                    )
                );
            }
            if ($senatorModel->getTreasury()>=13)
            {
                $senator->addMenuItem(
                    array (
                        'style' => 'primary' ,
                        'disabled' => FALSE ,
                        'verb' => 'forumGames' ,
                        'text' => _('Blood fest') ,
                        'attributes' => array (
                            'data-json'=> '{"action":["fixedAmount" , "13"]}'
                        )
                    )
                );
            }
            if ($senatorModel->getTreasury()>=18)
            {
                $senator->addMenuItem(
                    array (
                        'style' => 'primary' ,
                        'disabled' => FALSE ,
                        'verb' => 'forumGames' ,
                        'text' => _('Gladiator gala') ,
                        'attributes' => array (
                            'data-json'=> '{"action":["fixedAmount" , "18"]}'
                        )
                    )
                );
            }
        }
    }
    
    /**
     * - Change leader
     * @param \Entities\Game $game
     */
    public function setChangeLeaderInitiative($game)
    {
        $this->header['list'][] = _('Drag and drop the leader token on one of your Senators to make him party leader.') ;
        $this->header['actions'] = array (
            array (
                'type' => 'icon' ,
                'verb' => 'forumChangeLeader' ,
                'text' => ' Leader' ,
                'caption' => 'Drag and drop on top of a Senator to make him Party leader'
            ) ,
            array (
                'type' => 'button' ,
                'verb' => 'noChangeLeader' ,
                'text' => 'DONE'
            )
        );
        // droppable : add the droppable to all Senators that can be leadera
        foreach ($this->yourParty->senators as $senatorID=>$senator)
        {
            /**
             * Get the corresponding Senator Model (entity)
             * @var \Entities\Senator $senatorModel
             */
            $senatorModel = $game->getFilteredCards(array('SenatorID' => $senatorID))->first() ;
            if (!$senatorModel->isLeader())
            {
                $senator->addClass('droppable') ;
            }

        }
    }
    
    /**
     * ===========================================================================
     * =========================      SUB-FUNCTIONS      =========================
     * ===========================================================================
     */
    
    /**
     * - Persuasion
     * - Pick Target
     * - Get a list of persuasion targets
     * Each element is an array : 'senatorID' , 'user_id' , 'LOY' , 'treasury' , 'description'
     * @param \Entities\Game $game
     * @return array
     */
    public function getTargetList($game)
    {
        $result = array() ;
        $result[] = array ( 'value' => NULL , 'user_id' => NULL , 'LOY' => NULL , 'treasury' => NULL , 'description' => 'NONE') ;

        // Put all Senators from another party, not leader, in Rome
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() != $this->user_id)
            {
                foreach ($party->getSenators()->getCards() as $senator)
                {
                    if ($senator->inRome() && !$senator->isLeader())
                    {
                        $result[] = array (
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
                $result[] = array (
                    'value' => $card->getSenatorID() ,
                    'user_id' => NULL ,
                    'LOY' => $card->getActualLOY($game) ,
                    'treasury' => $card->getTreasury() ,
                    'description' => $card->getName().' in the Forum - Loyalty: '.$card->getActualLOY($game)
                ) ;
            }
        }
        return $result ;
    }
    
    /**
     * - Persuasion
     * - Pick Target
     * - Gets a list of potential persuaders
     * Each element is an array : 'senatorID' , 'ORA' , 'INF' , 'treasury' , 'description'
     * @param \Entities\Game $game
     * @return array
     */
    public function getPersuaderList($game)
    {
        $result = array() ;
        $result[] = array ( 'value' => NULL , 'ORA' => NULL , 'INF' => NULL , 'treasury' => NULL , 'description' => 'NONE') ;

        foreach($game->getParty($this->user_id)->getSenators()->getCards() as $senator)
        {
            if ($senator->inRome())
            {
                $ORA = $senator->getORA() ;
                $INF = $senator->getINF() ;
                $treasury = $senator->getTreasury() ;
                $result[] = array (
                    'value' => $senator->getSenatorID() ,
                    'ORA' => $ORA ,
                    'INF' => $INF ,
                    'treasury' => $treasury ,
                    'description' => $senator->getName().' - ORA('.$ORA.') + INF('.$INF.') = '.($ORA+$INF).' ('.($ORA+$INF+$treasury).' with treasury)'
                ) ;
            }
        }
        return $result ;
    }
    
    /**
     * - Persuasion
     * - Pick Target
     * - Gets a list of persuasion cards
     * @param \Entities\Game $game
     */
    public function getPersuasionCards($game)
    {
        $result = array() ;
        $result [] = array ( 'value' => NULL , 'description' => 'NONE') ;

        foreach ($game->getParty($this->user_id)->getHand()->getCards() as $card)
        {
            if (($card->getName()=='SEDUCTION') || ($card->getName()=='BLACKMAIL')) {
                $result[] = array (
                    'value' => $card->getId() ,
                    'description' => $card->getName()
                );
            }
        }
        return $result ;
    }

    /**
     * Returns the ID of the first party who is not done (isDone==FALSE) or FALSE if everyone is done
     * @param \Entities\Game $game
     * @return user_id|boolean
     */
    public function getFirstIsNotDoneUserId($game)
    {
        foreach ($game->getOrderOfPlay() as $party)
        {
            if (!$party->getIsDone())
            {
                return $party->getUser_id() ;
            }
        }
        return FALSE ;
    }

    /**
     * @param \Entities\Game $game
     * @return int
     */
    public function getPersuaderBribes($game)
    {
        try
        {
            return $game->getParty($this->user_id)->getBid() ;
        }
        catch (Exception $e)
        {
            return 0 ;
        }
    }
    
    /**
     * @param \Entities\Game $game
     * @return int
     */
    public function getPersuaderAvailableTreasury($game)
    {
        try
        {
            return $game->getParty($this->user_id)->getBidWith()->getTreasury() - $this->getPersuaderBribes($game) ;
        }
        catch (Exception $e)
        {
            return 0 ;
        }
    }
    
    /**
     * @param \Entities\Game $game
     * @return int
     */
    public function getNonPersuaderAvailableTreasury($game)
    {
        try
        {
            return $game->getParty($this->user_id)->getTreasury() - $game->getParty($this->user_id)->getBid() ;
        }
        catch (Exception $e)
        {
            return 0 ;
        }
    }

    /**
     * @param \Entities\Game $game
     * @return string
     */
    public function getPersuasionDescription($game)
    {
        $persuader = $game->getParty($this->idWithInitiative)->getBidWith() ;
        $target = $game->getPersuasionTarget() ;
        $bribes =$game->getParty($this->idWithInitiative)->getBid() ;
        // Counter bribes
        $counterBribesDescription = _('') ;
        $counterBribes = 0 ;
        foreach ($game->getParties() as $party)
        {
            if ( ($party->getUser_id() != $this->idWithInitiative))
            {
                // Counter bribes totals
                if ($party->getBid()>0)
                {
                    $counterBribesDescription.=sprintf(_('%1$s with %2$dT, ') , $party->getFullName() , $party->getBid()) ;
                    $counterBribes+=$party->getBid() ;
                }
            }
        }
        if (strlen($counterBribesDescription)>0)
        {
            $counterBribesDescription=_(' Counter bribes : ').substr($counterBribesDescription,0,-2).'.';
        }
        // Odds for : INF + ORA + bribes
        // Odds against : LOY + treasury + counter bribes
        
        $for = $persuader->getINF() + $persuader->getORA() + $bribes ;
        $against = $target->getActualLOY($game) + $target->getTreasury() + $counterBribes ;
        return sprintf( _('%1$s is persuading %2$s, spending %3$d in bribes.%4$s Totals are %5$d for and %6$d against.') , $persuader->getName() , $target->getName() , $bribes , $counterBribesDescription , $for , $against ) ;
    }
}
