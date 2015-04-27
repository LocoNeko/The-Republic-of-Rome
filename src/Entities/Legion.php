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

    // A Legion can be on a non-Card named location ("Rome" , "Pool")
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

    public function canBeRecruited()
    {
        return ($this->getOtherLocation() == 'Pool') ;
    }

    public function canBeDisbanded()
    {
        return ($this->getOtherLocation()== 'Rome' || $this->getOtherLocation() == 'released') ;
    }
    
    public function recruit()
    {
        $this->setOtherLocation('Rome') ;
        $this->setCardLocation(NULL) ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
    }

    public function disband() {
        $this->setOtherLocation(NULL) ;
        $this->setCardLocation(NULL) ;
        $this->setVeteran(FALSE) ;
        $this->setLoyalTo(NULL) ;
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
