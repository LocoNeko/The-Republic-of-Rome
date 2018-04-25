<?php
namespace Entities ;

/**
 * @Entity  @Table(name="fleets")
 **/
class Fleet extends TraceableEntity
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    /** @ManyToOne(targetEntity="Game", inversedBy="fleets") **/
    private $game ;
    
    /** @Column(type="string") @var string */
    protected $name ;
    
    // A Fleet can be in ("Rome" , "Pool" , "Card")
    /** @Column(type="string") @var string */
    private $location ='Pool';

    // A Fleet can be located on a Card (Senator, Conflict ?) when its location is equal to 'Card'
    /** @ManyToOne(targetEntity="Card", inversedBy="withFleets") @JoinColumn(name="locatedOn_id", referencedColumnName="id" , nullable=true) **/
    private $cardLocation ;

    public function setGame($game) { $this->game = $game; }
    public function setName($name) { $this->name = $name; }
    /**
     * Set the location of the fleet. If a Card is sent, sets it to 'Card' and sets the otherLocation to the Card that was sent
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
        $this->setName($this->numberToRoman($num)) ;
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
        return ($this->getLocation() == 'Pool') ;
    }

    public function inRome()
    {
        return ($this->getLocation()== 'Rome') ;
    }

    public function isAway()
    {
        return ($this->location== 'Card') ;
    }

    public function recruit()
    {
        $this->setLocation('Rome') ;
    }

    public function disband() {
        $this->setLocation = 'Pool' ;
    }

    /**
     * Determines whether or not a Fleet should be maintained by Rome.
     * Which means the fleet is : in Rome, in a province (garrison) , with a non-rebel Senator
     * @return boolean
     */
    public function romeMaintenance()
    {
        if ($this->location=='Pool') { return FALSE ; } 
        if ($this->location=='Rome') { return TRUE ; } 
        if ($this->location=='Card')
        {
            if (!is_null($this->cardLocation) && $this->cardLocation->getPreciseType() == 'Senator' && !$this->cardLocation->getRebel()) { return TRUE; } 
            if (!is_null($this->cardLocation) && $this->cardLocation->getPreciseType() == 'Province' ) { return TRUE; } 
        }
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

