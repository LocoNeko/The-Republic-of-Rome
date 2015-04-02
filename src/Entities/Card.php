<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="cards")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({"concession" = "Concession"})
 */
abstract class Card
{
    //public static $VALID_TYPES = array('Family', 'Statesman' , 'Concession' , 'Province' , 'Conflict' , 'Leader' ,'Faction' ,'Era ends' ) ;
    public static $VALID_TYPES = array('Concession') ;
    public static $VALID_DECKS = array('Draw' , 'Discard' , 'Forum', 'Curia' , 'Unplayed Provinces' , 'Early Republic' , 'Middle Republic' , 'Late Republic' , 'Inactive Wars' , 'Active Wars' , 'Imminent Wars' , 'Unprosecuted Wars') ;
    
    /**
     * @Id @Column(type="integer")
     * @var int
     */
    protected $id ;
    /**
     * @ManyToOne(targetEntity="Game", inversedBy="cards")
     **/
    private $game ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $name ;
    /**
     * @ManyToOne(targetEntity="Card", inversedBy="cards_controlled")
     * @JoinColumn(name="location_card_id", referencedColumnName="id", nullable=true)
     **/
    protected $location_card = NULL ;
    /**
    * @Column(type="string", nullable=true)
    * @var string
    */
    protected $location_deck = NULL ;
    /**
     * @OneToMany(targetEntity="Card", mappedBy="location_card")
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
    public function getId()
    {
        return $this->id ;
    }

    public function setId($id)
    {
        $this->id = (int)$id ;
    }

    public function getName()
    {
        return $this->name ;
    }

    public function setName($name)
    {
        $this->name = (string)$name ;
    }

    public function getLocation()
    {
        if ($this->location_deck!==NULL) {
            return $this->location_deck ;
        } else {
            return $this->location_card ;
        }
    }
    
    public function __construct(Game $game , $id , $name) {
        $this->game = $game ;
        $this->setId($id) ;
        $this->setName($name) ;
        $this->cards_controlled = new ArrayCollection();
    }

    /**
    * ----------------------------------------------------
    * Other methods
    * ----------------------------------------------------
    */

    public function resetLocation()
    {
        if ($this->location_card!==NULL) {
            $this->location_card->cards_controlled->remove($this) ;
            $this->location_card = NULL ;
        }
        $this->location_deck = NULL ;
    }
    
    /**
     * If location is a string, the card is in a deck
     * If location is a card, the card is controlled by another
     * @param mixed $location
     */
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

    public function getValue($property) {
        if (isset($this->$property))
        {
            return $this->$property ;
        } else {
            throw new Exception(sprintf(_('Property %1$s doesn\'t exist.') , $property));
        }
    }
}