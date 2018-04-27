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
    public function __construct($operation  , $parameters=NULL , $entities=NULL)
    {
        $this->operation = $operation ;
        $this->parameters = $parameters ;
        $this->entities = $entities ;
    }
    
    /**
     * describe() calls function with the format describe{operation}
     * Describe traces.
     * 
     * Traces format - For every type of $trace->operation, list the format of $parameters and $entities. Then the trace can be shown
     * 
     * 
     * 'PlayStatesman' 
     * parameters : array ('familyLocation' , 'familyLocationName' , 'priorConsul' , 'INF' , 'statesmanINF' , 'POP' , 'statesmanPOP' , 'Treasury' , 'Knights' , 'Office' , 'isLeader' )
     * entities : ArrayCollection($party , $statesman , $family->getCardsControlled() , $statesman->getCardsControlled()) 
     * 
     * 'PlayConcession'
     * parameters : NULL
     * entities : ArrayCollection($recipient , $concession)
     * @throws \Exception
     */
    public function describe()
    {
        $methodName = 'describe'.$this->operation ;
        if (method_exists($this , $methodName))
        {
            try {
                return $this->$methodName();
            } catch (Exception $ex) {
                return FALSE ;
            }
        }
        else
        {
            return FALSE ;
        }
    }

    /**
     * 'PickLeader'
     * parameters : array(finished -> boolean)
     * entities : ArrayCollection($party , $leader) ;
     * @return string
     * @throws \Exception
     */    
    function describePickLeader()
    {
        try 
        {
            $party = $this->entities->first() ;
            $leader = $this->entities->next() ;
            $finished = ($this->parameters['finished'] ? _('. All leaders picked, moving to play cards sub phase.') : '' );
            return sprintf(_('%1$s appointed leader of %2$s%3$s') , $leader->getName() , $party->getName() , $finished);
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
}
