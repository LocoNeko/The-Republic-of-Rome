<?php
namespace Entities ;

/**
 * @Entity @Table(name="proposals")
 * - vote is in the form of an array :
 * > keys are integer from 0 and give the order of vote
 * > values are arrays of 'user_id' , 'votes' , 'description'
 **/
class Proposal extends TraceableEntity
{
    const ERROR_NO_VOTER = 1;
    
    /** @ManyToOne(targetEntity="Game", inversedBy="proposals" , cascade={"persist"}) */
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
     * @todo Implement / remove constraints & conditions (they should be unused and covered by the checkConstraints function)
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
            $this->setProposedBy ($game->getParty($user_id)) ;
        }
        catch (\Exception $ex)
        {
            throw new \Exception(_('Error with Proposal - ').$ex->getMessage().' , Line : '.$ex->getLine()) ;
        }
        
        /**
         * Second, check & set the voting order
         * Some proposals have no voting order, as they are special 
         */
        $this->vote = [] ;
        if (($json_data!==NULL) && (array_key_exists('senateListVotingOrder', $json_data)))
        {
            try 
            {
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
                throw new \Exception(_('Error with Proposal - Problem with voting order')) ;
            }
        }

        /**
         * -------------------
         * Type-specific setup : 
         * - type itself
         * - flow
         * - decision
         * - step = 0
         * -------------------
         */
        
        /**
         * Consuls
         */
        if ($type=='Consuls')
        {
            $this->setType('Consuls') ;
            $this->setFlow(
                array  (
                    0 => 'vote' ,
                    1 => 'decision' , // Consuls must decide who becomes what
                    2 => 'done'
                )
            ) ;
            $this->setStep(0) ;
            $this->setDecision('First Senator' , NULL) ;
            $this->setDecision('Second Senator' , NULL) ;
        }
        /**
         * @todo : Pontifex Maximus 
         */

        /**
         * @todo : Dictator
         */

        /**
         * Censor
         */
        if ($type=='Censor')
        {
            $this->setType('Censor') ;
            $this->setFlow(
                array  (
                    0 => 'vote' ,
                    1 => 'done'
                )
            ) ;
            $this->setStep(0) ;
        }
        /**
         * Prosecutions
         */
        if ($type=='Prosecutions')
        {
            $this->setType('Prosecutions') ;
            $this->setFlow(
                array  (
                    0 => 'decision' , // Prosecutor must agree
                    1 => 'vote' ,
                    2 => 'done'
                )
            ) ;
            $this->setStep(0) ;
        }
        /**
         * -------------------
         * Proposals below are all "Other Business"
         * -------------------
         */

        /**
         * @todo : Rhodes - Not exactly a proposal, but handled as such
         */

        /**
         * concessions
         */
        if ($type=='concession')
        {
            $this->setType('concession') ;
            $this->setFlow(
                array  (
                    0 => 'vote' ,
                    1 => 'done'
                )
            ) ;
            $this->setStep(0) ;
        }
        
        /**
         * @todo : Land bill
         */

        /**
         * recruit
         */
        if ($type=='recruit')
        {
            $this->setType('recruit') ;
            $this->setFlow(
                array  (
                    0 => 'vote' ,
                    1 => 'done'
                )
            ) ;
            $this->setStep(0) ;
        }

        /**
         * @todo : Garrisons
         */

        /**
         * commander
         */
        if ($type=='commander')
        {
            $this->setType('commander') ;
            $this->setFlow(
                array  (
                    0 => 'decision' , // Commander must agree to go if forces are inadequate
                    1 => 'vote' ,
                    2 => 'done'
                )
            ) ;
            $this->setStep(0) ;
        }

        /**
         * @todo : Recall
         */

        /**
         * @todo : Reinforce
         */

        /**
         * @todo : Recall Pontifex
         */

        /**
         * @todo : Priests
         */

        /**
         * @todo : Consul for life
         */

        /**
         * @todo : Minor
         */

        /**
         * @todo : Assassin's prosecution
         */

        /**
         * @todo : Automatic Recall
         */

        /**
         * Unanimous defeat
         */
        if ($type=='UnanimousDefeat')
        {
            $this->setType('UnanimousDefeat') ;
            $this->setFlow(
                array  (
                    0 => 'decision' , // HRAO must decided to step down or not
                    1 => 'done'
                )
            ) ;
            $this->setStep(0) ;
            $this->setDecision('stepDown' , NULL) ;
        }
    }
   
    public function getType()       { return $this->type ; }
    public function getFlow()       { return $this->flow; }
    public function getStep()       { return $this->step; }
    public function getContent()    { return $this->content; }
    public function getProposedBy() { return $this->proposedBy; }
    public function getOutcome()    { return $this->outcome ; }
    public function getVote()       { return $this->vote; }
    public function getDecision()      { return $this->decision; }
    
    public function setType($type)
    {
        if ($type!= $this->type) 
        {
            $this->type = $type;
        }
    }

    public function setFlow($flow) 
    {
        if ($flow!= $this->flow) 
        {
            $this->flow = $flow;
        }
    }

    public function setStep($step) 
    {
        if ($step!== $this->step) 
        {
            $this->step = $step;
        }
    }

    public function setContent($content) 
    {
        if ($content!= $this->content) 
        {
            $this->content = $content;
        }
    }

    public function setProposedBy($proposedBy) 
    {
        if ($proposedBy!= $this->proposedBy) 
        {
            $this->proposedBy = $proposedBy;
        }
    }

    public function setConstraints($constraints) 
    {
        if ($constraints!= $this->constraints) 
        {
            $this->constraints = $constraints;
        }
    }

    public function setConditions($conditions) 
    {
        if ($conditions!= $this->conditions) 
        {
            $this->conditions = $conditions;
        }
    }

    public function setOutcome($outcome) 
    { 
        if ($outcome!= $this->outcome) 
        {
            $this->outcome = $outcome; 
        }
    }

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
            /** @todo: the content array has card ids, it must be changed to SenatorID */
            $this->setContent(
                array ( 
                    'First Senator' => $First_Senator->getCardId() , 
                    'Second Senator' => $Second_Senator->getCardId() 
                )
            );
            /** @todo : Check already proposed pairs */
        }
        
        /**
         * @todo : Check constraints for Pontifex Maximus 
         */
        
        /**
         * @todo : Check constraints for Dictator
         */
        
        /**
         * @todo : Check constraints for Censor 
         */
        
        /**
         * Prosecutions :
         * specific json :
         * - Prosecutor : SenatorID of prosecutor
         * - Prosecution : json {type=>'Minor|Major' , senatorID , cardId if applicable}
         */
        if ($type=='Prosecutions')
        {
            $content = [] ;
            try {
                $prosecutor = $game->getFilteredCards(array('senatorID'=>$json_data['Prosecutor']))->first() ;
                $content['Prosecutor'] = $prosecutor->getSenatorID() ;
            } catch (Exception $ex) {
                throw new \Exception(_('Could not retrieve the Prosecutor. He might be hiding.')) ;
            }
            try {
                $prosecution = json_decode($json_data['Prosecution'],TRUE) ;
                $accused = $game->getFilteredCards(array('senatorID'=>$prosecution['senatorID']))->first() ;
                $content['Accused'] = $accused->getSenatorID() ;
                $content['Type'] = $prosecution['Type'] ;
                if (array_key_exists('cardId' , $prosecution))
                {
                    $content['Card'] = $prosecution['cardId'] ;
                }
            } catch (Exception $ex) {
                throw new \Exception(_('Error in the prosecution.')) ;
            }
            $this->setContent($content) ;
        }
        /**
         * @todo : Check constraints for Governors
         */

        /**
         * -------------------
         * Proposals below are all "Other Business"
         * -------------------
         */

        /**
         * @todo : Check constraints for Rhodes - Not exactly a proposal, but handled as such
         */

        /**
         * concessions
         */
        if ($type=='concession')
        {
            try {
                $data = $json_data['dynamicSections']['concession'] ;
            } catch (Exception $ex) {
                throw new \Exception(_('ERROR - Problem with Concessionproposal data.')) ;
            }
            $content = [] ;
            foreach ($data as $key=>$item)
            {
                $concessionID = $item['otherBusinessConcessionCard'] ;
                /* @var $concession \Entities\Concession */
                $concession = $game->getFilteredCards(array('cardId'=>$concessionID))->first() ;
                // Check if this card is indeed a concession
                if ($concession->getPreciseType()!='Concession')
                {
                    throw new \Exception(sprintf(_('%1$s is not a concession.') , $concession->getName())) ;
                }
                // Check if concessions is indeed in the Forum
                if ($concession->getLocation()['name']!='forum')
                {
                    throw new \Exception(sprintf(_('Concession %1$s is not in the Forum.') , $concession->getName())) ;
                }
                // If concession is Land Commissioner, check whether a land bill is active
                if (($concession->getSpecial()=='land bill') && ($game->getLandBillsTotalCost()['total']==0) )
                {
                    throw new \Exception(sprintf(_('Cannot assign Land commissioner : no land bill in play.') , $concession->getName())) ;
                }
                // Check if the concession appears only once
                $count = 0 ;
                foreach ($data as $item2)
                {
                    if ($item2['otherBusinessConcessionCard'] == $concessionID )
                    {
                        $count++ ;
                    }
                }
                if ($count>1)
                {
                    throw new \Exception(sprintf(_('Cannot assign %1$s more than once.') , $concession->getName())) ;
                }
                // Set the content of the proposal
                $content[$key] = array(
                    'concession' => $concessionID ,
                    'senator' => $item['otherBusinessConcessionSenator']
                ) ;
            }
            $this->setContent($content) ;
        }

        /**
         * @todo : Check constraints for Land bill
         */

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
                throw new \Exception($ex) ;
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
                throw new \Exception($ex) ;
            }
            $this->setContent(
                array 
                (
                    'fleetsToRecruit' => $fleetsToRecruit ,
                    'regularsToRecruit' => $regularsToRecruit
                )
            );
        }

        /**
         * @todo : Check constraints for Garrisons
         */

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
                    /** @todo constraint for commander proposal : veterans content */
                ) ;
                // decision are in an array of the form senatorID => decision (initialised to NULL)
                $this->setDecision($item['otherBusinessCommanderSenator'] , NULL) ;
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
            /**
             * @todo constraint for commander proposal : Check whether Correct veterans are sent
             * @todo constraint for commander proposal : Check whether there are enough support fleets when legions are sent
             * @todo constraint for commander proposal : Check whether conflict is attackable : right deck, attacked in the right sequence (e.g. First Punic before or at the same time as Second Punic)
             * @todo constraint for commander proposal : Check whether commanders are sent in the right order (Field Consul before or at the same time as Rome Consul). In other words : can't send Rome Consul, except if Field Consul has already been sent OR will be sent in the same proposal
             */
            $this->setContent($content) ;
            foreach ($content as $item)
            {
                /** @var $conflict \Entities\Conflict */
                $conflict = $this->game->getFilteredCards(array('cardId'=>$item['conflict'] ))->first() ;
                $modifiedConflictStrength = $this->game->getModifiedConflictStrength($conflict) ;
                /**
                 * @todo Check if the Conflict has a non-defeated fleet, in which case it's acceptable to send 0 legions and only check adequate forces for fleets
                 */
                if ($modifiedConflictStrength['fleet']>0 && $conflict->getFleetDefeated()===FALSE)
                {
                    
                }
                /**
                 * @todo Check the conflict land strength against legions
                 */
            }
            
            /** @todo Set decision to agreed automatically if minimum forces are met. Remember that Fleet-only battles are possible */
            
        }

        /**
         * @todo : Check constraints for Recall
         */

        /**
         * @todo : Check constraints for Reinforce
         */

        /**
         * @todo : Check constraints for Recall Pontifex
         */

        /**
         * @todo : Check constraints for Priests
         */

        /**
         * @todo : Check constraints for Consul for life
         */

        /**
         * @todo : Check constraints for Minor
         */

        /**
         * @todo : Check constraints for Assassin's prosecution
         */

        /**
         * @todo : Check constraints for Automatic Recall
         */

        /**
         * @todo : Check constraints for Unanimous defeat
         */
    }
    
    /**
     * Adds the array $newContent to this proposal's current content
     * @param array$newContent
     */
    public function addContent($newContent)
    {
        if (is_array($newContent))
        {
            $this->setContent(array_merge($this->getContent() , $newContent)) ;
        }
    }
    /**
     * @return string A description of the proposal in the form "Party * is proposing *"
     */
    public function getDescription()
    {
        $content = $this->getContent() ;
        
        /**
         * Consuls
         */

        if ($this->getType()=='Consuls')
        {
            $FirstSenatorName = $this->game->getFilteredCards(array('cardId'=>$content['First Senator']))->first()->getFullName() ;
            $SecondSenatorName = $this->game->getFilteredCards(array('cardId'=>$content['Second Senator']))->first()->getFullName() ;
            return sprintf(_('%1$s is proposing %2$s and %3$s as Consuls.') , $this->proposedBy->getFullName() , $FirstSenatorName , $SecondSenatorName) ;
        }
        /**
         * @todo : Pontifex Maximus 
         */
        
        /**
         * @todo : Dictator
         */
        
        /**
         * @todo : Censor 
         */

        if ($this->getType()=='Censor')
        {
            return ('PROPOSAL DESCRIPTION - TO DO');
        }

        /**
         * Prosecutions
         */

        if ($this->getType()=='Prosecutions')
        {
            $accused = $this->game->getFilteredCards(array('SenatorID'=>$content['Accused']))->first()->getFullName() ;
            if (array_key_exists('Card', $content))
            {
                $card = $this->game->getFilteredCards(array('cardId'=>$content['Card']))->first() ;
                $reason = sprintf(_('profiting from %1$s') , $card->getName()) ;
            }
            else
            {
                $reason = _('holding an office') ;
            }
            $prosecutor = $this->game->getFilteredCards(array('SenatorID'=>$content['Prosecutor']))->first()->getFullName() ;
            return sprintf(_('%1$s faces a %2$s prosecution for %3$s. The prosecutor is %4$s.') , $accused , $content['Type'] , $reason , $prosecutor);
        }
        
        /**
         * @todo : Governors
         */

        /**
         * -------------------
         * Proposals below are all "Other Business"
         * -------------------
         */

        /**
         * @todo : Rhodes - Not exactly a proposal, but handled as such
         */
        
        /**
         * @todo : Concessions
         */

        if ($this->getType()=='concession')
        {
            $message='' ;
            foreach ($content as $item)
            {
                /* @var $concession \Entities\Concession */
                $concession = $this->game->getFilteredCards(array('cardId'=>$item['concession']))->first() ;
                /* @var $senator \Entities\Senator */
                $senator = $this->game->getFilteredCards(array('SenatorID'=>$item['senator']))->first() ;
                $message.=sprintf(_('%1$s to %2$s , ') , $concession->getName() , $senator->getFullName());
            }
            return sprintf(_('%1$s is proposing to assign %2$s.') , $this->proposedBy->getFullName() , substr($message , 0 , -3) ) ;
        }

        
        /**
         * @todo : Land bill
         */
        
        /**
         * Recruit
         */
        if ($this->getType()=='recruit')
        {
            $fleetMessage = ((array_key_exists ('fleetsToRecruit' , $content) && $content['fleetsToRecruit'] > 0 )  ? sprintf(ngettext('%1$d fleet' , '%1$d fleets' , $content['fleetsToRecruit']) , $content['fleetsToRecruit'] ) : '') ;
            $regularMessage = ((array_key_exists ('regularsToRecruit' , $content) && $content['regularsToRecruit'] > 0 )? sprintf(ngettext('%1$d regular legion' , '%1$d regular legions' , $content['regularsToRecruit']) , $content['regularsToRecruit'] ) : '') ;
            return sprintf(_('%1$s is proposing to recruit %2$s%3$s%4$s.') , $this->proposedBy->getFullName() , $fleetMessage , ( (($fleetMessage!='') && ($regularMessage!='')) ? _('and') : '' ) , $regularMessage ) ;
        }
        
        /**
         * @todo : Garrisons
         */
        
        if ($this->getType()=='commander')
        {
            $message='' ;
            foreach ($content as $item)
            {
                $commander = $this->game->getFilteredCards(array('SenatorID'=>$item['commander']))->first() ;
                $conflict = $this->game->getFilteredCards(array('cardId'=>$item['conflict']))->first() ;
                $veteransMessage = 'no veterans' ;
                $message.=sprintf(_('%1$s (%2$s - MIL: %3$d) to fight %4$s with %5$d fleets, %6$d regular legions and %7$s, and ') , $commander->getFullName() , $commander->getOffice() , $commander->getMIL() , $conflict->getName() , $item['fleets'] , $item['regulars'] , $veteransMessage);
            }
            $message = substr($message , 0 , -6) ;
            return sprintf(_('%1$s is proposing to send %2$s.') , $this->proposedBy->getFullName() , $message ) ;
        }
        
        /**
         * @todo : Recall
         */

        /**
         * @todo : Reinforce
         */

        /**
         * @todo : Recall Pontifex
         */

        /**
         * @todo : Priests
         */

        /**
         * @todo : Consul for life
         */

        /**
         * @todo : Minor
         */

        /**
         * @todo : Assassin's prosecution
         */

        /**
         * @todo : Automatic Recall
         */

        /**
         * @todo : Unanimous defeat
         */
        if ($this->getType()=='UnanimousDefeat')
        {
            return _('The Presiding Magistrate has been unanimously defeated and must decide whether to step down or lose 1 INF.');
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
            return $this->getFlow()[$this->getStep()] ;
        } catch (Exception $ex) {
            throw new \Exception(_('Invalid step for this proposal')) ;
        }
    }

    /**
     * Returns TRUE if the current step is the last one
     * @return bool
     */
    public function isFinished()
    {
        return ( ( $this->getStep()) >= (count($this->getFlow())-1) ) ;
    }
    
    /**
     * Returns the user id of the current voter for this proposal, or throws an exception
     * @return int $user_id
     * @throws \Exception
     */
    public function getCurrentVoter()
    {
        if ($this->getOutcome()!='underway')
        {
            throw new \Exception(_('No vote underway')) ;
        }
        foreach ($this->getVote() as $vote)
        {
            if ($vote['votes']===NULL)
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
                    /** @todo in Vote tally, add INF for Prosecutions & Consul for life */
                    // Dropdown for spending talents
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
     * Sets the votes and their description for this proposal and this user_id, as well as whether the vote was unanimous FOR or AGAINST in the party
     * @param int $user_id
     * @param int $votes
     * @param string $description
     * @param boolean $unanimous
     * @return TRUE
     * @throws \Exception
     */
    public function setVote($user_id , $votes , $description , $unanimous) 
    {
        $currentVote = $this->getVote() ;
        foreach ($this->getVote() as $i=>$vote)
        {
            if ($vote['user_id']==$user_id)
            {
                $this->vote[$i]['votes'] = $votes ;
                $this->vote[$i]['description'] = $description ;
                $this->vote[$i]['unanimous'] = $unanimous ;
                return TRUE ;
            }
        }
        throw new \Exception(_('ERROR - user not found')) ;
    }

    /**
     * Sets this->decision[$key] to be equal to $value
     * @param mixed $key
     * @param mixed $value
     * @throws \Exception
     */
    public function setDecision($key , $value)
    {
        try {
            $this->decision[$key] = $value ;
        } catch (Exception $ex) {
            throw new \Exception(_('ERROR - Proposal-.setDecision')) ;
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
    
    /*
     * Whether or not this was a unanimous defeat of the HRAO's proposal
     * @return bool
     */
    public function isHRAOunanimousDefeat()
    {
        $partyOfHRAO_userID = (int)$this->game->getHRAO(TRUE)->getLocation()['value']->getUser_id() ;
        if ((int)$this->getProposedBy()->getUser_id() != $partyOfHRAO_userID)
        {
            // This was not proposed by the HRAO's party
            return FALSE ;
        }
        if ($this->getOutcome()=='fail')
        {
            foreach ($this->getVote() as $vote)
            {
                // Only check the parties that are not the HRAO's
                if ((int)$vote['user_id']!=$partyOfHRAO_userID)
                {
                    if (!$vote['unanimous'] || $vote['votes']>=0)
                    {
                        /*
                         *  Unanimous is FALSE if :
                         * - A party's vote was not unanimous
                         * - A party's vote was not against
                         */
                        return FALSE ;
                    }
                }
            }
        }
        else
        {
            return FALSE ;
        }
        return TRUE ;
    }
    
    /**
     * Adds 1 to this proposal flow
     */
    public function incrementStep()
    {
        $this->setStep($this->getStep()+1) ;
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
        foreach ($this->getContent() as $key=>$item)
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
            $conflict = $this->game->getFilteredCards(array('cardId'=>$item['conflict']))->first() ;
            $modifiedConflictStrength = $this->game->getModifiedConflictStrength($conflict) ;
            $result[$key] = array (
                'fleets' => $item['fleets'] ,
                'regulars' => $item['regulars'] ,
                'veterans' => 0 , /** @todo veterans for getCommanderProposalDetails */
                'MIL' => $commander->getMIL() ,
                'MoH MIL' => $MoHMIL ,
                'totalSea' => $item['fleets'] + $commander->getMIL() + $MoHMIL ,
                'totalLand' => $item['regulars'] + 0 + $commander->getMIL() + $MoHMIL , /** @todo add veterans for getCommanderProposalDetails */
                'conflictSea' => $modifiedConflictStrength['fleet'] ,
                'conflictLand' => $modifiedConflictStrength['land']
            ) ;
        }
        return $result ;
    }
}
