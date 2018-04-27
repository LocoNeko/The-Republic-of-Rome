<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

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
    
    /** 
     * @ManyToMany(targetEntity="TraceableEntity", cascade={"persist"}  ) 
     * @JoinTable(name="traces_entities",
     *      joinColumns={@JoinColumn(name="traceId", referencedColumnName="traceId")},
     *      inverseJoinColumns={@JoinColumn(name="entityId", referencedColumnName="id")}
     *      )
     **/
    private $entities ;

    /** @Column(type="string") @var string */
    protected $operation ;

    /** @Column(type="array") @var array */
    protected $parameters ;

    /**
     * 
     * @param \Entities\Game $game
     * @param string $operation
     * @param array $parameters
     * @param \ArrayCollection $entities These entities should only be used when a trace is recorded for a new entity. Without this, there's no way to know the Entity ID, since the operation occurs within the constructor, before persisting, hence before the id is known
     */
    public function __construct($game , $operation  , $parameters=NULL , $entities=NULL)
    {
        $this->game = $game ;
        $this->tick = $game->getTick() ;
        $this->operation = $operation ;
        $this->parameters = $parameters ;
        $this->entities = $entities ;
        $game->getTraces()->add($this) ;
    }
    
    /**
     * Traces format - For every type of $trace->operation, list the format of $parameters and $entities. Then the trace can be shown
     * 
     * 'log'
     * parameters : NULL
     * entities : ArrayCollection($message) ;
     *
     * 'PickLeader'
     * parameters : NULL
     * entities : ArrayCollection($party , $leader) ;
     * 
     * 'PlayStatesman' 
     * parameters : array ('familyLocation' , 'familyLocationName' , 'priorConsul' , 'INF' , 'statesmanINF' , 'POP' , 'statesmanPOP' , 'Treasury' , 'Knights' , 'Office' , 'isLeader' )
     * entities : ArrayCollection($party , $statesman , $family->getCardsControlled() , $statesman->getCardsControlled()) 
     * 
     * 'PlayConcession'
     * parameters : NULL
     * entities : ArrayCollection($recipient , $concession)
     */
}
