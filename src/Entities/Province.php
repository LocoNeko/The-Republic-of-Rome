<?php
namespace Entities ;

/**
 * @Entity  @Table(name="provinces")
 **/
class Province extends Card
{
    /** @Column(type="integer") @var int */
    protected $mandate ;
    
    /** @Column(type="boolean") @var int */
    protected $developed = FALSE ;
    
    /** @Column(type="boolean") @var int */
    protected $overrun = FALSE ;
    
    /** @Column(type="boolean") @var int */
    protected $frontier = FALSE ;
    
    /** @Column(type="array") @var array */
    protected $income = array() ;
    
    /** @Column(type="array") @var array */
    protected $forces = array() ;
 
    /**
     * ----------------------------------------------------
     * Getters & Setters
     * ----------------------------------------------------
     */
    
    public function setMandate ($mandate) { $this->mandate = $mandate ; }
    public function setDeveloped ($developed) { $this->developed = $developed ; }
    public function setOverrun ($overrun) { $this->overrun = $overrun ; }
    public function setFrontier ($frontier) { $this->frontier = $frontier ; }
    public function setIncome ($income) { $this->income = $income ; }
    public function setForces ($forces) { $this->forces = $forces ; }
    
    public function getMandate () { return $this->mandate ; }
    public function getDeveloped () { return $this->developed ; }
    public function getOverrun () { return $this->overrun ; }
    public function getFrontier () { return $this->frontier ; }
    public function getIncome () { return $this->income ; }
    public function getForces () { return $this->forces ; }

    public function __construct($data) {
        parent::__construct((int)$data[0], ( is_string($data[1]) ? $data[1] : NULL ) , 'Province' ) ;
        $this->setMandate (0) ;
        $this->setDeveloped (FALSE) ;
        $this->setOverrun (FALSE) ;
        $this->setFrontier ($data[15]) ;
        $this->setIncome(
            Array (
                'undeveloped' => Array(
                    'senator' => Array ( 'variable' => (int)$data[3] , 'fixed' => (int)$data[4] ),
                    'rome'    => Array ( 'variable' => (int)$data[5] , 'fixed' => (int)$data[6] )
                ) ,
		'developed' => Array (
                    'senator' => Array ( 'variable' => (int)$data[7] , 'fixed' => (int)$data[8] ),
                    'rome'    => Array ( 'variable' => (int)$data[9] , 'fixed' => (int)$data[10])
                )
            )
        ) ;
        $this->setForces(
            Array (
                'undeveloped' => Array ( 'land' => (int)$data[11] , 'sea'  => (int)$data[12] ) ,
                'developed'   => Array ( 'land' => (int)$data[13] , 'sea'  => (int)$data[14] )
            )
        ) ;
    }
            
    public function saveData() {
        $data = array() ;
        $data['id'] = $this->getId() ;
        $data['name'] = $this->getName() ;
        $data['mandate'] = $this->getMandate () ;
        $data['developed'] = $this->getDeveloped () ;
        $data['overrun'] = $this->getOverrun () ;
        $data['frontier'] = $this->getFrontier () ;
        $data['income'] = $this->getIncome () ;
        $data['forces'] = $this->getForces () ;
        return $data ;
    }

    /**
     * ----------------------------------------------------
     * Other methods
     * ----------------------------------------------------
     */

    /**
     * Returns the Province's revenues either for Rome or the Senator (in case of Provincial spoils)<br>
     * Evil Omens do not affect the revenue roll, but the total, so the current Evil Omens event level is passed as $modifier
     * @param string $type 'rome'|'senator'
     * @param int $modifier
     * @return type
     */
    public function rollRevenues($type , $modifier)
    {
        $status = ($this->getDeveloped()) ? 'developed' : 'undeveloped' ;
        $variable = (int)$this->getIncome()[$status][$type]['variable']*mt_rand(1,6) ;
        $fixed = (int)$this->getIncome()[$status][$type]['fixed'] ;
        return ($variable + $fixed + (int)$modifier);
    }

    /**
    * Returns the value of local land forces, based on current status
    * @return integer
    */
    public function getLand()
    {
        $status = ($this->getDeveloped()) ? 'developed' : 'undeveloped' ;
        return $this->getForces()[$status]['land'] ;
    }

   /**
    * Returns the value of local sea forces, based on current status
    * @return integer
    */
    public function getSea()
    {
        $status = ($this->getDeveloped()) ? 'developed' : 'undeveloped' ;
        return $this->getForces()[$status]['sea'] ;
    }

}
