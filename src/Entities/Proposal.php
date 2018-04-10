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

    // Possible values : 'vote' , 'decision' , 'appoint'
    /** @Column(type="array") @var array */
    private $flow = array() ;

    /** @Column(type="integer") @var int */
    private $step ;

    /**
     * An array of cards id, text, values, etc...<br>
     * keys are meanginful strings (like 'First Senator' , 'Prosecutor' ... )<br>
     * For cards, use card ids, <b>even</b> for Senators<br> (not SenatorID)
     * @Column(type="array") @var array */
    private $content ;

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
    private $decision = array() ;

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
                1 => 'decision' , // Consuls must decide who becomes what
                2 => 'done'
            ) ;
            $this->step = 0 ;
            $this->decision = array ('First Senator' => NULL , 'Second Senator' => NULL);
        }
        /**
         * Censor
         */
        if ($type=='Censor')
        {
            $this->type = 'Censor' ;
            $this->flow = array (
                0 => 'vote' ,
                1 => 'done'
            ) ;
            $this->step = 0 ;
        }
        /**
         * Prosecutions
         */
        if ($type=='Prosecutions')
        {
            $this->type = 'Prosecutions' ;
            $this->flow = array (
                0 => 'decision' , // Prosecutor must agree
                1 => 'vote' ,
                2 => 'done'
            ) ;
            $this->step = 0 ;
        }
        /**
         * OtherBusiness - recruit
         */
        if ($type=='recruit')
        {
            $this->type = 'recruit' ;
            $this->flow = array (
                0 => 'vote' ,
                1 => 'done'
            ) ;
            $this->step = 0 ;
        }
        /**
         * OtherBusiness - commander
         */
        if ($type=='commander')
        {
            $this->type = 'commander' ;
            $this->flow = array (
                0 => 'decision' , // Commander must agree to go if forces are inadequate
                1 => 'vote' ,
                2 => 'done'
            ) ;
            $this->step = 0 ;
        }
        // TO DO : all other types of proposals
    }

    public function getId()         { return $this->id; }
    public function getType()       { return $this->type ; }
    public function getFlow()       { return $this->flow; }
    public function getStep()       { return $this->step; }
    public function getContent()    { return $this->content; }
    public function getProposedBy() { return $this->proposedBy; }
    public function getOutcome()    { return $this->outcome ; }
    public function getVote()       { return $this->vote; }
    public function getDecision()      { return $this->decision; }

    public function setOutcome($outcome) { $this->outcome = $outcome; }

     /**
     * Check constraints and if passed, sets content of the proposal
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
            // REMINDER : the content array has card ids, NOT senator ids...
            // TO DO : Which is RETARDED. Change to SenatorID
            $this->content = array ( 
                'First Senator' => $First_Senator->getId() , 
                'Second Senator' => $Second_Senator->getId() 
            ) ;
            // TO DO : Check already proposed pairs
        }
        /**
         * Prosecutions :
         * specific json :
         * - Prosecutor : SenatorID of prosecutor
         * - Prosecution : json {type=>'Minor|Major' , senatorID , cardId if applicable}
         */
        if ($type=='Prosecutions')
        {
            $this->content = [] ;
            try {
                $prosecutor = $game->getFilteredCards(array('senatorID'=>$json_data['Prosecutor']))->first() ;
                $this->content['Prosecutor'] = $prosecutor->getSenatorID() ;
            } catch (Exception $ex) {
                throw new \Exception(_('Could not retrieve the Prosecutor. He might be hiding.')) ;
            }
            try {
                $prosecution = json_decode($json_data['Prosecution'],TRUE) ;
                $accused = $game->getFilteredCards(array('senatorID'=>$prosecution['senatorID']))->first() ;
                $this->content['Accused'] = $accused->getSenatorID() ;
                $this->content['Type'] = $prosecution['Type'] ;
                if (array_key_exists('cardId' , $prosecution))
                {
                    $this->content['Card'] = $prosecution['cardId'] ;
                }
            } catch (Exception $ex) {
                throw new \Exception(_('Error in the prosecution.')) ;
            }
        }
        /**
         * Recruit
         */
        if ($type=='recruit')
        {
            try {
                $fleetStatus = $game->getFleetStatus() ;
                $fleetsToRecruit = (int)$json_data['otherBusinessRecruitFleets'] ;
                if ($fleetStatus['canBeRecruited']<$fleetsToRecruit)
                {
                    throw new \Exception(_('ERROR - Not enough fleet to recruit.')) ;
                }
            } catch (Exception $ex) {
                throw new \Exception(_('ERROR - '.$ex->getTraceAsString())) ;
            }
            try {
                $legionStatus = $game->getLegionsStatus() ;
                $regularsToRecruit = (int)$json_data['otherBusinessRecruitRegulars'] ;
                if ($legionStatus['regularsCanBeRecruited']<$regularsToRecruit)
                {
                    throw new \Exception(_('ERROR - Not enough regulars to recruit.')) ;
                }
            } catch (Exception $ex) {
                throw new \Exception(_('ERROR - '.$ex->getTraceAsString())) ;
            }
            if ( ( ($regularsToRecruit*$legionStatus['cost']) + ($fleetsToRecruit*$fleetStatus['cost']) ) > $game->getTreasury() )
            {
                throw new \Exception(_('ERROR - Not enough money in Rome treasury.')) ;
            }
            $this->content = array (
                'fleetsToRecruit' => $fleetsToRecruit ,
                'regularsToRecruit' => $regularsToRecruit
            );
        }
        /**
         * Commander
         */
        if ($type=='commander')
        {
            try {
                $data = $json_data['dynamicSections']['commander'] ;
            } catch (Exception $ex) {
                throw new \Exception(_('ERROR - Problem with Commander proposal data.')) ;
            }
            $content = array() ;
            $decision = array() ;
            // Go through each item in the proposal
            $totalFleetsInRome = $game->getFleetStatus()['inRome'];
            $totalRegularsInRome = $game->getLegionsStatus()['regularsInRome'];
            $commanderIDs = array() ;
            foreach ($data as $key=>$item)
            {
                $totalFleetsInRome -= (int)$item['otherBusinessCommanderFleets'];
                $totalRegularsInRome -= (int)$item['otherBusinessCommanderRegulars'];
                $commanderIDs[] = $item['otherBusinessCommanderSenator'] ;
                $content[$key] = array(
                    'commander' => $item['otherBusinessCommanderSenator'] ,
                    'conflict' => $item['otherBusinessCommanderConflict'] ,
                    'fleets' => (int)$item['otherBusinessCommanderFleets'] ,
                    'regulars' => (int)$item['otherBusinessCommanderRegulars']
                    // TO DO : Veterans
                ) ;
                // decision are in an array of the form senatorID => decision (initialised to NULL)
                $decision[$item['otherBusinessCommanderSenator']] = NULL ;
            }
            // Check if there are enough fleets
            if ($totalFleetsInRome<0)
            {
                throw new \Exception(_('ERROR - There aren\'t enough flets in Rome to fulfil this proposal.')) ;
            }
            // Check if there are enough regulat legions
            if ($totalRegularsInRome<0)
            {
                throw new \Exception(_('ERROR - There aren\'t enough regulars in Rome to fulfil this proposal.')) ;
            }
            // Check whether commanders are all different
            if (count($commanderIDs) !== count(array_unique($commanderIDs)))
            {
                throw new \Exception(_('ERROR - The same commander has been sent to fight more than once')) ;
            }
            // TO DO :
            // Check whether Correct veterans are sent
            // Check whether there are enough support fleets when legions are sent
            // Check whether conflict is attackable : right deck, attacked in the right sequence (e.g. First Punic beofre or at the same time as Second Punic)
            // Check whether commanders are sent in the right order (Field Consul before or at the same time as Rome Consul). In other words : can't send Rome Consul, except if Field Consul has already been sent OR will be sent in the same proposal
            $this->content = $content ;
            $this->decision = $decision ;
            // TO DO : Set decision to agreed automatically if minimum forces are met. Reember that Fleet-only battles are possible
            
        }
        // TO DO : Check constraints of all other proposals
    }
    
    /**
     * Adds the array $newContent to this proposal's current content
     * @param array$newContent
     */
    public function addContent($newContent)
    {
        if (is_array($newContent))
        {
            foreach ($newContent as $key => $value)
            {
                $this->content[$key] = $value ;
            }
        }
    }
    /**
     * @return string A description of the proposal in the form "Party * is proposing *"
     */
    public function getDescription()
    {
        $content = $this->content ;
        if ($this->type=='Consuls')
        {
            $FirstSenatorName = $this->game->getFilteredCards(array('id'=>$content['First Senator']))->first()->getFullName() ;
            $SecondSenatorName = $this->game->getFilteredCards(array('id'=>$content['Second Senator']))->first()->getFullName() ;
            return sprintf(_('%1$s is proposing %2$s and %3$s as Consuls.') , $this->proposedBy->getFullName() , $FirstSenatorName , $SecondSenatorName) ;
        }
        if ($this->type=='Censor')
        {
            return ('PROPOSAL DESCRIPTION - TO DO');
        }
        if ($this->type=='Prosecutions')
        {
            $accused = $this->game->getFilteredCards(array('SenatorID'=>$content['Accused']))->first()->getFullName() ;
            if (array_key_exists('Card', $content))
            {
                $card = $this->game->getFilteredCards(array('id'=>$content['Card']))->first() ;
                $reason = sprintf(_('profiting from %1$s') , $card->getName()) ;
            }
            else
            {
                $reason = _('holding an office') ;
            }
            $prosecutor = $this->game->getFilteredCards(array('SenatorID'=>$content['Prosecutor']))->first()->getFullName() ;
            return sprintf(_('%1$s faces a %2$s prosecution for %3$s. The prosecutor is %4$s.') , $accused , $content['Type'] , $reason , $prosecutor);
        }
        if ($this->type=='recruit')
        {
            $fleetMessage = ((array_key_exists ('fleetsToRecruit' , $content) && $content['fleetsToRecruit'] > 0 )  ? sprintf(ngettext('%1$d fleet' , '%1$d fleets' , $content['fleetsToRecruit']) , $content['fleetsToRecruit'] ) : '') ;
            $regularMessage = ((array_key_exists ('regularsToRecruit' , $content) && $content['regularsToRecruit'] > 0 )? sprintf(ngettext('%1$d regular legion' , '%1$d regular legions' , $content['regularsToRecruit']) , $content['regularsToRecruit'] ) : '') ;
            return sprintf(_('%1$s is proposing to recruit %2$s%3$s%4$s.') , $this->proposedBy->getFullName() , $fleetMessage , ( (($fleetMessage!='') && ($regularMessage!='')) ? _('and') : '' ) , $regularMessage ) ;
        }
        if ($this->type=='commander')
        {
            $message='' ;
            foreach ($content as $item)
            {
                $commander = $this->game->getFilteredCards(array('SenatorID'=>$item['commander']))->first() ;
                $conflict = $this->game->getFilteredCards(array('id'=>$item['conflict']))->first() ;
                $veteransMessage = 'no veterans' ;
                $message.=sprintf(_('%1$s (%2$s - MIL: %3$d) to fight %4$s with %5$d fleets, %6$d regular legions and %7$s, and ') , $commander->getFullName() , $commander->getOffice() , $commander->getMIL() , $conflict->getName() , $item['fleets'] , $item['regulars'] , $veteransMessage);
            }
            $message = substr($message , 0 , -6) ;
            return sprintf(_('%1$s is proposing to send %2$s.') , $this->proposedBy->getFullName() , $message ) ;
        }
        return ('PROPOSAL DESCRIPTION - TO DO');
    }
 
    /**
     * 
     * @return string Description of the current step ('vote' , 'decision' , 'appoint' ...)
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
     * Sets this->decision[$key] to be equal to $value
     * @param mixed $key
     * @param mixed  $value
     * @throws \Exception
     */
    public function setDecision($key , $value)
    {
        try {
            $this->decision[$key] = $value ;
        } catch (Exception $ex) {
            throw new \Exception(_('ERROR - user not found')) ;
        }
    }
            
    /**
     * Returns TRUE if the proposal would pass with current votes
     * @return bool
     */
    public function isCurrentOutcomePass()
    {
        // There was a Popular appeal special result : ignore the votes (only applicable for prosecutions)
        $content = $this->getContent() ;
        if (array_key_exists('PopularAppealSpecial' , $content))
        {
            return ($content=='killed') ;
        }
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
    
    /**
     * Returns a full calculation of the strength of each commander sub proposal.
     * Each sub proposal is an array : 'fleets' , 'regulars' , 'veterans' , 'MIL' , 'MoH MIL' , 'totalSea' , 'totalLand' , 'conflictSea' , 'conflictLand' 
     * @return array
     */
    public function getCommanderProposalDetails()
    {
        // calculate 
        if ($this->getType()!='commander')
        {
            return [] ;
        }
        $result = array() ;
        foreach ($this->content as $key=>$item)
        {
            $commander = $this->game->getFilteredCards(array('SenatorID'=>$item['commander']))->first() ;
            if ($commander->getOffice() == 'Dictator') 
            {
                $MoH = $this->game->getAllSenator('isMaster of Horse')->first() ;
                $MoHMIL = $MoH->getMIL() ;
            }
            else
            {
                $MoHMIL = 0 ;
            }
            $conflict = $this->game->getFilteredCards(array('id'=>$item['conflict']))->first() ;
            $modifiedConflictStrength = $this->game->getModifiedConflictStrength($conflict) ;
            $result[$key] = array (
                'fleets' => $item['fleets'] ,
                'regulars' => $item['regulars'] ,
                'veterans' => 0 , // TO DO
                'MIL' => $commander->getMIL() ,
                'MoH MIL' => $MoHMIL ,
                'totalSea' => $item['fleets'] + $commander->getMIL() + $MoHMIL ,
                'totalLand' => $item['regulars'] + 0 + $commander->getMIL() + $MoHMIL , // TO DO : Add veterans
                'conflictSea' => $modifiedConflictStrength['fleet'] ,
                'conflictLand' => $modifiedConflictStrength['land']
            ) ;
        }
        return $result ;
    }
}
