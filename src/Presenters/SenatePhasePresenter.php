<?php
namespace Presenters ;
use Doctrine\Common\Collections\ArrayCollection;
// TO DO : Beginning of Senate phase - Initialise free tribunes of all senators
class SenatePhasePresenter
{
    // Common to all phase presenters
    public $user_id;
    public $game;
    public $yourParty;
    public $otherParties = [];
    public $header = [];
    public $interface = [];
    public $sliders = [];
    
    /**
     * @param \Entities\Game $game
     * @param int $user_id
     */
    public function __construct($game, $user_id)
    {
        /**
         * Common to all Phase presenters (should I abstract / extend ?)
         */
        $this->user_id = $user_id;
        $this->game = new \Presenters\GamePresenter($game, $user_id);
        foreach ($game->getParties() as $party)
        {
            if ($party->getUser_id() == $user_id) 
            {
                $this->yourParty = new \Presenters\PartyPresenter($party, $user_id);
            }
            else
            {
                $this->otherParties[$party->getUser_id()] = new \Presenters\PartyPresenter($party, $user_id);
            }
        }
        
        /**
         * Phase Header
         */
        $this->header['list'] = array();
        $this->header['actions'] = array();
        $this->header['description'] = _('Senate') ;
        if ($game->getPhase() != 'Senate') 
        {
            $this->header['description'] .= _('ERROR - Wrong phase');
        }
        else
        {
            $currentProposal = $game->getProposals()->last() ;
            /**
             * There is a proposal underway
             */
            if ($currentProposal!==FALSE && $currentProposal->getOutcome()!=='underway')
            {
                $this->header['description'] .= _(' - Proposal underway');
            }
            
            /**
             * No proposal underway
             * - Check if the user can make a proposal
             * - This will bring up the 'senateMakeProposal' interface
             * - In case he can't, this will bring up a waiting screen
             */
            else
            {
                $this->header['description'] .= _(' - No proposal underway') ;
                $listProposalHow = $this->getProposalHow($game) ; 
                
                /**
                * This user has at least one way of making a proposal
                **/
                if (count($listProposalHow)>0)
                {
                    // Bring up the senateMakeProposal interface
                    $this->interface['name'] = 'senateMakeProposal';
                    
                    /**
                    * How the Proposal is made :
                    * Items consists of arrays with 'type' , 'code', and 'description'
                    * Types : office, statesman, tribune
                    * Code : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , {senatorID} , {cardID}
                    **/
                    $this->interface['listProposalHow'] =  array (
                        'type' => 'select' ,
                        'items' => $listProposalHow
                    ) ;
                    $this->interface['senateMakeProposal'] = array (
                        'type' => 'button' ,
                        'disabled' => TRUE ,
                        'verb' => 'senateMakeProposal' ,
                        'text' => _('MAKE PROPOSAL')
                    ) ;
                    /**
                    * The proposal's content, specific to the subPhase. This should include :
                    * - A description in the header
                    * - An interface proposalType
                    **/
                    
                    $this->setContent($game) ;
                }
                
                /**
                * This user cannot make a proposal at this point in time
                **/
                else
                {
                    $this->header['list'] = array (
                        _('You have no way to make a proposal at the moment') ,
                        _('Waiting for player who do')
                    ) ;
                }
            }
        }
    }
    
    /**
    * Function to add classes and attributes to cards
    * Not used at the moment, as proposals will be done by drop-downs
    **/
    
    public function addClassToCards($toAdd , $cardsToApplyItTo)
    {
        /**
         * Create a collection of all cards from game decks & parties
         * Hands and not included
         */
        $allCards = new ArrayCollection() ;
        foreach ($this->game->deck as $deck)
        {
            foreach ($deck->cards as $card)
            {
                $allCards->add($card) ;
            }
        }
        foreach ($this->otherParties as $party)
        {
            foreach ($party->senators as $senator)
            {
                $allCards->add($senator) ;
            }
        }
        foreach ($this->yourParty->senators as $senator)
        {
            $allCards->add($senator) ;
        }
        /**
         * Goes through the collection and checks if the target card's ID matches one of the cardsToApplyItTo
         */
        foreach ($allCards as $card)
        {
            foreach ($cardsToApplyItTo as $targetCard)
            {
                if ($targetCard->getId() == $card->id)
                {
                    foreach ($toAdd as $value)
                    {
                        if ($value['type'] == 'class')
                        {
                            $card->addClass($value['name']) ;
                        }
                        elseif ($value['type'] == 'attribute')
                        {
                            $card->addAttribute($value['name'] , $value['value']) ;
                        }
                    }
                }
            }
        }
    }
    /**
     * Sets the senateMakeProposal interface based on the subPhase :
     * Consuls , DictatorAppointment , Prosecutions ...
     * @param \Entities\Game $game
     * @return array
     */
    public function setContent($game)
    {
        /**
        * Consuls
        */
        if ($game->getSubPhase()=='Consuls')
        {
            $this->header['list'] = array (
                _('Consuls Election') ,
                _('You must select a pair of Senators') ,
                _('Only Senators aligned in Rome can be proposed') ,
                _('Among officials, only the Censor and Master of Horse can be proposed') ,
                _('Already rejected pairs cannot be proposed again')
            ) ;
            $this->interface['proposalType']= 'Consuls' ;
            $possibleValues = $this->getListOfCandidates($game) ;
            $this->interface['senators']= array
            (
                array (
                    'description' => _('First Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'First Senator',
                        'items' => $possibleValues 
                    )
                ) ,
                array (
                    'description' => _('Second Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'Second Senator',
                        'items' => $possibleValues 
                    )
                )
            ) ;
        }
        /**
        * Consuls
        */
        elseif ($game->getSubPhase()=='Censor')
        {
            $this->header['list'] = array (
                _('Censor Election') ,
                _('You must select a Senator') ,
                _('Only Senators aligned in Rome who are prior consuls can be proposed') ,
                _('The Censor can suceed himself') ,
                _('Already rejected senators cannot be proposed again')
            ) ;
            $this->interface['proposalType']= 'Censor' ;
            $this->interface['senators']= array
            (
		    'description' => _('Senator') ,
		    'list' => array (
		        'type' => 'select' ,
		        'class' => 'Senator',
		        'items' => $this->getListOfCandidates($game) 
		    )
            ) ;
        }
        /**
        * Prosecutions
        */
        elseif ($game->getSubPhase()=='Prosecutions')
        {
            $this->header['list'] = array (
                _('Prosecution') ,
                _('You must select a Senator') ,
                _('Only Senators aligned in Rome who have a major or minor corruption marker can be prosecuted') ,
            ) ;
            $this->interface['proposalType']= 'Prosecutions' ;
            $this->interface['prosecutions']= array
            (
                    'description' => _('Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'Senator',
                        'items' => $this->getListOfCandidates($game)
                    )
            ) ;
        }
        /**
        * Governors
        */
        elseif ($game->getSubPhase()=='Governors')
        {
            $this->header['list'] = array (
                _('Governors') ,
                _('You must pair Senators with provinces')
            ) ;
            $this->interface['proposalType']= 'Governors' ;
            $this->interface['senators']= array
            (
                    'description' => _('Senator') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'Senator',
                        'items' => $this->getListOfCandidates($game)
                    )
            ) ;
            $this->interface['provinces']= array
            (
                    'description' => _('Province') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'Province',
                        'items' => $this->getListOfAvailableCards($game)
                    )
            ) ;
        }
        /**
        * Other business
        */
        elseif ($game->getSubPhase()=='OtherBusiness')
        {
            $this->header['list'] = array (
                _('Other business') ,
                _('You must first choose a type of proposal')
            ) ;
	    // This interface just has a drop down list otherBusinessList (defined at the end of this section)
            $this->interface['proposalType']= 'OtherBusiness' ;
            $availableOtherBusiness = array () ;
            
            /**
             * Create a collection of all cards from game decks & parties
             * Hands not included
             */
            $allCards = new ArrayCollection() ;
            $allSenators  = new ArrayCollection() ;
            foreach ($this->game->deck as $deck)
            {
                foreach ($deck->cards as $card)
                {
                    if ($card->preciseType == 'Senator')
                    {
                        $allSenators->add($card) ;
                    }
                    else
                    {
                        // only add concessions if they are in the forum
                        if ($card->preciseType !== 'Concession' || $deck->name==='forum')
                        {
                            $allCards->add($card) ;
                        }
                    }
                }
            }
            foreach ($this->otherParties as $party)
            {
                foreach ($party->senators as $senator)
                {
                    $allSenators->add($senator) ;
                }
            }
            foreach ($this->yourParty->senators as $senator)
            {
                $allSenators->add($senator) ;
            }
            
            /**
            * adds element to the 'otherBusiness' attribute array of each cardPresenter based on criteria
            * - For Senators cardPresenter
            **/
            foreach ($allSenators as $senator)
            {
                // Go through controlled cards and add them to $allCards except :
                // - Senators, which represents the family of a Statesman and is not needed)
                // - Concessions, which are already assigned if we find them in a Senator->controlledCards
                foreach($senator->controlledCards as $subCard)
                {
                    if ($card->preciseType !== 'Senator' && $card->preciseType !== 'Concession')
                    {
                        $allCards->add($card) ;
                    }
                }
                $senatorModel = $game->getFilteredCards(array('senatorID' => $senator->getAttribute('senatorID')))->first();
                
                // Concession holder , Land bill sponsor & co-sponsor
                if ($senatorModel->checkCriteria('alignedInRome'))
                {
                    $senator->addAttribute('otherBusiness' , 'concession' , TRUE);
                    $senator->addAttribute('otherBusiness' , 'landBill' , TRUE);
		    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'concession' , _('Assign concessions') ) ;
		    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'landBill' , _('Sponsor Land bills')) ;
                }
                
                // Possible commander
                if ($senatorModel->checkCriteria('possibleCommanders'))
                {
                    $senator->addAttribute('otherBusiness' , 'commander' , TRUE);
		    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'commander' , _('Send Forces') ) ;
                }
                
                // Proconsul : can be recalled, can be reinforced
                if ($senatorModel->checkCriteria('isProconsul'))
                {
                    $senator->addAttribute('otherBusiness' , 'commanderRecall' , TRUE);
                    $senator->addAttribute('otherBusiness' , 'reinforcement' , TRUE);
		    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'commanderRecall' , _('Recall ProConsul') ) ;
		    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'reinforcement' , _('Reinforce ProConsul') ) ;
                }
                // TO DO : 'senatorsForPontifex' , 'senatorsForPontifexRecall' , 'senatorsForPriest' , 'senatorsForPriestRecall' , 'senatorsForConsulForLife'
            }

            /**
            * adds element to the 'otherBusiness' attribute array of each cardPresenter based on criteria
            * - For normal cards cardPresenter
            **/
            foreach ($allCards as $card)
            {
                $cardModel = $game->getFilteredCards(array('cardID' => $card->id))->first();
                if ($card->preciseType==='Concession')
                {
                    $card->addAttribute('otherBusiness' , 'concession' , TRUE);
                }
                elseif ($card->preciseType==='Conflict')
                {
                    $card->addAttribute('otherBusiness' , 'commander' , TRUE);
                }
            }
	    
	    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'recruit' , _('Recruit Forces') ) ;
	    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'disband' , _('Disband Forces') ) ;
	    $availableOtherBusiness = $this->addAvailableOtherBusiness($availableOtherBusiness , 'garrison' , _('Send garrions in Provinces') ) ;

  	    // TO DO : Prepare data for legions : Commander, reinforcement, recruit, disband , garrison
	    // Fleets : how many in Rome, in the pool, with a commander ->addAttribute('fleets' , X)
	    // Regular legions : how many in Rome, in the pool, with a commander ->addAttribute('regulars' , X)
	    // Veteran legion : for each - its allegiance & if it's in Rome or with a commander ->addAttribute('veterans' , X , TRUE) where X is the allegiance

	    /**
            * Finally set the main drop-down of the interface to show a list of otherBusiness that are available. This is determined by having at least one card checked TRUE in the loop above
	    **/
	    $this->interface['otherBusinessList']= array
            (
                array (
                    'description' => _('Proposal type') ,
                    'list' => array (
                        'type' => 'select' ,
                        'class' => 'otherBusinessList',
                        'items' => $availableOtherBusiness
                    )
                )
            ) ;


        }
    }
    
    /**
     * Returns an array of how the current user can make a proposal : CENSOR , DICTATOR APPOINTMENT , PRESIDENT , FREE TRIBUNE FROM X , TRIBUNE CARD
     * @param \Entities\Game $game
     * @return array {'type' , 'code' , 'description'}
     */
    public function getProposalHow($game)
    {
    // President
    // Prosecutions : The Censor can propose
    // Dictator appointment : Only consuls can appoint
    // Tribune Cards
    // Free tribunes from Satesmen
        $result = array() ;
        /**
        * Censor during prosecutions
        * This is exculsive (will be the only result returned)
        **/
        if ($game->getSubPhase() == 'Prosecutions')
        {
            $searchResult = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'isCensor') ;
            if (count($searchResult)==1 && $searchResult->first()->getUser_id() == $this->user_id)
            {
                return array(array ('type' => 'office' , 'code' => 'CENSOR' , 'description' => _('Proscutions by the Censor')));
            }
            else
            {
                return array() ;
            }
        }
        /**
        * Consuls appointing a Dictator
        * This is exculsive (will be the only result returned)
        **/
        if ($game->getSubPhase() == 'DictatorAppointment')
        {
            $searchResult = $game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'canAppointDictator') ;
            if (count($searchResult)==1 && $searchResult->first()->getUser_id() == $this->user_id)
            {
                return array(array ('type' => 'office' , 'code' => 'DICTATOR APPOINTMENT' , 'description' => _('Dictator appointment by consuls')));
            }
            else
            {
                return array() ;
            }
        }
        /**
        * Other means of proposals : President, tribune card, free tribune
        * This is not exclusive (several results can be returned)
        **/
        if ($game->getHRAO(TRUE)->getLocation()['value']->getUser_id() == $this->user_id)
        {
            $result[] = array ('type' => 'office' , 'code' => 'PRESIDENT' , 'description' => _('President')) ;
        }
        $result = array_merge($result, $this->getFreeTribunes($game->getParty($this->user_id))) ;
        $result = array_merge($result, $this->getCardTribunes($game->getParty($this->user_id))) ;
        return $result ;
    }
    
    /**
    * Returns a list of free tribunes provided by Satesmen special abilities
    **/
    public function getFreeTribunes($party)
    {
        $result = array() ;
        foreach ($party->getSenators()->getCards() as $senator)
        {
            if ($senator->getFreeTribune() == 1)
            {
                $result[] = array ('type' => 'statesman' , 'code' => $senator->getSenatorID() , 'description' => _('Free tribune from ').$senator->getName()) ;
            }
        }
        return $result ;
    }
    
    /**
    * Returns a list of tribune cards for this party
    **/
    public function getCardTribunes($party)
    {
        $result = array() ;
        foreach ($party->getHand()->getCards() as $card)
        {
            if ($card->getName()=='TRIBUNE')
            {
                $result[] = array ('type' => 'tribune' , 'code' => $card->getId() , 'description' => 'Tribune card') ;
            }
        }
        return $result ;
    }
    
    public function getListOfCandidates($game)
    {
        $result = array() ;
        if ($game->getSubPhase() == 'Consuls')
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleConsul') as $senator)
            {
                $result[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'senatorID' => $senator->getSenatorID()
                );
            }
        }
        elseif ($game->getSubPhase() == 'Censor')
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'priorConsul') as $senator)
            {
                // TO DO : Check if the senator was not previously rejected in a proposal
                $result[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'senatorID' => $senator->getSenatorID()
                ) ;
            }
            // TO DO : Check if $result is empty, in which case, return all non-rejected aligned Senator in Rome
        }
        elseif ($game->getSubPhase() == 'Prosecutions')
        {
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'alignedInRome') as $senator)
            {
                $possibleProsecutions = $senator->getPossibleProsecutions() ;
                if (count($possibleProsecutions)>0)
                {
                    foreach ($possibleProsecutions as $possibleProsecution) 
                    {
                        // TO DO : check if there already was a minor prosecution (in which case, a major prosecution is impossible)
                        $result[] = array (
                            'prosecutionType' => $possibleProsecution['type'] ,
                            'description' => $possibleProsecution['description'] ,
                            'senatorID' => $senator->getSenatorID()
                        ) ;
                    }
                }
            }
        }
        elseif ($game->getSubPhase() == 'Governors')
        {
            // Add a "-" option linked to a NULL cardID, so no Senator is selected by default on a new drop down
            $result[] = array (
                'description' => _('-') ,
                'senatorID' => NULL
            ) ;
            // TO DO - add 'possibleGovernor' to Senator->checkCriteria() :
            // case 'possibleGovernor' :
            //     return ( $this->inRome() && $this->getOffice()===NULL) ;
            foreach ($game->getFilteredCards(array('isSenatorOrStatesman' => TRUE) , 'possibleGovernor') as $senator)
            {
                $result[] = array (
                    'description' => $this->game->displayContextualName($senator->getFullName()) ,
                    'senatorID' => $senator->getSenatorID()
                ) ;
            }
        }
        return $result ;
    }
    
    /**
    * List of available Province Cards for Governors proposals
    **/
    public function getListOfAvailableCards($game)
    {
        $result = array() ;
        if ($game->getSubPhase() == 'Governors')
        {
            // Add a "-" option linked to a NULL cardID, so no Province is selected by default on a new drop down
            $result[] = array (
                'description' => _('-') ,
                'recall' => FALSE ,
                'cardID' => NULL
            ) ;
            // TO DO  - Add to Province :
            // public function getIsProvinceInPlay()
            // {
            //    return ($this->getDeck()->getName() !== 'unplayedProvinces') ;
            // }
            foreach ($game->getFilteredCards(array('preciseType' => 'Province' , 'isProvinceInPlay' => TRUE)) as $province)
            {
                // If the card is not in the Forum, this is a recall
                $recall = $province->getDeck()->getName() !== 'Forum' ;
                $result[] = array (
                    'description' => $province->getName().($recall ? sprintf(_(' (recall of %1$s)') , $province->getDeck()->getControlled_by()->getName()) : '') ,
                    'recall' => $recall ,
                    'cardID' => $province->getId()
                ) ;
            }
        }
        return $result ;
    }

    /**
    * Taken out of setContent (otherBusiness section) for readibility's sake
    * This function adds an available other busines to the current list if it wasn't there already
    **/
    public function addAvailableOtherBusiness($current , $description , $value )
    {
	$result = $current ;
	foreach ($current as $business)
	{
	    if (isset($business['description']) && $business['description']== $description)
	    {
		break ;
	    }
	    $result[] = array (
		'description' => $description ,
		'value' => $value
	    ) ;
	}
	return $result ;
    }
}

