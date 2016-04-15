<?php
namespace Presenters ;

class PartyPresenter
{
    public $self ;
    public $name_attribute ;
    public $name ;
    public $leaderName ;
    public $treasury ;
    public $totalVotes ;
    public $nbOfCardsInHand ;
    public $hand ;
    /** 
     * An array of CardPresenter for the Senators of this party, the key of each element is the SenatorID
     * @var \Presenters\CardPresenter[] $senators 
     */
    public $senators = [] ;
    
    /**
     * Creates a Party Presenter for this $party
     * @param \Entities\Party $party
     * @param int $user_id The user id of the user seeing this party
    */
    public function __construct($party , $user_id) {
        $this->self = ($party->getUser_id() == $user_id) ;
        $this->name_attribute = 'Party_'.$party->getUser_id() ;
        $this->name = ($this->self ? $party->getName() : $party->getFullName()) ;
        $this->leaderName = ( $party->getLeader()!=NULL ? $party->getLeader()->getName() : 'NONE' ) ;
        $this->treasury = $party->getTreasury() ;
        $this->totalVotes = $party->getTotalVotes() ;
        $this->nbOfCardsInHand = $party->getHand()->getNumberOfCards() ;
        $this->hand = new \Presenters\DeckPresenter($party->getHand() , $user_id) ;
        foreach ($party->getSenators()->getCards() as $senator)
        {
            /** @var \Entities\Senator $senator */
            $this->senators[$senator->getSenatorID()] = new \Presenters\CardPresenter($senator, $user_id) ;
        }
        
    }

}