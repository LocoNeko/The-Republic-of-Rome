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
    
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $internalId ;

    /** @Column(type="integer") @var int */
    protected $id ;
    
    // One Deck has many cards
    /** @ManyToOne(targetEntity="Deck", inversedBy="cards", cascade={"persist"}) **/
    private $deck ;
    
    /** @Column(type="string", name="preciseType") @var string */
    private $preciseType ;
    
    /** @Column(type="string") @var string */
    protected $name ;

    /** @Column(type="integer") @var int */
    protected $position ;
    
    // A Card can have a deck (of controlled cards)
    /** @OneToOne(targetEntity="Deck", inversedBy="controlled_by" , cascade={"persist"}) **/
    private $cards_controlled ;
    
    // A Card can be the location of any number of Legions
    /** @OneToMany(targetEntity="Legion", mappedBy="cardLocation") **/
    private $withLegions ;
    
    // A Card can be the location of any number of Fleets
    /** @OneToMany(targetEntity="Fleet", mappedBy="cardLocation") **/
    private $withFleets ;

    // A Card can be the battlefield of more than one commander
    /** @OneToMany(targetEntity="Senator", mappedBy="commanderIn") **/
    private $battlefieldFor ;

    public static function isValidType($type)
    {
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
    public function setPosition($position) { $this->position = $position ; }
    public function setPreciseType($preciseType) { $this->preciseType = $preciseType ; }

    public function getId() { return $this->id ; }
    public function getName() { return $this->name ; }
    public function getDeck() { return $this->deck ; }
    public function getPosition() { return $this->position ; }
    public function getPreciseType() { return $this->preciseType ; }
    public function getWithLegions() { return $this->withLegions; }
    public function getWithFleets() { return $this->withFleets; }

    // Only create the cards_controlled deck when it becomes necessary
    /**
     * 
     * @return \Entities\Deck
     */
    public function getCardsControlled()
    {
        if ($this->cards_controlled === NULL)
        {
            $this->cards_controlled = new \Entities\Deck('Cards controlled by '.$this->getName()) ;
            $this->cards_controlled->setControlled_by($this) ;
        }
        return $this->cards_controlled ;
    }
    
    public function __construct($id , $name , $preciseType)
    {
        $this->setId($id) ;
        $this->setName($name) ;
        $this->setPreciseType($preciseType) ;
        $this->withLegions = new ArrayCollection() ;
        $this->withFleets = new ArrayCollection() ;
        $this->battlefieldFor = new ArrayCollection() ;
    }
    
    /**
     * Goes through all the object's properties and saves them in an array
     * @return type
     */
    public function saveData()
    {
        $data = array() ;
        foreach (get_object_vars($this) as $name=>$property)
        {
            $getter = 'get'.ucfirst($name);
            if (method_exists($this, $getter))
            {
                // Get the item and its class
                $item = $this->$getter() ;
                $dataType = gettype($item) ;
                if ($dataType=='object')
                {
                    $dataType=get_class($item);
                }
                if ($dataType=='Doctrine\\ORM\\PersistentCollection')
                {
                    foreach($item as $key=>$value)
                    {
                        $data[$name][$key] = $value->saveData() ;
                    }
                }
                // If cards_controlled is not NULL, it's a Deck, and saveData() must be called on it
                elseif ($name=='cards_controlled')
                {
                    $data[$name] = (is_null($item) ? NULL : $item->saveData() ) ;
                }
                /*
                 *  - No need to save the Deck, as it can be populated from the deck entity when re-created
                 *  - Never save Senator->biddingFor, as it would be circular
                 */
                elseif ($name!='deck' && $name!='biddingFor')
                {
                    $data[$name] = $item ;
                }
            }
        }
        return $data ;
    }

    /* 
     * TO DO 
     * The saveData() functions of each child class of Card should be moved here.
     * A foreach() should go through every property (using get_object_vars()) and save them in the form $data[$propertyName] = $propertyValue
     * All properties that are an array (cards_controlled , withLegions , withFleets) should call the saveData() of the releveant target entity
     */
    
    public function loadData($data)
    {
        foreach ($data as $key=>$value)
        {
            // Non-arrays should all be treated later
            if (!is_array($value))
            {
                $getter = 'get'.ucfirst($key);
                if (method_exists($this, $getter) && !is_array($value))
                {
                    if ($this->$getter() != $value)
                    {
                        $setter = 'set'.ucfirst($key);
                        // TO DO  : Uncomment once happy
                        // $this->.$setter($value) ;
                        error_log('LOAD - $card->'.$setter.' ('.$value.')') ;
                    }
                }
            }
            else
            {
                // 3 cases : cards_controlled , withLegions , withFleets
            }
        }    
    }
    
    /**
    * ----------------------------------------------------
    * Other methods
    * ----------------------------------------------------
    */
    
    /**
     * returns whether or not a card is a Senator (Family or Statesman)
     * @return boolean
     */
    public function getIsSenatorOrStatesman()
    {
        return ($this->getPreciseType()=='Statesman' || $this->getPreciseType()=='Senator');
    }
    
    /**
     * Returns the value of the $property, using a getter, returns an exception if no getter exists
     * @param string $property
     * @return mixed The value of the property
     */
    public function getValue($property)
    {
        $getter = 'get'.ucfirst($property);
        if (method_exists($this, $getter))
        {
            return $this->$getter() ;
        }
        else
        {
            return FALSE ;
        }
    }
                
    /**
     * Returns a boolean indicating whether $property is equal to $value
     * @param string $property
     * @param mixed $value
     * @return boolean
     * @throws Exception
     */
    public function checkValue($property , $value)
    {
        return ($this->getValue($property) == $value) ;
    }
    
    /**
     * Returns an array giving the type & name of its location and the location itself when it's a card or a party
     * This is entirely based on the Deck the card belongs to
     * @return array ('type' => 'game|card|party|hand' , 'deck' , 'name' , 'value' => NULL|(deck)|(card)|(party)|(party) )
     */
    public function getLocation()
    {
        if (method_exists($this, 'getDeck'))
        {
            $deck = $this->getDeck() ;
            // Deck
            if (method_exists($deck, 'getGame') && $deck->getGame() != NULL)
            {
                $result = array (
                    'type' => 'game' ,
                    'deck' => $deck ,
                    'value' => $deck ,
                    'name' => $deck->getName() 
                ) ;
            }
            // Card
            elseif (method_exists($deck, 'getControlled_by') && $deck->getControlled_by() != NULL)
            {
                $result = array (
                    'type' => 'card' ,
                    'deck' => $deck ,
                    'value' => $deck->getControlled_by() ,
                    'name' => $deck->getControlled_by()->getName()
                ) ;
            }
            // Party
            elseif (method_exists($deck, 'getInParty') && $deck->getInParty() != NULL)
            {
                $result = array (
                    'type' => 'party' ,
                    'deck' => $deck ,
                    'value' => $deck->getInParty() ,
                    'name' => $deck->getInParty()->getName()
                ) ;
            }
            elseif (method_exists($deck, 'getInHand') && $deck->getInHand() != NULL)
            {
                $result = array (
                    'type' => 'hand' ,
                    'deck' => $deck ,
                    'value' => $deck->getInHand() ,
                    'name' => $deck->getInHand()->getName()
                ) ;
            }
            else
            {
                $result = array (
                    'type' => 'game' ,
                    'deck' => NULL ,
                    'value' => NULL ,
                    'name' => 'Unknown location'
                ) ;
            }
        }
        else
        {
           $result = array (
               'type' => 'game' ,
               'deck' => NULL ,
               'value' => NULL ,
               'name' => 'no getDeck method'
            ) ;
        }
        return $result ;
    }
    
    /**
     * Changes the position of a card by (int) $change
     * @param int $change
     */
    public function changePosition ($change)
    {
        $this->position+=(int)$change ;
    }
    
    /**
     * Return the game entity to which this card ultimately belongs or FALSE if there was an error
     * This function makes use of the fact that all entitites returned by the getLocation() function (Deck, Card , Party) have a getGame() function
     * @return Game
     */
    public function getGame()
    {
        $location = $this->getLocation() ;
        return ( ($location['value']==NULL) ? NULL : $location['value']->getGame() ) ;
    }
    
    /**
     * Checks if a card controls other, as calling getCardsControlled() directly would trigger on-the-fly creation
     * @return type
     */
    public function hasControlledCards()
    {
        return ($this->cards_controlled != NULL && count($this->cards_controlled->getCards())>0) ;
    }    

}
