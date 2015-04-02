<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Although this is actually a Faction card, this class exists as a convenience
 * Warning : the type is 'Concession' NOT 'Faction'
 * @property int $income The concession income, can be 0 for special cases (armaments & ship building)
 * @property string $special If the concession has special ways of earning income (like land commissioner or grain)
 * values can be 'legions' , 'fleets' , 'drought' , 'land bill'
 * @property bool $corrupt whether or not the concession has generated revenue for its controlling senator
 * @property bool $flipped whether or not assigning the concession has already been proposed and rejected during the Senate phase
 */
/**
 * @Entity  @Table(name="concessions")
 **/
class Concession extends \Entities\Card
{
    public static $VALID_SPECIAL = array('legions' , 'fleets' , 'drought' , 'land bill' ) ;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $income ;
    /**
    * @Column(type="string", nullable=true)
    * @var string
    */
    protected $special = NULL;
    /**
     * @Column(type="boolean")
     * @var int
     */
    protected $corrupt = FALSE ;
    /**
     * @Column(type="boolean")
     * @var int
     */
    protected $flipped = FALSE ;
    
    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
    public function setIncome($income) {
        $this->income = (int)$income ;
    }
    
    public function setSpecial($special) {
        $this->special = ( (in_array($special , self::$VALID_SPECIAL)) ? $special : NULL );
    }
    
    public function setCorrupt($corrupt) {
        $this->corrupt = (bool)$corrupt ;
    }
    
    public function setFlipped($flipped) {
        $this->flipped = (bool)$flipped ;
    }
    
    public function getIncome() {
        return $this->income ;
    }

    public function getSpecial() {
        return $this->special ;
    }
    
    public function getCorrupt() {
        return $this->corrupt ;
    }
    
    public function getFlipped() {
        return $this->flipped ;
    }

    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    public function __construct(Game $game , $data) {
        parent::__construct($game, (int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) ) ;
        $this->setIncome((int)$data[4]) ;
        $this->setSpecial(($data[5]=='' ? NULL : $data[5])) ;
    }

}