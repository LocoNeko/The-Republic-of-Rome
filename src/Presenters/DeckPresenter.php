<?php
namespace Presenters ;

class DeckPresenter
{
    public $name ;
    public $description ;
    public $nbOfCards ;
    public $cards = array();
        
    /**
     * Creates a Deck Presenter for this $deck
     * @param \Entities\Deck $deck
     * @param int $user_id
    */
    public function __construct($deck , $user_id) {
        $this->name = $deck->getName() ;
        $this->nbOfCards = $deck->getNumberOfCards() ;
        if ($this->nbOfCards > 0 )
        {
            $this->description = $deck->getFullName() ;
            foreach ($deck->getCards() as $card)
            {
                $this->cards[] = new \Presenters\CardPresenter($card, $user_id) ;
            }
        }
        else
        {
            $this->description = sprintf(_('%1$s is emtpy') , $deck->getFullName()) ;
        }
    }
}