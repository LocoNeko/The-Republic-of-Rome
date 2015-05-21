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

    public function canBeRecruited()
    {
        return ($this->getOtherLocation() == 'Pool') ;
    }

    public function canBeDisbanded()
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

