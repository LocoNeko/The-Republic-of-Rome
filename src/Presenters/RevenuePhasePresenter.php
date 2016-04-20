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
        $this->game =  new \Presenters\GamePresenterNew($game , $user_id) ;
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
                $this->header['description'] = _('Your Senators can give to Rome\'s treasury') ;
                $this->header['list'] = array (
                    _('A contribution of 50T or more will increase his Influence by 7') ,
                    _('A contribution of 25T or more will increase his Influence by 3') ,
                    _('A contribution of 10T or more will increase his Influence by 1')
                ) ;
                $this->header['actions'] = array (
                    array (
                        'type' => 'button' ,
                        'verb' => 'ContributionsDone' ,
                        'text' => 'DONE'
                    )
                ) ;
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
        
        //TO DO - Release legions interface
        $this->interface['showReleasedLegions'] = FALSE ;
        
        // The RevenueRedistributeModal will pop up each time a "FROM" is dropped
        $this->sliders[] = array (
            'ID' => 'RevenueRedistributeModal' ,
            'title' => _('Redistribute'),
            'verb' => 'revenueRedistribute',
            'text' => 'TRANSFER'
        ) ;
        
        // The first potential "FROM" is the party treasury
        $this->header['actions'][] = array (
            'type' => 'icon' ,
            'verb' => 'revenueRedistribute' ,
            'text' => ' '.$game->getParty($this->user_id)->getName() ,
            'draggable' => 'YES' ,
            'droppable' => 'YES' ,
            'caption' => 'Drag and drop to transfer money to/from your party treasury' ,
            'class' => 'glyphicon glyphicon-plus' ,
            'data_json'=> '{"action":["slider" , "RevenueRedistributeModal" , "Transfer talents from party treasury" , "0" , "'.$game->getParty($this->user_id)->getTreasury().'" , "T." ]}'
        );
        
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
                    'caption' => 'Drag and drop to transfer money to this party treasury' ,
                    'class' => 'glyphicon glyphicon-plus' ,
                    'data_json'=> json_encode(array("user_id"=>$party->getUser_id()))
                );
            }
        }
        
        // The last action in the header is "DONE"
        $this->header['actions'][] = array (
            'type' => 'button' ,
            'verb' => 'revenueRedistributionDone' ,
            'text' => 'DONE'
        );

        // Senators in the party are both potential "FROM" and "TO"
        foreach ($this->yourParty->senators as $senatorID=>$senator)
        {
            /**
             * Get the corresponding Senator Model (entity)
             * @var \Entities\Senator $senatorModel
             */
            $senatorModel = $game->getFilteredCards(array('SenatorID' => $senatorID))->first() ;
            // Potential "From"
            $senator->addClass('draggable') ;
            $senator->addAttribute("action", 
                array(
                    "slider" ,
                    "RevenueRedistributeModal" ,
                    "Transfer talents from ".$senatorModel->getName() ,
                    "0" ,
                    $senatorModel->getTreasury() ,
                    "T."
                )
            ) ;
            //$senator->setDataJson ('{"action":["slider" , "RevenueRedistributeModal" , "Transfer talents from '.$senatorModel->getName().'" , "0" , "'.$senatorModel->getTreasury().'" , "T." ]}') ;
            // Potential "To"
            $senator->addClass('droppable') ;
        }
    }
    
    public function getContent()
    {
        /*
         * Redistribution
         */
        if ($this->getPhase()=='Revenue' && $this->getSubPhase()=='Redistribution')
        {
            $result['showReleasedLegions'] = ( $this->getAreThereReleasedLegions() && $this->getIsPartyOfHRAO() ) ;
            $result['otherParties'] = array() ;
            foreach ($this->getParties() as $party)
            {
                if ($party->getUser_id() == $this->getUser_id())
                {
                    $result['yourParty'] = $party ;
                }
                else
                {
                    $result['otherParties'][] = $party ;
                }
            }
            $result['action'] = array (
                'type' => 'button' ,
                'verb' => 'revenueRedistributionDone' ,
                'text' => 'DONE' ,
                'user_id' => $this->getUser_id()
            );

        }
        return $result ;
    }
}