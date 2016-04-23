<?php
namespace Presenters ;

class RevenuePhasePresenter
{
    // Common to all phase presenters
    public $user_id ;
    public $game ;
    public $yourParty ;
    public $otherParties = [] ;
    public $header = [] ;
    public $interface = [] ;
    public $sliders = [] ;

    // Specific to Revenue
    public $isDone = FALSE ;

    private $areThereReleasedLegions ;
    private $isPartyOfHRAO ;
    
    /**
     * @param \Entities\Game $game
     * @param int $user_id
     */
    public function __construct($game , $user_id)
    {
        /**
         * Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id ;
        $this->game =  new \Presenters\GamePresenter($game , $user_id) ;
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id)
            {
                $this->yourParty = new \Presenters\PartyPresenter($party, $user_id) ;
                $this->isDone = $party->getIsDone() ;
            }
            else
            {
                $this->otherParties[$party->getUser_id()] = new \Presenters\PartyPresenter($party, $user_id) ;
            }
        }
        
        /**
         * Phase Header
         */
        $this->header['list'] = array() ;
        $this->header['actions'] = array () ;
        if ($game->getPhase()!='Revenue')
        {
            $this->header['description'] .= _('ERROR - Wrong Phase') ;
        }
        
        /**
         * Base Revenue
         */
        elseif($game->getSubPhase()=='Base' && !$this->isDone)
        {
            $this->header['description'] = _('This is your base revenue :') ;
            $this->setBase($game) ;
        }
        elseif($game->getSubPhase()=='Base' && $this->isDone)
        {
            $this->header['description'] = _('You are done with revenue and are currently waiting for :') ;
            foreach ($game->getParties() as $party)
            {
                if ($party->getId() != $user_id && $party->getIsDone() == FALSE)
                {
                    $this->header['list'][] = $party->getFullName();
                }
            }
        }
        
        /**
         * Redistribution
         */
        elseif($game->getSubPhase()=='Redistribution' && !$this->isDone)
        {
            $this->setRedistribution($game) ;
        }
        elseif($game->getSubPhase()=='Redistribution' && $this->isDone)
        {
            $this->header['description'] = _('You are done with redistribution and are currently waiting for :') ;
            foreach ($game->getParties() as $party)
            {
                if ($party->getId() != $user_id && $party->getIsDone() == FALSE)
                {
                    $this->header['list'][] = $party->getFullName();
                }
            }
        }

        /**
         * Contributions
         */
        elseif($game->getSubPhase()=='Contributions' && !$this->isDone)
        {
            $this->setContributions($game) ;
        }
        elseif($game->getSubPhase()=='Contributions' && $this->isDone)
        {
            $this->header['description'] = _('You are done with contributions and are currently waiting for :') ;
            foreach ($game->getParties() as $party)
            {
                if ($party->getId() != $user_id && $party->getIsDone() == FALSE)
                {
                    $this->header['list'][] = $party->getFullName();
                }
            }
        }
        else
        {
            $this->header['description'] .= _('ERROR - Wrong Sub Phase') ;
        }
    }
    
    /**
     * 
     * @param \Entities\Game $game
     */
    public function setBase($game)
    {
        $this->interface['name'] = 'revenueBase';
        $this->interface['droughtLevel'] = $game->getEventProperty('name' , 'Drought') ;
        $this->interface['base'] = $game->getParty($this->user_id)->revenue_base($game->getLegions()) ;
        $this->header['actions'][] = array (
            'type' => 'button' ,
            'verb' => 'RevenueDone' ,
            'text' => 'DONE'
        );
    }
    
    /**
     * Taken out of the constructor for convenience and lisabilatility
     * @param \Entities\Game $game
     */
    public function setRedistribution ($game)
    {
        $this->header['description'] = _('You can redistribute money between your party, senators, and other parties') ;
        $this->header['list'] = array (
            _('Drag and drop to transfer money'),
            _('Tokens below : To/From treasury of your party and other players\' parties'),
            _('Senators : To/From this Senator')
        );
        $this->interface['name'] = 'revenueRedistribution';
        
        // The first action in the interface is "DONE"
        $this->interface['revenueRedistributionDone'] = array (
            'type' => 'button' ,
            'verb' => 'revenueRedistributionDone' ,
            'text' => 'DONE'
        );

        //TO DO - Release legions interface
        $this->interface['showReleasedLegions'] = FALSE ;

        // The RevenueRedistributeModal will pop up each time a "FROM" is dropped
        $this->sliders[] = array (
            'ID' => 'RevenueRedistributeModal' ,
            'title' => _('Redistribute'),
            'verb' => 'revenueRedistribute',
            'text' => 'TRANSFER'
        ) ;
        
        // The first potential "FROM" is the party treasury if greater than 0, it's also a "TO"
        if ($game->getParty($this->user_id)->getTreasury()>0)
        {
            $this->header['actions'][] = array (
                'type' => 'icon' ,
                'verb' => 'revenueRedistribute' ,
                'text' => ' '.$game->getParty($this->user_id)->getName().' ('.$game->getParty($this->user_id)->getTreasury().'T.)' ,
                'draggable' => 'YES' ,
                'droppable' => 'YES' ,
                'caption' => 'Drag and drop to transfer money to/from your party treasury' ,
                'data_json'=> '{"user_id":'.$this->user_id.' , "action":["slider" , "RevenueRedistributeModal" , "Transfer talents from party treasury" , "0" , "'.$game->getParty($this->user_id)->getTreasury().'" , "T." ]}'
            );
        }
        // if it's only a "TO", don't make it draggable
        else
        {
            $this->header['actions'][] = array (
                'type' => 'icon' ,
                'verb' => 'revenueRedistribute' ,
                'text' => ' '.$game->getParty($this->user_id)->getName().' ('.$game->getParty($this->user_id)->getTreasury().'T.)' ,
                'draggable' => 'NO' ,
                'droppable' => 'YES' ,
                'caption' => 'Drop here to transfer money to your party treasury' ,
                'data_json'=> '{"user_id":'.$this->user_id.'}'
            );
        }

        // All other parties are potential "TO"
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() != $this->user_id)
            {        
                $this->header['actions'][] = array (
                    'type' => 'icon' ,
                    'verb' => 'revenueRedistribute' ,
                    'text' => ' '.$party->getName() ,
                    'draggable' => 'NO' ,
                    'droppable' => 'YES' ,
                    'caption' => 'Drop here to transfer money to this party treasury' ,
                    'data_json'=> '{"user_id":'.$party->getUser_id().'}'
                );
            }
        }

        // Senators in the party are both potential "FROM" and "TO"
        foreach ($this->yourParty->senators as $senatorID=>$senator)
        {
            /**
             * Get the corresponding Senator Model (entity)
             * @var \Entities\Senator $senatorModel
             */
            $senatorModel = $game->getFilteredCards(array('SenatorID' => $senatorID))->first() ;
            // Potential "From"
            if ($senatorModel->getTreasury()>0)
            {
                $senator->addClass('draggable') ;
                $senator->addAttribute("action",
                    array(
                        "slider" ,
                        "RevenueRedistributeModal" ,
                        "Transfer talents from ".$senatorModel->getName() ,
                        "1" ,
                        $senatorModel->getTreasury() ,
                        "T."
                    )
                ) ;
            }
            // Potential "To"
            $senator->addClass('droppable') ;
        }
    }
        
    /**
     * Taken out of the constructor for convenience and lisabilatility
     * @param \Entities\Game $game
     */
    public function setContributions($game)
    {
        $this->header['description'] = _('Your Senators can give to Rome\'s treasury') ;
        $this->header['list'] = array (
            _('A contribution of 50T or more will increase his Influence by 7') ,
            _('A contribution of 25T or more will increase his Influence by 3') ,
            _('A contribution of 10T or more will increase his Influence by 1')
        ) ;
        $this->header['actions'] = array (
            array (
                'type' => 'button' ,
                'verb' => 'revenueContributionsDone' ,
                'text' => 'DONE'
            )
        ) ;
        // The revenueContributionsModal will pop up each time a "FROM" is dropped
        $this->sliders[] = array (
            'ID' => 'revenueContributionsModal' ,
            'title' => _('Give to Rome'),
            'verb' => 'revenueContributions',
            'text' => 'GIVE TO ROME'
        ) ;

        foreach ($this->yourParty->senators as $senatorID=>$senator)
        {
            /**
             * Get the corresponding Senator Model (entity)
             * @var \Entities\Senator $senatorModel
             */
            $senatorModel = $game->getFilteredCards(array('SenatorID' => $senatorID))->first() ;
            if ($senatorModel->getTreasury()>0)
            {
                $senator->addMenuItem(
                    array (
                        'style' => 'primary' ,
                        'disabled' => FALSE ,
                        'verb' => 'revenueContributions' ,
                        'text' => _('Give to Rome') ,
                        'classes' => array (
                            'revenueContributions'
                        ) ,
                        'attributes' => array (
                            'data-json'=> '{"action":["slider" , "revenueContributionsModal" , "'.$senatorModel->getName().' give to Rome" , "0" , "'.$senatorModel->getTreasury().'" , "T." ]}'
                        )
                    )
                );
            }
        }
    }
}