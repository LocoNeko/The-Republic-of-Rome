<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="cards")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="type", type="string")
 * @DiscriminatorMap({ "Concession" = "Concession" , "Conflict" = "Conflict" , "Senator" = "Senator" , "Leader" = "Leader" , "FactionCard" = "FactionCard" , "EraEnds" = "EraEnds" , "Province" = "Province" })
 */
abstract class Card
{
    public static $VALID_TYPES = array('Concession' , 'Conflict' , 'Senator' , 'Leader' , 'FactionCard' , 'EraEnds' , 'Province') ;
    
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
     * @ManyToOne(targetEntity="Deck", inversedBy="cards", cascade={"persist"})
     **/
    private $deck ;
    /**
    * @Column(type="string", name="preciseType")
    * @var string
     */
    private $preciseType ;
    /**
    * @Column(type="string")
    * @var string
    */
    protected $name ;
    // A Card can have a deck (of controlled cards)
    /**
     * @OneToOne(targetEntity="Deck", inversedBy="controlled_by", cascade={"persist"})
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
    
    public function setId($id) { $this->id = (int)$id ; }
    public function setName($name) { $this->name = (string)$name ; }
    public function setDeck($deck) { $this->deck = $deck ; }
    public function setPreciseType($preciseType) { $this->preciseType = $preciseType ; }
    
    public function getId() { return $this->id ; }
    public function getName() { return $this->name ; }
    public function getDeck() { return $this->deck ; }
    public function getPreciseType() { return $this->preciseType ; }
    // Only create the cards_controlled deck when it becomes necessary
    public function getCardsControlled() {
        if ($this->cards_controlled === NULL) {
            $this->cards_controlled = new \Entities\Deck('Cards controlled by '.$this->getName()) ;
            $this->cards_controlled->setControlled_by($this) ;
        }
        return $this->cards_controlled ;
    }
    
    public function __construct($id , $name , $preciseType) {
        $this->setId($id) ;
        $this->setName($name) ;
        $this->setPreciseType($preciseType) ;
    }

    /**
    * ----------------------------------------------------
    * Other methods
    * ----------------------------------------------------
    */
    
    public function getValue($property) {
        if (isset($this->$property))
        {
            return $this->$property ;
        } else {
            throw new Exception(sprintf(_('Property %1$s doesn\'t exist.') , $property));
        }
    }
    
    /**
     * Returns an array giving the type & name of its location and the location itself when it's a card or a party
     * This is entirely based on the Deck the card belongs to
     * @return array ('type' => 'game|card|party|hand' , 'name' , 'value' => NULL|(card)|(party)|(party) )
     */
    public function getLocation() {
        $deck = $this->getDeck() ;
        if ($deck->getGame() != NULL) {
            $result = array ('type' => 'game' , 'value' => NULL , 'name' => $deck->getName()) ;
        } elseif ($deck->getControlled_by() != NULL) {
            $result = array ('type' => 'card' , 'value' => $deck->getControlled_by() , 'name' => $deck->getControlled_by()->getName()) ;
        } elseif ($deck->getInParty() != NULL) {
            $result = array ('type' => 'party' , 'value' => $deck->getInParty() , 'name' => $deck->getInParty()->getName() ) ;
        } elseif ($deck->getInHand() != NULL) {
            $result = array ('type' => 'hand' , 'value' => $deck->getInHand() , 'name' => $deck->getInHand()->getName()) ;
        } else {
            $result = array ('type' => 'game' , 'value' => NULL , 'name' => 'Unknown location') ;
        }
        return $result ;
    }
    
    public function hasControlledCards() {
        return ($this->cards_controlled != NULL && count($this->cards_controlled->getCards())>0) ;
    }
}