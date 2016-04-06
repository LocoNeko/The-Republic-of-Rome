<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;

class RevenuePhasePresenter
{
    private $user_id ;
    private $phase ;
    private $subPhase ;
    private $parties ;
    private $isDone ;
    private $revenue_base ;
    private $droughtLevel ;
    private $areThereReleasedLegions ;
    private $isPartyOfHRAO ;
    
    /**
     * @param \Entities\Game $game
     * @param int $user_id user_id of the user viewing this
     */
    public function __construct($game , $user_id) {
        $this->setUser_id($user_id) ;
        $this->setPhase($game->getPhase()) ;
        $this->setSubPhase($game->getSubPhase()) ;
        $this->setParties($game->getParties()) ;
        $this->setIsDone($game->getParty($user_id)->getIsDone()) ;
        $this->setRevenue_base($game->getParty($user_id)->revenue_base($game->getLegions())) ;
        $this->setDroughtLevel($game->getEventProperty('name' , 'Drought'));
        $this->setAreThereReleasedLegions($game->areThereReleasedLegions()) ;
        $this->setIsPartyOfHRAO($game->getHRAO()->getLocation()['value']->getUser_id() == $user_id) ;
        //game.getHRAO().getLocation()['value'].getUser_id() == app.user.id ;
    }
    
    /*
     * Setters & Getters
     */
    public function setUser_id($user_id) { $this->user_id = $user_id; }
    public function setPhase($phase) { $this->phase = $phase;}
    public function setSubPhase($subPhase) { $this->subPhase = $subPhase;}
    public function setParties($parties) { $this->parties = $parties;}
    public function setIsDone($isDone) { $this->isDone = $isDone; }
    public function setRevenue_base($revenue_base) { $this->revenue_base = $revenue_base; }
    public function setDroughtLevel($droughtLevel) { $this->droughtLevel = $droughtLevel; }
    public function setAreThereReleasedLegions($areThereReleasedLegions) { $this->areThereReleasedLegions = $areThereReleasedLegions; }
    public function setIsPartyOfHRAO($isPartyOfHRAO) { $this->isPartyOfHRAO = $isPartyOfHRAO; }

    public function getUser_id() { return $this->user_id; }
    public function getPhase() { return $this->phase;}
    public function getSubPhase() { return $this->subPhase;}
    public function getParties() { return $this->parties;}
    public function getIsDone() { return $this->isDone;}
    public function getRevenue_base() { return $this->revenue_base; }
    public function getDroughtLevel() { return $this->droughtLevel; }
    public function getAreThereReleasedLegions() { return $this->areThereReleasedLegions; }
    public function getIsPartyOfHRAO() { return $this->isPartyOfHRAO; }

    public function getParty($user_id)
    {
        foreach ($this->getParties() as $party)
        {
            if ($party->getUser_id()==$user_id)
            {
                return $party ;
            }
        }
        return FALSE ;
    }
    
    /**
     * Get header and return $result with ['description'] , optional ['list'] , optional ['action']
     * @return array
     */
    public function getHeader()
    {
        $result = array() ;
        $result['list'] = array() ;
        $result['action'] = array () ;
        if ($this->getPhase()=='Revenue' && $this->getSubPhase()=='Base')
        {
            if ($this->getIsDone() === FALSE)
            {
                $result['description'] = _('This is your base revenue :') ;
            }
            else
            {
                $result['description'] = _('You are done with revenue and are currently waiting for :') ;
                foreach ($this->getParties() as $party)
                {
                    if ($party->getId() != $this->getUser_id() && $party->getIsDone() == FALSE)
                    {
                        $result['list'][] = $party->getFullName();
                    }
                }
            }
        }
        elseif ($this->getPhase()=='Revenue' && $this->getSubPhase()=='Redistribution')
        {
            if ($this->getIsDone() === FALSE)
            {
                $result['description'] = _('You can redistribute money between your party, senators, and other parties') ;
                $result['list'][] = _('Drag and drop to transfer money') ;
                $result['list'][] = _('Tokens below : To/From treasury of your party and other players\' parties') ;
                $result['list'][] = _('Senators : To/From this Senator');
            }
            else
            {
                $result['description'] = _('You are done with redistribution and are currently waiting for :') ;
                foreach ($this->getParties() as $party)
                {
                    if ($party->getId() != $this->getUser_id() && $party->getIsDone() == FALSE)
                    {
                        $result['list'][] = $party->getFullName();
                    }
                }
            }
            
        }
        elseif ($this->getPhase()=='Revenue' && $this->getSubPhase()=='Contributions')
        {
            if ($this->getIsDone() === FALSE)
            {
                $result['description'] = _('Your Senators can give to Rome\'s treasury') ;
                $result['list'][] = _('A contribution of 50T or more will increase his Influence by 7') ;
                $result['list'][] = _('A contribution of 25T or more will increase his Influence by 3') ;
                $result['list'][] = _('A contribution of 10T or more will increase his Influence by 1') ;
            }
            else
            {
                $result['description'] = _('You are done with contributions and are currently waiting for :') ;
                foreach ($this->getParties() as $party)
                {
                    if ($party->getId() != $this->getUser_id() && $party->getIsDone() == FALSE)
                    {
                        $result['list'][] = $party->getFullName();
                    }
                }
            }
            $result['action'] = array (
                'type' => 'button' ,
                'verb' => 'ContributionsDone' ,
                'text' => 'DONE' ,
                'user_id' => $this->getUser_id()
            );
        }
        return $result ;
    }
    
    public function getContent()
    {
        $result= array() ;
        $result['showInterface'] = !$this->getIsDone() ;
        /*
         *  Base
         */
        if ($this->getPhase()=='Revenue' && $this->getSubPhase()=='Base')
        {
            $result['base'] = $this->getRevenue_base() ;
            $result['droughtLevel'] = $this->getDroughtLevel();
        }
        /*
         * Redistribution
         */
        elseif ($this->getPhase()=='Revenue' && $this->getSubPhase()=='Redistribution')
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
                'verb' => 'RedistributionDone' ,
                'text' => 'DONE' ,
                'user_id' => $this->getUser_id()
            );

        }
        return $result ;

    }
}