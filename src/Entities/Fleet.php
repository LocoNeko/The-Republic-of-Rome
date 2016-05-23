<?php
namespace Entities ;

/**
 * @Entity  @Table(name="fleets")
 **/
class Fleet
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    /** @ManyToOne(targetEntity="Game", inversedBy="fleets") **/
    private $game ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    // A Fleet can be on a non-Card named location ("Rome" , "Pool")
    /** @Column(type="string") @var string */
    private $otherLocation ='Pool';

    // A Fleet can be located on a Card (Senator, Conflict ?)
    /** @ManyToOne(targetEntity="Card", inversedBy="withFleets") @JoinColumn(name="locatedOn_id", referencedColumnName="internalId" , nullable=true) **/
    private $cardLocation ;

    public function setGame($game) { $this->game = $game; }
    public function setName($name) { $this->name = $name; }
    public function setOtherLocation($otherLocation) { $this->otherLocation = $otherLocation; }
    public function setCardLocation($cardLocation) { $this->cardLocation = $cardLocation; }

    public function getId() { return $this->id; }
    public function getGame() { return $this->game; }
    public function getName() { return $this->name; }
    public function getOtherLocation() { return $this->otherLocation; }
    public function getCardLocation() { return $this->cardLocation; }

    public function __construct($game , $num) 
    {
        $this->setGame($game) ;
        $this->setName($this->numberToRoman($num)) ;
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
                if($name=='cardLocation')
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

    public function inRome()
    {
        return ($this->getOtherLocation()== 'Rome') ;
    }
    
    public function recruit()
    {
        $this->setOtherLocation('Rome') ;
        $this->setCardLocation(NULL) ;
    }

    public function disband() {
        $this->setOtherLocation(NULL) ;
        $this->setCardLocation(NULL) ;
    }

    /**
     * Determines whether or not a Fleet should be maintained by Rome.
     * Which means the fleet is : in Rome, in a province (garrison) , with a non-rebel Senator
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
    
    public function numberToRoman($num)
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
   
}

