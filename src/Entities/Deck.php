<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
/**
 * @Entity @Table(name="decks")
 **/
class Deck
{
    /**
    * @Id @Column(type="integer") @GeneratedValue
    * @var int
    */
    protected $id ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $name ;
    // One deck has many cards
    /**
     * @OneToMany(targetEntity="Card", mappedBy="deck")
     **/
    private $cards ;
    // One Game has many decks
    /**
    * @ManyToOne(targetEntity="Game", inversedBy="decks")
    **/
    private $game ;
    // One Card can have a Deck (of controlled cards)
    /**
    * @OneToOne(targetEntity="Card", mappedBy="cards_controlled")
    **/
    private $controlled_by ;
    /**
    * @Column(name="`order`" , type="array")
    * @var array
    */
    protected $order = array() ;


    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */

    public function setName($name) {
        $this->name = $name ;
    }
    
    public function setGame($game) {
        $this->game = $game ;
    }

    public function setControlled_by($card) {
        $this->controlled_by = $card ;
    }

    public function getCards() { return $this->cards ; }
    public function getName() { return $this->name ; }
    public function getId() { return $this->id; }
    public function getGame() { return $this->game; }
    public function getControlled_by() { return $this->controlled_by; }
    public function getOrder() { return $this->order; }

    public function __construct($name) {
        $this->setName($name) ;
        $this->cards = new ArrayCollection();
    }

    public function saveData() {
        $data = array() ;
        $data['name'] = $this->getName() ;
        $data['order'] = $this->getOrder() ;
        $data['cards'] = array () ;
        foreach ($this->getCards() as $key=>$card) {
            $data['cards'][$key] = $card->saveData() ;
        }
        $data['game_id'] = ($this->game === NULL ? NULL : $this->game->getId()) ;
        return $data ;
    }

    /**
    * ----------------------------------------------------
    * Other methods
    * ----------------------------------------------------
    */

    public function attachToGame($game) {
        $this->setGame($game) ;
        $game->getDecks()->add($this) ;
    }

    public function attachToCard(Card $controlled_by) {
        $this->setControlledBy($controlled_by) ;
        $controlled_by->getCardsControlled()->add($this) ;
    }
    
    public function putCardOnTop($card) {
        $this->getCards()->add($card) ;
        $card->setDeck($this) ;
        array_unshift($this->order , $card->getId()) ;
    }

    public function shuffle() {
        shuffle($this->order) ;
    }
    
    /**
     * Returns the first card that has a $property equal to $value
     * @param type $property
     * @param type $value
     * @return boolean
     */
    public function getFirstCardByProperty($property , $value) {
        foreach($this->getCards() as $card) {
            $getter = 'get'.ucfirst($property);
            if (method_exists($card, $getter)) {
                if ($card->$getter() == $value) {
                    return $card ;
                }
            }
        }
        return FALSE ;
    }
}