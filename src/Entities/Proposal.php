<?php
namespace Entities ;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @Entity @Table(name="proposals")
 * - vote is in the form of an array :
 * > keys are integer from 0 and give the order of vote
 * > values are arrays of 'user_id' , 'votes' , 'description'
 **/
class Proposal
{
    const ERROR_NO_VOTER = 1;
    
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

    /**
     * An array of cards id<br>
     * keys are meanginful strings (like 'First Senator' , 'Prosecutor' ... )<br>
     * values are card ids, <b>including</b> for Senators<br>
     * @Column(type="array") @var array */
    private $cards ;

    // A Proposal has a Party that proposed it
    /** @ManyToOne(targetEntity="Party" , inversedBy="proposed" , cascade={"persist"}) **/
    private $proposedBy ;
    
    /** @Column(type="string") @var string 
     * Can be 'underway' , 'pass' , or 'fail'
     */
    private $outcome = 'underway';

    /** @Column(type="array") @var array */
    private $vote = array() ;

    /** @Column(type="array") @var array */
    private $agree = array() ;

    /**
     * TO DO : Implement / remove all this below.  
     */
    
    /** @Column(type="array") @var array */
    private $constraints = array() ;

    /** @Column(type="array") @var array */
    private $conditions = array() ;

    /**
     * @param int $user_id
     * @param type $type
     * @param \Entities\Game $game
     * @param $json_data
     * @throws \Exception
     */
    public function __construct($user_id , $type , $game , $json_data)
    {
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
            $this->vote = array() ;
            $i = 0 ;
            foreach ($json_data['senateListVotingOrder'] as $votingOrderUser_id)
            {
                $this->vote[$i++] = array (
                    'user_id' => (int)$votingOrderUser_id ,
                    'votes' => NULL ,
                    'description' => ''
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
                1 => 'agree' ,
                2 => 'done'
            ) ;
            $this->step = 0 ;
            $this->agree = array ('Rome consul' => NULL , 'Field consul' => NULL);
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
    public function getAgree()      { return $this->agree; }

    public function setOutcome($outcome) { $this->outcome = $outcome; }

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
            // REMINDER : the cards array is by card ids, NOT senator ids...
            $this->cards = array ( 
                'First Senator' => $First_Senator->getId() , 
                'Second Senator' => $Second_Senator->getId() 
            ) ;
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
            $FirstSenatorName = $this->game->getFilteredCards(array('id'=>$this->cards['First Senator']))->first()->getFullName() ;
            $SecondSenatorName = $this->game->getFilteredCards(array('id'=>$this->cards['Second Senator']))->first()->getFullName() ;
            return sprintf(_('%1$s is proposing %2$s and %3$s as Consuls.') , $this->proposedBy->getFullName() , $FirstSenatorName , $SecondSenatorName) ;
        }
        // TO DO : all other proposals
    }
 
    /**
     * 
     * @return string Description of the current step ('vote' , 'agree' , 'appoint' ...)
     * @throws \Exception
     */
    public function getCurrentStep()
    {
        try {
            return $this->flow[$this->step] ;
        } catch (Exception $ex) {
            throw new \Exception(_('Invalid step for this proposal')) ;
        }
    }

    /**
     * Returns the user id of the current voter for this proposal, or throws an exception
     * @return int $user_id
     * @throws \Exception
     */
    public function getCurrentVoter()
    {
        if ($this->outcome!='underway')
        {
            throw new \Exception(_('No vote underway')) ;
        }
        foreach ($this->vote as $vote)
        {
            if ($vote['votes']==NULL)
            {
                return $vote['user_id'] ;
            }
        }
        throw new \Exception(_('No voters left') , self::ERROR_NO_VOTER) ;
    }
    
    /**
     * @return string A string showing the voting order using party->getFullName()
     */
    public function getVotingOrder()
    {
        $result ='' ;
        foreach ($this->vote as $vote)
        {
            $result.=$this->game->getParty($vote['user_id'])->getFullName().', ';
        }
        return substr($result , 0 , -2) ;
    }
    
    /**
     * Returns a state and a message describing the current voting situation for this user_id
     * @param int $user_id
     * @return array 'message' => 'Waiting for XXX' | 'This is your turn to vote' , 'state' => 'waiting'|'voting'
     * @throws \Exception
     */
    public function getVotingOrWaiting($user_id)
    {
        foreach ($this->vote as $vote)
        {
            // party hasn't voted, and is not you : waiting
            if ((int)$vote['user_id']!==(int)$user_id && $vote['votes']===NULL)
            {
                return array('message' => sprintf(_('Waiting for %1$s') , $this->game->getParty($vote['user_id'])->getFullName() )  , 'state' => 'waiting') ;
            }
            // Party hasn't voted and is you
            elseif ((int)$vote['user_id']===(int)$user_id && $vote['votes']===NULL)
            {
                return array('message' => _('This is your turn to vote') , 'state' => 'voting') ;
            }
        }
        throw new \Exception(_('Invalid voting state')) ;
    }
    
    /**
     * Returns a complex array describing the vote tally of a user senator by senator. Array is presenter-ready
     * @param int $user_id
     * @return array 
     * @throws \Exception
     */
    public function getVoteTally($user_id)
    {
        $result = [] ;
        try 
        {
            $party = $this->game->getParty($user_id) ;
        } catch (Exception $ex) {
            throw new \Exception(_('Couldn\'t find party')) ;
        }
        try
        {
            /* @var $senator \Entities\Senator  */
            foreach($party->getSenators()->getCards() as $senator)
            {
                $currentSenator = array() ;
                $currentSenator['name'] = $senator->getName() ;
                $currentSenator['senatorID'] = $senator->getSenatorID() ;
                // Is in Rome : can vote
                if ($senator->checkCriteria('alignedInRome'))
                {
                    $oratory = $senator->getORA() ;
                    $knights = $senator->getKnights() ;
                    $currentSenator['votes'] = $oratory + $knights ;
                    // Tooltip
                    $knightsTooltip = ($knights==0 ? '' : sprintf(_(' and %1$d knights') , $knights)) ;
                    $currentSenator['attributes'] = array (
                       'data-toggle' => 'popover' ,
                       'data-content' => sprintf(_('%1$d votes from %2$s Oratory%3$s.') , $currentSenator['votes'] , $oratory , $knightsTooltip ) ,
                       'data-trigger' => 'hover' ,
                       'data-placement' => 'bottom'
                    ) ;
                    // TO DO  : Add INF for Prosecutions & Consul for life
                    // Dropdown for spedning talents
                    $treasury = $senator->getTreasury() ;
                    if ($treasury>0)
                    {
                        $items = array() ;
                        for ($i=0 ; $i<=$treasury ; $i++)
                        {
                            $items[] = array (
                                'value' => $i ,
                                'description' => $i." T."
                            ) ;
                        }
                        $currentSenator['talents']= array (
                            'type' => 'select' ,
                            'class' => 'senatorVoteTalents_'.$senator->getSenatorID() ,
                            'items' => $items
                        ) ;
                    }
                    else
                    {
                        $currentSenator['talents'] = 0 ;
                    }
                    // Toggle for split vote (when a senator votes differently from the party)
                    $currentSenator['splitVote'] = array (
                        'type'  => 'toggle' ,
                        'name' => $senator->getSenatorID() ,
                        'class' => 'toggleSenatorVote' ,
                        'items' => array(
                            array('value' => 'FOR'     , 'description' =>_('FOR')) ,
                            array('value' => 'AGAINST' , 'description' =>_('AGAINST')) ,
                            array('value' => 'ABSTAIN' , 'description' =>_('ABSTAIN'))
                        )
                    ) ;
                }
                // For Senators who cannot vote
                else
                {
                    $currentSenator['votes'] = 0 ;
                    $currentSenator['talents'] = 0 ;
                    // Tooltip
                    $currentSenator['attributes'] = array (
                       'data-toggle' => 'popover' ,
                       'data-content' => _('Cannot vote.') ,
                       'data-trigger' => 'hover' ,
                       'data-placement' => 'bottom'
                    ) ;
                }
                $result[] = $currentSenator ;
            }
        } catch (Exception $ex) {
            throw new \Exception(_('ERROR retrieving senator vote')) ;
        }
        return $result ;
    }

    /**
     * Sets the votes and their description for this proposal and this user_id
     * @param int $user_id
     * @param int $votes
     * @param string $description
     * @return TRUE
     * @throws \Exception
     */
    public function setVote($user_id , $votes , $description) 
    {
        foreach ($this->getVote() as $i=>$vote)
        {
            if ($vote['user_id']==$user_id)
            {
                $this->vote[$i]['votes'] = $votes ;
                $this->vote[$i]['description'] = $description ;
                return TRUE ;
            }
        }
        throw new \Exception(_('ERROR - user not found')) ;
    }

    /**
     * Returns TRUE if the proposal would pass with current votes
     * @return bool
     */
    public function isCurrentOutcomePass()
    {
        $totalVotes = 0 ;
        foreach ($this->getVote() as $aVote)
        {
            if ($aVote['votes']!=NULL)
            {
                $totalVotes+=$aVote['votes'] ;
            }
        }
        return ($totalVotes>0) ;
    }
    
    /**
     * Adds 1 to this proposal flow
     */
    public function incrementStep()
    {
        $this->step++;
    }
}
