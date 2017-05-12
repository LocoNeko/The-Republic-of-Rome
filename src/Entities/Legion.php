<?php
namespace Entities ;

/**
 * @Entity  @Table(name="legions")
 **/
class Legion
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
    /** @ManyToOne(targetEntity="Senator", inversedBy="loyalLegions") @JoinColumn(name="loyalTo_id", referencedColumnName="internalId" , nullable=true) **/
    private $loyalTo ;

    // A Legion can be on a non-Card named location ("Rome" , "Pool" , "Released")
    /** @Column(type="string") @var string */
    private $otherLocation ='Pool';

    // A Legion can be located on a Card (Senator, Province, Conflict ?)
    /** @ManyToOne(targetEntity="Card", inversedBy="withLegions") @JoinColumn(name="locatedOn_id", referencedColumnName="internalId" , nullable=true) **/
    private $cardLocation ;

    public function setGame($game) { $this->game = $game; }
    public function setName($name) { $this->name = $name; }
    public function setVeteran($veteran) { $this->veteran = $veteran; }
    public function setLoyalTo($loyalTo) { $this->loyalTo = $loyalTo; }
    public function setOtherLocation($otherLocation) { $this->otherLocation = $otherLocation; }
    public function setCardLocation($cardLocation) { $this->cardLocation = $cardLocation; }

    public function getId() { return $this->id; }
    public function getGame() { return $this->game; }
    public function getName() { return $this->name; }
    public function getVeteran() { return $this->veteran; }
    public function getLoyalTo() { return $this->loyalTo; }
    public function getOtherLocation() { return $this->otherLocation; }
    public function getCardLocation() { return $this->cardLocation; }

    public function __construct($game , $num) 
    {
        $this->setGame($game) ;
        $this->setName(numberToRoman($num)) ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
        $this->setOtherLocation('Pool') ;
        $this->setCardLocation(NULL) ;
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
        return ($this->getOtherLocation() == 'Pool') ;
    }

    public function canBeDisbanded()
    {
        return ($this->getOtherLocation()== 'Rome' || $this->getOtherLocation() == 'Released') ;
    }
    
    public function isRegularInRome()
    {
        return ($this->getOtherLocation()== 'Rome') ;
    }
    
    /**
     * Returns the senatorID of the Senator the legion is loyal to or 0 if it's not loyal to any Senator
     * @return string|NULL 
     */
    public function getLoyalToSenatorID()
    {
        $senator = $this->getLoyalTo() ;
        return ($senator === '' ? 0 : $senator->getSenatorID()) ;
    }
    
    /**
     * Returns the card Id of the Card the legion is located on or 0 if it's not located on a card
     * @return string|NULL 
     */
    public function getCardLocationCardId()
    {
        $card = $this->getCardLocation() ;
        return ($card === '' ? 0 : $card->getId()) ;

    }
    
    public function recruit()
    {
        $this->setOtherLocation('Rome') ;
        $this->setCardLocation(NULL) ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
    }

    public function disband()
    {
        $this->setOtherLocation(NULL) ;
        $this->setCardLocation(NULL) ;
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
        if ($this->getOtherLocation()=='Pool') { return FALSE ; } 
        if ($this->getOtherLocation()=='Rome') { return TRUE ; } 
        if (!is_null($this->getCardLocation()) && $this->getCardLocation()->getPreciseType() == 'Senator' && !$this->getCardLocation()->getRebel()) { return TRUE; } 
        if (!is_null($this->getCardLocation()) && $this->getCardLocation()->getPreciseType() == 'Province' ) { return TRUE; } 
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
