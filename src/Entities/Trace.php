<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity @Table(name="traces")
 **/
class Trace
{
    public static $VALID_OPERATIONS = array('PickLeader' , 'PlayStatesman' , 'PlayConcession' , 'DonePlayingCards' , 'RevenueRedistribute' , 'RevenueContributions' , 'Proposal') ;

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

    /**
     * There is no way to sort $entities, so I save their order in an array.
     * using this method, I can actually use more descriptive keys than relying on order 
     * @Column(type="array") @var array */
    private $entitiesOrder ;
    
    /** @todo unfortunately, I need an entity order */

    /** @Column(type="string") @var string */
    protected $operation ;

    /** @Column(type="array") @var array */
    protected $parameters ;

    public static function isValidOperation($operation)
    {
        return in_array($operation , self::$VALID_OPERATIONS) ;
    }

    /**
     * 
     * @param string $operation
     * @param array $parameters
     * @param array $orderedEntities These entities should only be used when a trace is recorded for a new entity. Without this, there's no way to know the Entity ID, since the operation occurs within the constructor, before persisting, hence before the id is known
     * this parameter is passed in the format of an array of 'key' => entity
     */
    public function __construct($operation  , $parameters=NULL , $orderedEntities=NULL)
    {
        $this->operation = $operation ;
        $this->parameters = $parameters ;
        if ($orderedEntities)
        {
            $this->entities = new ArrayCollection();
        }
        $this->entitiesOrder = array() ;
        foreach ($orderedEntities as $key=>$entity)
        {
            $this->entities->add($entity) ;
            $this->entitiesOrder[$key] = $entity->getId() ;
        }
    }
    
    public function getOperation() 
    {
        return (self::isValidOperation($this->operation) ? $this->operation : FALSE) ;
    }
    public function getParameters() { return $this->parameters ; }

    
    /**
     * 
     * @param string $key
     */
    public function getEntity($key) 
    {
        foreach ($this->entities as $entity)
        {
            if ($entity->getId()==$this->entitiesOrder[$key])
            {
                return $entity ;
            }
        }
    }
        /**
     * describe() calls function with the format describe{operation}
     * Describe traces.
     * 
     * Traces format - For every type of $trace->operation, list the format of $parameters and $entities. Then the trace can be shown
     * 
     * 'PickLeader'
     * 'PlayStatesman' 
     * 'PlayConcession'
     * 'DonePlayingCards'
     * 
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
     * entities : 'party' => $party , 'leader' => $leader
     * @return string
     * @throws \Exception
     */    
    function describePickLeader()
    {
        try 
        {
            $party = $this->getEntity('party') ;
            $leader = $this->getEntity('leader') ;
            $finished = ($this->parameters['finished'] ? _('. All leaders picked, moving to play cards sub phase.') : '' );
            return sprintf(_('%1$s appointed leader of %2$s%3$s') , $leader->getName() , $party->getName() , $finished);
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }

    /**
     * 'PlayStatesman' 
     * parameters : array ('familyLocation' , 'familyLocationName' , 'wasInTheParty' , 'priorConsul' , 'INF' , 'statesmanINF' , 'POP' , 'statesmanPOP' , 'Treasury' , 'Knights' , 'Office' , 'isLeader' )
     * entities : ArrayCollection($party , $statesman , $family) 
     * 
     * @return string
     * @throws \Exception
     */
    function describePlayStatesman()
    {
        try 
        {
            $party = $this->getEntity('party') ;
            /* @var $statesman \Entities\Senator */
            $statesman = $this->getEntity('statesman') ;
            $family = $this->getEntity('family') ;
            return sprintf(_('%1$s played statesman %2$s') , $party->getName() , $statesman->getName());
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
    
    /**
     * 'PlayConcession' 
     * parameters : NULL
     * entities : ArrayCollection($recipient , $concession , $party)
     * 
     * @return string
     * @throws \Exception
     */
    function describePlayConcession()
    {
        try {
            $party = $this->getEntity('party') ;
            $recipient = $this->getEntity('recipient') ;
            $concession = $this->getEntity('concession') ;
            return sprintf(_('%1$s plays concession %2$s on %3$s') , $party->getName() , $concession->getName() , $recipient->getName());
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }

    /**
     * 'DonePlayingCards'
     * parameters : NULL
     * entities : ArrayCollection($party)
     * 
     * @return string
     * @throws \Exception
     */
    function describeDonePlayingCards()
    {
        try {
            $party = $this->getEntity('party') ;
            return sprintf(_('%1$s is done playing cards') , $party->getName());
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }    
    
    /**
     * 'RevenueRedistribute'
     * parameters : array('user_id' , 'fromName' , 'amount' , 'fromParty' , 'toParty')
     * entities : ArrayCollection($fromEntity , $toEntity)
     * 
     * @return string
     * @throws \Exception
     */
    function describeRevenueRedistribute()
    {
        try {
            $fromEntity = $this->getEntity('fromEntity') ;
            $toEntity = $this->getEntity('toEntity') ;
            return sprintf(_('%1$s transfers %2$d T. from %3$s to %4$s') , $this->parameters['fromName'] , $this->parameters['amount'] , $fromEntity->getName() , $toEntity->getName() );
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
    
    /**
     * 'RevenueContributions'
     * parameters : array('amount' , 'INFgain')
     * entities : ArrayCollection($giver)
     * 
     * @return string
     * @throws \Exception
     */
    function describeRevenueContributions()
    {
        try {
            $giver = $this->getEntity('giver') ;
            return sprintf(_('%1$s gives %2$d T. toRome') , $giver->getName() , $this->parameters['amount']);
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
    
    /**
     * 'Proposal'
     * parameters : array( 'action')
     * entities : ArrayCollection($proposal)
     * 
     * @return string
     * @throws \Exception
     */
    function describeProposal()
    {
        try {
            $proposal = $this->getEntity('proposal') ;
            return sprintf(_('Proposal %1$s , action : %2$s') , $proposal->getType() , $this->parameters['action']);
        } catch (Exception $ex) {
            throw new \Exception($ex);
        }
    }
    
}
