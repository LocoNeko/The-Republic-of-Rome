<?php
namespace Presenters ;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Entities ;

class CardPresenter
{
    private $user_id ;
    public $preciseType ;
    public $classes;
    public $attributes ;
    public $data_json ;
    public $elements ;
    public $menu ;
    public $controlledCards ;

    /**
     * 
     * @param \Entities\Card $card
     * @param int $user_id
     */
    public function __construct($card , $user_id , $menu=NULL)
    {
        $this->user_id = $user_id ;
        $this->preciseType = $card->getPreciseType() ;
        /*
         * What we need to display the card :
         */
        
        $this->classes = array() ;
        $this->attributes = array() ;
        $this->elements = array() ;
        $this->menu = array() ;
        $this->controlledCards = array() ;
        
        /**
         * All cards have :
         * - Attribute card_id
         */
        $this->addAttribute('card_id', $card->getId()) ; 
        
        /**
         * Senator or Statesman
         */
        
        if ($card->getIsSenatorOrStatesman())
        {
            /** @var \Entities\Senator $card */
            $this->classes[] = 'sprite-Senator' ;
            $this->addAttribute('name', $card->getName()) ;
            $this->addAttribute('senatorID', $card->getSenatorID()) ;
            $this->elements[] = array (
                'classes' => array(
                    'sprite-position-name' ,
                    ($card->isLeader() ? 'leader' : '')
                ) ,
                'attributes' => array (
                   'data-toggle' => 'popover' ,
                   'data-content' => $card->statesmanPlayable($user_id)['message'] ,
                   'data-trigger' => 'hover' ,
                   'data-placement' => 'bottom'
                ) ,
                'text' => $card->getName().'<br>'.($card->getHasStatesman() ? '['.$card->getSenatorID().']' : $card->getSenatorID())
            ) ;
            /**
             * Basic text : MIL , ORA , LOY , treasury
             */
            $this->elements[] = array (
                'classes' => array('sprite-position-MIL') ,
                'text' => $card->getMIL()
            ) ;
            $this->elements[] = array (
                'classes' => array('sprite-position-ORA') ,
                'text' => $card->getORA()
            ) ;
            $this->elements[] = array (
                'classes' => array('sprite-position-LOY') ,
                'text' => $card->getLOY()
            ) ;
            $this->elements[] = array (
                'classes' => array('sprite-position-treasury') ,
                'text' => $card->getTreasury()
            ) ;
            /**
             * Padded text : CardID
             */
            $this->elements[] = array (
                'classes' => array('sprite-position-card-id') ,
                'text' => sprintf("%'.03d", $card->getId())
            ) ;
            /**
             * Markers (no text, but another class) : INF , INF10 , POP , Knights , Corrupt , priori consul , office
             */
            if ($card->getINF() > 0)
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-INF' , 'sprite-Marker_'.($card->getINF() % 10))
                ) ;
            }
            if ($card->getINF() >= 10)
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-INF10' , 'sprite-Marker_'.(int)($card->getINF() / 10))
                ) ;
            }
            if ($card->getPOP() > 0)
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-POP' , 'sprite-Marker_'.$card->getPOP())
                ) ;
            }
            if ($card->getKnights() > 0)
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-knights' , 'sprite-Marker_'.$card->getKnights())
                ) ;
            }
            if ($card->getCorrupt())
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-corrupt' , 'sprite-Marker_Corrupt')
                ) ;
            }
            if ($card->getPriorConsul())
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-PriorConsul' , 'sprite-Marker_PriorConsul')
                ) ;
            }
            if ($card->getOffice() != NULL)
            {
                $this->elements[] = array (
                    'classes' => array('sprite' , 'sprite-position-office' , 'sprite-Office_'.  str_replace(' ','-' ,$card->getOffice()))
                ) ;
            }
        }
        
        /**
         * Concessions
         */
        
        elseif($card->getPreciseType()=='Concession')
        {
            $this->classes[] = 'sprite-Card' ;
            $this->elements[] = array (
                'classes' => array('sprite-position-card-name') ,
                'text' => $card->getName()
            );
            $this->elements[] = array (
                'classes' => array('sprite-position-card-subname') ,
                'text' => 'Income : '.$card->getIncome()
            );
            if ($card->getSpecial()!=NULL)
            {
                $this->elements[] = array (
                    'classes' => array('sprite-position-card-attacks') ,
                    'text' => 'Special : '.$card->getSpecial()
                );
            }
            if ($card->getCorrupt())
            {
                $this->elements[] = array (
                    'classes' => array('sprite sprite-Marker_Corrupt sprite-position-corrupt')
                );
            }
        }
        
        /**
         * Others
         */
        
        else
        {
            $this->classes[] = 'sprite-Card' ;
            $this->elements[] = array (
                'classes' => array('sprite-position-card-name') ,
                'text' => $card->getName()
            );
        }
        
        /**
         * Cards controlled
         */
        
        if ($card->hasControlledCards())
        {
            foreach ($card->getCardsControlled()->getCards() as $subCard)
            {
                $subCardPresenter = new CardPresenter($subCard, $user_id) ;
                $this->controlledCards[] = $subCardPresenter ;
            }
        }
    }

    /**
     * Adds a class to the Card
     * @param string $name
     */
    public function addClass($name)
    {
        $this->classes[] = $name ;
    }
    
    /**
     * Adds a json value {"name" : "value"} to this card's data_json
     * @param string $name The value's name (key)
     * @param mixed $value The value itself
     */
    public function addAttribute($name , $value)
    {
        // TO DO ; Once attributes have been made obsolete, remove this
        $this->attributes[$name] = $value ;
        $json = json_decode($this->data_json , TRUE) ;
        $json[$name] = $value ;
        $this->data_json = json_encode($json) ;
    }

    /**
     * Adds a menu item to the menu for this card
     * Menu items behave like a 'submitWithVerb' element
     * @param array $item
     */
    public function addMenuItem($item)
    {
        $this->menu[] = $item ;
    }
}

