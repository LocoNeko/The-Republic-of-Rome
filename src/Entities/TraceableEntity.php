<?php
namespace Entities ;

/**
 * @Entity @Table(name="traceableEntities")
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="entityClass", type="string")
 * @DiscriminatorMap({ "Card" = "Card" , "Proposal" = "Proposal"  , "Party" = "Party" , "Deck" = "Deck" , "Fleet" = "Fleet" , "Legion" = "Legion" ,"Message" = "Message"})
 */
abstract class TraceableEntity
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;
    
    /**
     * For traceable entities when a property is changed and the property was an object, we store the id.
     * @todo If all properties that are objects are also traceableentities (basically everything but game), can't we store the traceableEntity itself ?  
     * @return type
     */
    public function getId() 
    { 
        return $this->id; 
    }

}