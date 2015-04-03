<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="cards")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({ "Concession" = "Concession" , "Conflict" = "Conflict" , "Senator" = "Senator" , "Leader" = "Leader" , "FactionCard" = "FactionCard" })
 */
abstract class Card
{
    // TO DO : ADD 'Province'and 'Era ends'
    public static $VALID_TYPES = array('Concession' , 'Conflict' , 'Senator' , 'Leader' , 'FactionCard' ) ;
    public static $VALID_DECKS = array('Draw' , 'Discard' , 'Forum', 'Curia' , 'Unplayed Provinces' , 'Early Republic' , 'Middle Republic' , 'Late Republic' , 'Inactive Wars' , 'Active Wars' , 'Imminent Wars' , 'Unprosecuted Wars') ;
    
    /**
     * @Id @Column(type="integer") @GeneratedValue
     * @var int
     */
    protected $internalId ;
    /**
     * @Column(type="integer")
     * @var int
     */
    protected $id ;
    // One Deck has many cards
    /**
     * @ManyToOne(targetEntity="Deck", inversedBy="cards")
     **/
    private $deck ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $name ;
    // A Card can have a deck (of controlled cards)
    /**
     * @OneToOne(targetEntity="Deck", mappedBy="controlled_by")
     **/
    private $cards_controlled ;

    public static function isValidType($type) {
        return in_array($type , self::$VALID_TYPES) ;
    }
    
    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
    
    public function setId($id)
    {
        $this->id = (int)$id ;
    }

    public function setDeck($deck)
    {
        $this->deck = $deck ;
    }

    public function setName($name)
    {
        $this->name = (string)$name ;
    }

    public function getId()
    {
        return $this->id ;
    }

    public function getName()
    {
        return $this->name ;
    }

    public function getDeck()
    {
        return $this->deck ;
    }
    
    public function getCardsControlled()
    {
        return $this->cards_controlled ;
    }
    
    public function __construct($id , $name) {
        $this->setId($id) ;
        $this->setName($name) ;
    }

    /**
    * ----------------------------------------------------
    * Other methods
    * ----------------------------------------------------
    */

    /*
    public function resetLocation()
    {
        if ($this->location_card!==NULL) {
            $this->location_card->cards_controlled->remove($this) ;
            $this->location_card = NULL ;
        }
        $this->location_deck = NULL ;
    }
    */
    
    /**
     * If location is a string, the card is in a deck
     * If location is a card, the card is controlled by another
     * @param mixed $location
     */
    /*
    public function setLocation($location) {
        if (is_string($location) && in_array($location, self::$VALID_DECKS)) {
            $this->resetLocation() ;
            $this->location_deck = $location ;
        } elseif (is_object($location) && get_class($location)=='Card' ) {
            $this->resetLocation() ;
            $this->location_card = $location ;
            $this->location_card->cards_controlled->add($this) ;
        } else {
            throw new Exception(_('Invalid location type.'));
        }
    }
    */
    
    public function getValue($property) {
        if (isset($this->$property))
        {
            return $this->$property ;
        } else {
            throw new Exception(sprintf(_('Property %1$s doesn\'t exist.') , $property));
        }
    }
}