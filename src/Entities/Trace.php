<?php
namespace Entities ;

/**
 * @Entity @Table(name="traces")
 **/
class Trace
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $traceId ;
    
    // One Game has many traces
    /** @ManyToOne(targetEntity="Game", inversedBy="traces", cascade={"persist"}) **/
    private $game ;
    
    /** @Column(type="integer") @var int */
    private $tick ;
    
    /** @ManyToOne(targetEntity="TraceableEntity", cascade={"persist"}) @JoinColumn(name="entityTrace_id", referencedColumnName="id" , nullable=true)**/
    private $entity ;
    
    /** @Column(type="string") @var string */
    protected $propertyName ;
    
    /** @Column(type="string") @var string */
    protected $propertyClass ;
    
    /** @Column(type="object") @var string */
    protected $currentState ;

    /** @Column(type="object") @var string */
    protected $newState ;

    /**
     * @param \Entities\Game $game
     * @param \Entities\TraceableEntity $entity
     * @param string $propertyName
     * @param string  $propertyClass
     * @param serialized $currentState
     * @param serialized $newState
     */
    public function __construct($game , $entity , $propertyName , $propertyClass , $currentState , $newState)
    {
        $this->game=$game ;
        $this->tick=$game->getTick() ;
        $this->entity = $entity ;
        $this->propertyName = $propertyName ;
        $this->propertyClass = $propertyClass ;
        $this->currentState = $currentState ;
        $this->newState = $newState ;
    }
    
}