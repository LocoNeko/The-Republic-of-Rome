<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="decks")
 **/
class Deck
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    // One deck has many cards
    /** @OneToMany(targetEntity="Card", mappedBy="deck" , cascade={"persist"} ) **/
    private $cards ;
    
    // One Game has many decks
    /** @ManyToOne(targetEntity="Game", inversedBy="decks") **/
    private $game ;
    
    // One Card can have a Deck (of controlled cards)
    /** @OneToOne(targetEntity="Card", mappedBy="cards_controlled") **/
    private $controlled_by ;

    // A deck can represent the Senators of a party
    /** @OneToOne(targetEntity="Party", inversedBy="senators") **/
    private $inParty ;
    
    // A deck can represent the hand of a player
    /** @OneToOne(targetEntity="Party", inversedBy="hand") **/
    private $inHand ;

    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */

    private function setCards($cards) { $this->cards = $cards; }
    public function setName($name) { $this->name = $name ; }
    public function setGame($game) { $this->game = $game ; }
    public function setControlled_by($card) { $this->controlled_by = $card ; }
    public function setInParty($inParty) { $this->inParty = $inParty; }
    public function setInHand($inHand) { $this->inHand = $inHand; }
 
    /** @return \Entities\Card[] */
    public function getCards()
    {
        $iterator = $this->cards->getIterator();
        $iterator->uasort(function ($a, $b) {
            return ($a->getPosition() < $b->getPosition()) ? -1 : 1;
        });
        $this->setCards(new ArrayCollection(iterator_to_array($iterator))) ;
        return $this->cards;
    }

    public function getName() { return $this->name ; }
    public function getId() { return $this->id; }
    public function getGame() { return $this->game; }
    public function getControlled_by() { return $this->controlled_by; }
    public function getInParty() { return $this->inParty; }
    public function getInHand() { return $this->inHand; }
 
    public function __construct($name)
    {
        $this->setName($name) ;
        $this->cards = new ArrayCollection();
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
     */
    public function checkValue($property , $value)
    {
        return ($this->getValue($property) == $value) ;
    }
    

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
                // When we need to save an Id only, check if NULL before
                if ($name=='game' || $name=='controlled_by' || $name=='inParty' || $name=='inHand' )
                {
                    $data[$name] = (is_null($item) ? NULL : $item->getId() ) ;
                }
                elseif ($name=='cards')
                {
                    foreach ($this->getCards() as $key=>$card)
                    {
                        $data['cards'][$key] = $card->saveData() ;
                    }
                }
                else
                {
                    $data[$name] = $item ;
                }
            }
        }
        return $data ;
    }
    
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
                        // $this->$setter($value) ;
                        error_log('LOAD - $deck->'.$setter.' ('.$value.')') ;
                    }
                }
            }
            elseif ($key=='cards')
            {
                
                /*
                 * - If the array collection of cards in the existing deck and in the saved data are both empty, don't do anything
                 * - Otherwise :
                 * 1 - Delete all cards for existing deck
                 * 2 - Create new cards based on savedData
                 */
                if ( count($this->getCards()) > 0 ) 
                {
                    //unset ($this->getCards()) ;
                    //$this->cards = new ArrayCollection();
                    if (count($value) > 0 )
                    {
                        foreach ($value as $key2=>$value2)
                        {
                            $cardType = $value2['preciseType'] ;
                            if ($cardType=='Statesman') { $cardType='Senator' ; }
                            if ($cardType=='Faction card') { $cardType='FactionCard' ; }
                            if ($cardType=='Era ends') { $cardType='EraEnds' ; }
                            $class = __NAMESPACE__.'\\'.$cardType ;
                            $card = new $class ($value2 , FALSE);
                            // Here : transform the $value2 array into something that can be used by the constructor of the relevant entity
                            /*
                            $card = new $class ($value2);
                            $this->getCards()[$key2] = $card ;
                            error_log(LOAD - $class) ;
                             */
                        }
                    }
                }
            }
        }

    }

    /**
    * ----------------------------------------------------
    * Other methods
    * ----------------------------------------------------
    */

    public function attachToGame($game)
    {
        $this->setGame($game) ;
        $game->getDecks()->add($this) ;
    }

    public function attachToCard(Card $controlled_by) 
    {
        $this->setControlledBy($controlled_by) ;
        $controlled_by->getCardsControlled()->add($this) ;
    }
    
    /**
     * Returns the position of all cards in the Deck in an array
     * @return array
     */
    public function getPositions()
    {
        $positions = array() ;
        foreach($this->getCards() as $card)
        {
            $positions[] = $card->getPosition();
        }
        return $positions ;
    }
    
    public function putCardOnTop($card) 
    {
        if ($this->getNumberOfCards()>0)
        {
            foreach($this->getCards() as $card2)
            {
                $card2->changePosition(1);
            }
        }
        $card->setPosition(0) ;
        $this->getCards()->add($card) ;
        $card->setDeck($this) ;
    }
    
    public function removeCard($card) 
    {
        $cardPosition = $card->getPosition();
        foreach($this->getCards() as $card2)
        {
            if ($card2->getPosition() > $cardPosition)
            {
                $card2->changePosition(-1);
            }
        }
        $this->getCards()->removeElement($card) ;
    }

    public function getNumberOfCards() 
    {
        return count($this->getCards()) ;
    }
    
    /**
     * Draws the first card of the deck
     * @return Card
     * @throws Exception Deck empty
     */
    public function drawFirstCard()
    {
        $card = $this->getCards()->first() ;
        if ($card!=NULL)
        {
            $this->getCards()->removeElement($card) ;
            foreach($this->getCards() as $card2)
            {
                $card2->changePosition(-1);
            }
            return $card ;
        }
        else
        {
            throw new Exception(sprintf(_('ERROR - Can\'t draw first card : deck %1$d is empty') , $this->getName() )) ;
        }
    }

    public function shuffle()
    {
        $positions = $this->getPositions() ;
        shuffle($positions) ;
        $i=0;
        foreach($this->getCards() as $card)
        {
            $card->setPosition($positions[$i++]) ;
        }
    }
    
    /**
     * Returns the first card that has a $property equal to $value. Moves it to another deck if $target is defined.
     * @param string $property
     * @param mixed $value
     * @param Deck $target
     * @return boolean|Card Card or FALSE if no card was found
     */
    public function getFirstCardByProperty($property , $value , $target=NULL)
    {
        foreach($this->getCards() as $card)
        {
            $getter = 'get'.ucfirst($property);
            if (method_exists($card, $getter))
            {
                if ($card->$getter() == $value)
                {
                    // If a $target deck was given, move the card to that deck
                    if ($target!=NULL && strpos(get_class($target), 'Entities\\Deck') !== FALSE) 
                    {
                        $this->removeCard($card) ;
                        $target->putCardOnTop($card) ;
                    }
                    return $card ;
                }
            }
        }
        throw new \Exception(_('ERROR - Could not find card')) ;
    }
    
    public function getFullName($self=FALSE)
    {
        $fullNames = array(
            'drawDeck' => _('The draw deck') ,
            'earlyRepublic' => _('The early republic') ,
            'middleRepublic' => _('The middle republic') ,
            'lateRepublic' => _('The late republic') ,
            'discard' => _('The discard pile') ,
            'unplayedProvinces' => _('Unplayed provinces') ,
            'inactiveWars' => _('Inactive wars') ,
            'activeWars' => _('Active Wars') ,
            'imminentWars' => _('Imminent Wars') ,
            'unprosecutedWars' => _('Unprosecuted Wars') ,
            'forum' => _('The Forum') ,
            'curia' => _('The Curia')
        ) ;
        if (key_exists($this->getName(), $fullNames))
        {
            return $fullNames[$this->getName()] ;
        }
        else
        {
            if ($this->getInHand()!=NULL && $self)
            {
                return _('Your hand');
            }
            return $this->getName() ;
        }
    }
    
}