<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="proposals")
 * - vote is in the form of an array :
 * > keys are integer from 0 and give the order of vote
 * > values are arrays of 'user_id' , 'votes' , 'split_vote'
 **/
class Proposal
{
    /** @Id @Column(type="integer") @GeneratedValue @var int */
    protected $id ;

    /** @ManyToOne(targetEntity="Game", inversedBy="proposals" , cascade={"persist"}) **/
    private $game ;

    /** @Column(type="string") @var string */
    private $type ;

    // Possible values : 'vote' , 'agree' , 'appoint'
    /** @Column(type="array") @var array */
    private $flow = array() ;

    /** @Column(type="integer") @var int */
    private $step ;

    // A Proposal can have many Cards
    /** @ManyToMany(targetEntity="Card", mappedBy="partOfProposal" , cascade={"persist"})
     *  @JoinTable(
     *      name="proposal_card",
     *      joinColumns={@JoinColumn(name="Proposal_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="Card_internalId", referencedColumnName="internalId")}
     *  )**/
    private $cards ;

    // A Proposal has a Party that proposed it
    /** @ManyToOne(targetEntity="Party" , inversedBy="proposed" , cascade={"persist"}) **/
    private $proposedBy ;

    /** @Column(type="string") @var string */
    private $outcome = 'underway';

    /** @Column(type="array") @var array */
    private $vote = array() ;

    /**
     * TO DO : Implement / remove all this below.  
     */
    
    /** @Column(type="array") @var array */
    private $constraints = array() ;

    /** @Column(type="array") @var array */
    private $conditions = array() ;

    /** @Column(type="array") @var array */
    private $agree = array() ;

    /**
     * @param int $user_id
     * @param type $type
     * @param \Entities\Game $game
     * @param $json_data
     * @throws \Exception
     */
    public function __construct($user_id , $type , $game , $json_data)
    {
        $this->cards = new ArrayCollection() ;
        $this->game = $game ;

        /**
         * First, check if the constraints for this proposal are satisfied
         * This also sets the cards in the proposal
         */
        try
        {
            $this->checkConstraints($type, $game , $json_data) ;
            $this->proposedBy = $game->getParty($user_id) ;
        }
        catch (\Exception $ex)
        {
            throw new \Exception(_('WRONG PROPOSAL - ').$ex->getMessage()) ;
        }
        
        /**
         * Second, check & set the voting order
         */
        try 
        {
            $this->vote = [] ;
            $i = 0 ;
            foreach ($json_data['senateListVotingOrder'] as $votingOrderUser_id)
            {
                $this->vote[$i++] = array (
                    'user_id' => $votingOrderUser_id ,
                    'votes' => 0 ,
                    'split_vote' => ''
                );
            }
        } catch (Exception $ex) {
            throw new \Exception(_('ERROR - Problem with voting order')) ;
        }

        /**
         * Type-specific setup : flow, etc
         */
        if ($type=='Consuls')
        {
            $this->type = 'Consuls' ;
            $this->flow = array (
                0 => 'vote' ,
                1 => 'agree'
            ) ;
            $this->step = 0 ;
        }
        // TO DO : all other types of proposals
        
    }

    public function getType()       { return $this->type ; }
    public function getFlow()       { return $this->flow; }
    public function getStep()       { return $this->step; }
    public function getCards()      { return $this->cards; }
    public function getProposedBy() { return $this->proposedBy; }
    public function getOutcome()    { return $this->outcome ; }
    public function getVote()       { return $this->vote; }

     /**
     * 
     * @param type $type
     * @param \Entities\Game $game
     * @param $json_data
     *  {
     *      "user_id" ,
     *      "senateMakeProposal" : an array describing how the proposal was made , 
     *      "senateListVotingOrder" : an array of user_id
     *      "verb": "senateMakeProposal" 
     *  } 
     */
    public function checkConstraints($type, $game , $json_data)
    {
        /**
         * Consuls :
         * specific json : { "First_Senator" ,  "Second_Senator" } 
         * Constraints :
         * - Both Senators must be different , in Rome , and possible consuls (see Senator->checkCriteria)
         * - The pair cannot have been proposed already
         */
        if ($type=='Consuls')
        {
            // ERROR - Pair of one
            if ($json_data['First_Senator'] == $json_data['Second_Senator'])
            {
                throw new \Exception(_('This is a pair of one.')) ;
            }
            /* @var $First_Senator \Entities\Senator  */
            /* @var $Second_Senator \Entities\Senator  */
            // ERROR - Couldn't find one fo the Senator
            try 
            {
                $First_Senator = $game->getFilteredCards(array('senatorID'=>$json_data['First_Senator']) , 'possibleConsul')->first() ;
                $Second_Senator = $game->getFilteredCards(array('senatorID'=>$json_data['Second_Senator']) , 'possibleConsul')->first() ;
            } catch (Exception $ex) {
                throw new \Exception(_('Senator doesn\'t exist or is not in Rome')) ;
            }
            $this->cards->set('First Senator' , $First_Senator);
            $this->cards->set('Second Senator' , $Second_Senator);
            $First_Senator->setAsPartOfProposal($this) ;
            $Second_Senator->setAsPartOfProposal($this) ;
            // TO DO : Check already proposed pairs
        }        
        // TO DO : Check constraints of all other proposals
    }
    
    /**
     * @return string A description of the proposal in the form "Party * is proposing *"
     */
    public function getDescription()
    {
        if ($this->type=='Consuls')
        {
            $this->getCards()->first() ;
            $FirstSenatorName = $this->getCards()->current()->getFullName() ;
            $this->getCards()->next() ;
            $SecondSenatorName = $this->getCards()->current()->getFullName() ;
            return sprintf(_('%1$s is proposing %2$s and %3$s as Consuls.') , $this->proposedBy->getFullName() , $FirstSenatorName , $SecondSenatorName) ;
        }
        // TO DO : all other proposals
    }
    
}