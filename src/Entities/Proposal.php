<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="proposals")
 **/
class Proposal
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;

    /** @ManyToOne(targetEntity="Game", inversedBy="proposals") **/
    private $game ;

    /** @Column(type="string") @var string */
    private $type ;

    /** @Column(type="array") @var array */
    private $flow = array() ;

    /** @Column(type="integer") @var int */
    private $step ;

    /** @Column(type="array") @var array */
    private $constraints = array() ;

    /** @Column(type="array") @var array */
    private $conditions = array() ;

    /** @Column(type="array") @var array */
    private $senators = array() ;

    /** @Column(type="array") @var array */
    private $cards = array() ;

    /** @Column(type="array") @var array */
    private $vote = array() ;

    /** @Column(type="array") @var array */
    private $appoint = array() ;

    /** @Column(type="array") @var array */
    private $agree = array() ;

    /** @Column(type="string") @var string */
    private $outcome = 'underway';

    public function __construct($type)
    {
        if ($type=='Consuls')
        {
            $this->type = 'Consuls' ;
            $this->flow = array (
                0 => 'content' ,
                1 => 'vote' ,
                2 => 'agree'
            ) ;
            $this->step = 0 ;
        }
    }

    public function getType() { return $this->type ; }
    public function getOutcome() { return $this->outcome ; }
}