<?php
namespace Entities ;

/**
 * @Entity  @Table(name="legions")
 **/
class Legion extends TraceableEntity
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    /** @ManyToOne(targetEntity="Game", inversedBy="legions") **/
    private $game ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    /** @Column(type="boolean") @var int */
    protected $veteran = FALSE ;

    // A Legion can be loyal to one Senator
    /** @ManyToOne(targetEntity="Senator", inversedBy="loyalLegions") @JoinColumn(name="loyalTo_id", referencedColumnName="id" , nullable=true) **/
    private $loyalTo ;

    // A Legion can be in ("Rome" , "Pool" , "Released" , "Card")
    /** @Column(type="string") @var string */
    private $location ='Pool';

    // A Legion can be located on a Card (Senator, Province, Conflict ?)
    /** @ManyToOne(targetEntity="Card", inversedBy="withLegions") @JoinColumn(name="locatedOn_id", referencedColumnName="id" , nullable=true) **/
    private $cardLocation ;

    public function setGame($game) { $this->game = $game; }
    public function setName($name) { $this->name = $name; }
    public function setVeteran($veteran) { $this->veteran = $veteran; }
    public function setLoyalTo($loyalTo) { $this->loyalTo = $loyalTo; }
    /**
     * Set the location of the Legion. If a Card is sent, sets it to 'Card' and sets the otherLocation to the Card that was sent
     * @param string|\Entities\Card $location
     */
    public function setLocation($location) {
        if (is_subclass_of($location, "Entities\\Card"))
        {
            $this->location='Card' ;
            $this->cardLocation = $location ;
        }
        else
        {
            $this->location = $location ;
            $this->cardLocation = NULL ;
        }
    }

    public function getId() { return $this->id; }
    public function getGame() { return $this->game; }
    public function getName() { return $this->name; }
    public function getVeteran() { return $this->veteran; }
    public function getLoyalTo() { return $this->loyalTo; }

    public function getLocation() {
        if ($this->location=='Card')
        {
            return $this->cardLocation ;
        }
        return $this->location; 
    }

    public function __construct($game , $num) 
    {
        $this->setGame($game) ;
        $this->setName(numberToRoman($num)) ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
        $this->setLocation('Pool') ;
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
                if($name=='loyalTo' || $name=='cardLocation')
                {
                    $data[$name] = (is_null($item) ? NULL : $item->saveData() ) ;
                }
                elseif ($name!='game')
                {
                    $data[$name] = $item ;
                }
            }
        }
        return $data ;
    }
    
    public function canBeRecruited()
    {
        return ($this->getLocation() == 'Pool') ;
    }

    public function canBeDisbanded()
    {
        return ($this->getLocation()== 'Rome' || $this->getLocation() == 'Released') ;
    }
    
    public function isRegularInRome()
    {
        return ($this->getLocation()== 'Rome') ;
    }
    
    public function isAway()
    {
        return ($this->getLocation()== 'Card') ;
    }
    
    /**
     * Returns the senatorID of the Senator the legion is loyal to or 0 if it's not loyal to any Senator
     * @return string|NULL 
     */
    public function getLoyalToSenatorID()
    {
        $senator = $this->getLoyalTo() ;
        if (is_null($senator))
        {
            return NULL ;
        }
        else
        {
            return (($senator === '') ? 0 : $senator->getSenatorID()) ;
        }
    }
    
    public function getLoyalToMessage()
    {
        $senator = $this->getLoyalTo() ;
        if (is_null($senator))
        {
            return _('nobody') ;
        }
        else
        {
            return (($senator === '') ? _('nobody') : $senator->getName()) ;
        }
    }

    /**
     * Returns the card Id of the Card the legion is located on or 0 if it's not located on a card
     * @return string|NULL 
     */
    public function getCardLocationCardId()
    {
        return (($this->location=='Card') ? $this->getLocation()->getCardId() : 0) ;
    }
    
    public function recruit()
    {
        $this->setLocation('Rome') ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
    }

    public function disband()
    {
        $this->setLocation('Pool') ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
    }

    /**
     * Determines whether or not a Legion should be maintained by Rome.
     * Which means the legion is : in Rome, in a province (garrison) , with a non-rebel Senator
     * @return boolean
     */
    public function romeMaintenance()
    {
        if ($this->getLocation()=='Pool') { return FALSE ; } 
        if ($this->getLocation()=='Rome') { return TRUE ; } 
        if ($this->location=='Card')
        {
            if (!is_null($this->cardLocation) && $this->cardLocation->getPreciseType() == 'Senator' && !$this->cardLocation->getRebel()) { return TRUE; } 
            if (!is_null($this->cardLocation) && $this->cardLocation->getPreciseType() == 'Province' ) { return TRUE; } 
        }
        return FALSE;
    }
    
}

function numberToRoman($num)
{
    $n = intval($num);
    $result = '';
    $lookup = array('M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1);
    foreach ($lookup as $roman => $value) {
            $matches = intval($n / $value);
            $result .= str_repeat($roman, $matches);
            $n = $n % $value;
    }
    return $result;
}
