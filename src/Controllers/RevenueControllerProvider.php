<?php
namespace Controllers ;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class RevenueControllerProvider implements ControllerProviderInterface
{
    private $entityManager ;
    
    public function connect(Application $app)
    {
        // creates a new controller based on the default route
        $controllers = $app['controllers_factory'];
        $this->entityManager = $app['orm.em'] ;
        
        /*
         * Revenue
         */
        $controllers->get('/{game_id}', function($game_id) use ($app)
        {
            $app['session']->set('game_id', (int)$game_id);
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $user_id = (int)$app['user']->getId() ;

                //If seeing your own party, this means the update time can be set (as all the updates you need to see are now displayed)
                /** @todo: See how to handle this update better (service ?) */
                $game->getParty($user_id)->setLastUpdateToNow() ;
                
                $view = new \Presenters\RevenuePhasePresenter($game , $user_id) ;

                return $app['twig']->render('BoardElements/Main.twig', array(
                        'layout_template' => 'InGameLayout.twig' ,
                        'view' => $view
                ));

            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->redirect('/') ;
            }
        })
        ->bind('Revenue');
        
        /*
        * POST target
        * Verb : RevenueDone
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/RevenueDone', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            $user_id = (int)$app['user']->getId() ;
            $this->doRevenue($game , $user_id , $request->request->all()) ;
            if ($game->isEveryoneDone())
            {
                $app['saveGame']($game) ;
                $game->setSubPhase('Redistribution') ;
                $game->resetAllIsDone() ;
            }
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_RevenueDone');

        /*
        * POST target
        * Verb : revenueContributionsDone
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/revenueContributionsDone', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            $user_id = (int)$app['user']->getId() ;
            $game->getParty($user_id)->setIsDone(TRUE) ;
            if ($game->isEveryoneDone())
            {
                $game->setSubPhase('StateExpenses') ;
                $this->doStateExpenses($game) ;
                $app['saveGame']($game) ;
                $game->resetAllIsDone() ;
                $game->setPhase('Forum') ;
                // Remove events that expire at the beginning of the forum phase
                foreach ($game->getEvents() as $number => $event) {
                    if ($event['level']>0) {
                        if ( ($number != 174) && ($number != 175) && ($number != 176) ) {
                            $game->setEventLevel ('number' , $number , 0) ;
                            $game->log(_('Event %s is removed.') , 'log' , array($event['name'])) ;
                        }
                    }
                }
                // Barbarians kill captives
                /* @var \Entities\Party $party */
                foreach ($game->getParties() as $party) {
                    $captiveList = $party->getListOfCaptives() ;
                    if ($captiveList!==FALSE) {
                        foreach ($captiveList as $captive) {
                            if ($captive['captiveOf'] == 'barbarians') {
                                $game->log(_('The barbarians slaughter %1$s, whose ransom was not paid by [['.$party->getUser_id().']]') , 'log' , array($captive['senatorID'])) ;
                                $killMessage = $game->killSenator($captive['senatorID'] , TRUE ) ;
                                $game->log($killMessage[0] , $killMessage[1]) ;
                            }
                        }
                    }
                }
                $game->setSubPhase('RollEvent') ;
                $game->setInitiative(1) ;
                $game->log('Initiative #1' , 'alert' ) ;
            }
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_revenueContributionsDone');

        /*
        * POST target
        * Verb : revenueRedistribute
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/revenueRedistribute', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;

                $this->doTransfer($game , $user_id , $json_data['from'] , $json_data['to'] , $json_data['value']) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
        })
        ->bind('verb_revenueRedistribute');
        
        /*
        * POST target
        * Verb : revenueContributions
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/revenueContributions', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
                $json_data = $request->request->all() ;
                $user_id = (int)$json_data['user_id'] ;
                $this->revenueContributions($game , $user_id , $json_data['senatorID'] , $json_data['value']) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
        })
        ->bind('verb_revenueContributions');

        /*
        * POST target
        * Verb : revenueRedistributionDone
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/revenueRedistributionDone', function($game_id , Request $request) use ($app)
        {
            try 
            {
                /** @var \Entities\Game $game */
                $game = $app['getGame']((int)$game_id) ;
            }
            catch (\Exception $exception)
            {
                $app['session']->getFlashBag()->add('danger', $exception->getMessage());
                return $app->json( $exception->getMessage() , 201);
            }
            $user_id = (int)$app['user']->getId() ;
            /** @todo Offer an interface to the HRAO for disbanding legions that were released by rebels 
             The HRAO cannot setDone to TRUE as long as there is released legions */
            $game->getParty($user_id)->setIsDone(TRUE) ;
            if ($game->isEveryoneDone())
            {
                $app['saveGame']($game) ;
                $this->doRomeRevenue($game) ;
                $game->setSubPhase('Contributions') ;
                $game->resetAllIsDone() ;
            }
            $this->entityManager->persist($game);
            $this->entityManager->flush();
            return $app->json( 'SUCCESS' , 201);
        })
        ->bind('verb_revenueRedistributionDone');

        return $controllers ;
    }

    /**
     * Performs all Revenue operations both standard (senators, knights, etc...) and special (Concessions drought income, Provincial spoils, rebel legions maintenance)
     * For special revenue, the player's choice must have been submitted through JSON data. The function checks if all submitted data is correct and if it was all used properly
     * @param \Entities\Game $game
     * @param int $user_id
     * @param array $submittedData : For Concessions & Provinces in the form [card_id] => choice (scalar or array). For legions, in the form [legion id] => choice
     */
    private function doRevenue($game , $user_id , $submittedData)
    {
        $data = $submittedData ;
        $party = $game->getParty($user_id) ;
        $base = $party->revenue_base($game->getLegions()) ;
        // Handle normal revenue (senators, leader, knights)
        $game->log( sprintf(
            _('[['.$user_id.']] {gain,gains} %1$dT : %2$dT from the Leader, %3$dT from Senators, %4$dT from Knights, and %5$dT from Concessions.') ,
            $base['total'] ,
            ($base['leader']=='' ? 0 : 3) ,
            $base['senators'] ,
            $base['knights'] ,
            $base['concessions_total']
        )) ;
        $party->changeTreasury($base['total']) ;
        
        // Handle Concessions, including extra revenue from drought (choice should be in 'data')
        foreach($base['concessions'] as $concession)
        {
            $concession['card']->setCorrupt(TRUE) ;
            $droughtLevel = $game->getEventProperty('name' , 'Drought') ;
            if ($droughtLevel>0)
            {
                $choice = $data[$concession['card']->getCardId()] ;
                unset($data[$concession['card']->getCardId()]) ;
                if ($choice == 'YES')
                {
                    $concession['senator']->changeTreasury($droughtLevel*$concession['card']->getIncome()) ;
                    $concession['senator']->changePOP(-1-$droughtLevel);
                    $game->log(
                        _('%1$s : %2$s decides to earn an extra %3$d during the drought, causing him a loss of %4$d POP.') ,
                        'log' ,
                        array( $concession['card']->getName() , $concession['senator']->getName() , $droughtLevel*$concession['card']->getIncome() , 1+$droughtLevel )
                    );
                    
                }
                else
                {
                    $game->log(
                        _('%1$s : %2$s decides not to earn more during the drought.') ,
                        'log' ,
                        array( $concession['card']->getName() , $concession['senator']->getName())
                    );
                }
            }
            /**  @todo : Figure out what this error was about. Shouldn't it be an elseif with a test on $data[$concession['card']->getCardId()] ? */
            /*
            else
            {
                $game->log(_('ERROR - Drought-related choices were made without a drought. Stop cheating.') , 'error') ;
            }
             */
        }

        // Handle Provincial spoils, choice should be in 'data'
        foreach($base['provinces'] as $province)
        {
            $choice = $data[$province['card']->getCardId()] ;
            unset($data[$province['card']->getCardId()]) ;
            // Check if province was overrun by barbarians / internal disorder
            if (!$province['card']->getOverrun())
            {

                // Spoils
                if ($choice['spoils'] == 'YES')
                {
                    $revenue = $province['card']->rollRevenues('senator' , -$game->getEventProperty('name' , 'Evil Omens'));
                    if ($province['senator']->getCorrupt())
                    {
                        $corruptMessage=_(' He was already corrupt.');
                    }
                    else
                    {
                        $province['senator']->setCorrupt(TRUE) ;
                        $corruptMessage=_(' He is now corrupt.');
                    }
                    $romeMessage='' ;
                    // Positive Revenue : give them to the Senator
                    if ($revenue>=0)
                    {
                        $province['senator']->changeTreasury($revenue);
                    }
                    
                    // Negative Revenue : Check whether the Senator wanted Rome to pay and/or can't pay
                    else
                    {
                        if ($choice['rome'] == 'YES')
                        // The Senator decided to let Rome pay for it
                        {
                            $game->changeTreasury($revenue);
                            $romeMessage=_(' He decides to let Rome pay for it.') ;
                        }
                        else
                        {
                            // The senator is forced to let Rome pay because of his treasury
                            if ($province['senator']->getTreasury()<-$revenue)
                            {
                                $game->changeTreasury($revenue);
                                $romeMessage=_(' Rome pays for it since he can\'t.') ;
                            }

                            // The Senator decided to pay for it
                            else
                            {
                                $province['senator']->changeTreasury($revenue);
                                $romeMessage=_(' He decides to pay for it.') ;
                            }
                        }
                    }
                    // Generate the log with Province name, Senator name, revenue generated, evil omens message, optional Rome message , corrupt message.
                    $game->log(
                        _('%1$s : %2$s takes Provincial spoils for %3$d%4$s.%5$s%6$s') ,
                        'log' ,
                        array( $province['card']->getName() , $province['senator']->getName() , $revenue , $game->getEvilOmensMessage(-1) , $romeMessage , $corruptMessage )
                    );

                }

                // No spoils
                else
                {
                    $game->log(
                        _('%1$s : %2$s decides not to take Provincial spoils') ,
                        'log' ,
                        array( $province['card']->getName() , $province['senator']->getName())
                    );
                }

                // Develop province
                if ( !($province['card']->getDeveloped()))
                {
                    $roll = $game->rollOneDie(-1) ;
                    $modifier = ( ($province['senator']->getCorrupt()) ? 0 : 1) ;
                    if ( ($roll+$modifier) >= 6 )
                    {
                        $developMessage = sprintf(_('The province is developed. %s gains 3 INFLUENCE') , $province['senator']->getName() ) ;
                        $province['card']->setDeveloped(TRUE) ;
                        $province['senator']->changeINF(+3);
                    }
                    else
                    {
                        $developMessage = _('The province is not developed.') ;
                    }
                    $game->log(
                        _('Development of %5$s: A %1$d is rolled%2$s%3$s. %4$s') ,
                        'log' ,
                        array( $roll , ($modifier==1 ? _(' (modified by +1 since senator is not corrupt)') : '') , $game->getEvilOmensMessage(-1) , $developMessage , $province['card']->getName() )
                    );
                }
            }
            // Province was overrun by Barbarians and/or internal disorder. No revenue nor development this turn.
            else
            {
                /** @todo Province was overrun by Barbarians and/or internal disorder. No revenue nor development this turn. */
            }

        }

        // Handle rebel legions maintenance, choice should be in 'data'
        foreach($base['rebels'] as $rebel)
        {
            //TO DO
        }
        
        $party->setIsDone(TRUE) ;
    }
    
    /**
     * This function transfers money based on the $data submitted.
     * @param \Entities\Game $game
     * @param int $user_id
     * @param json $from
     * @param json $to
     * @param int $amount
     * @throws \Exception
     */
    private function doTransfer($game , $user_id , $from , $to , $amount)
    {
        if ((int)$amount <= 0)
        {
            throw new \Exception(_('ERROR - Wrong amount'));
        }
        
        /**
         * From
         */
        $fromParty=FALSE ;
        if (isset($from['senatorID']))
        {
            // From Senator $from['senatorID']
            $fromEntity = $game->getFilteredCards(array('senatorID'=>$from['senatorID']))->first() ;
            if ($fromEntity->getLocation()['type']!=='party' || $fromEntity->getLocation()['value']->getUser_id()!==$user_id)
            {
                throw new \Exception(_('ERROR - Senator is not in your party'));
            }
        }
        else
        {
            // From Party treasury
            $fromEntity = $game->getParty($user_id) ;
            $fromParty=TRUE ;
        }

        /**
         * Enough talents ?
         */
        if ($fromEntity->getTreasury() < (int)$amount)
        {
            throw new \Exception(_('ERROR - Not enough talents'));
        }
        
        /**
         * To
         */
        $toParty=FALSE ;
        if (isset($to['senatorID']))
        {
            // To Senator $to['senatorID']
            $toEntity = $game->getFilteredCards(array('senatorID'=>$to['senatorID']))->first() ;
            if ($toEntity->getLocation()['type']!=='party' || $toEntity->getLocation()['value']->getUser_id()!==$user_id)
            {
                throw new \Exception(_('ERROR - Senator is not in your party'));
            }
        }
        else
        {
            // To party $to['user_id']
            $toEntity = $game->getParty($to['user_id']) ;
            $toParty = ($to['user_id'] == $user_id) ;
        }
        $fromEntity->changeTreasury(-$amount) ;
        $toEntity->changeTreasury($amount) ;
        $game->log(_('[['.$user_id.']] {transfer '.(int)$amount.'T , transfers money} from '.($fromParty ? _('party treasury') : $fromEntity->getName()).' to '.($toParty ? _('party treasury') : $toEntity->getName())) , 'log' ) ;
    }
    
    /**
     * This function gives money to Rome based on the $data submitted.
     * @param \Entities\Game $game
     * @param int $user_id
     * @param string $senatorID
     * @param int $amount
     * @throws \Exception
     */
    private function revenueContributions($game , $user_id , $senatorID , $amount)
    {
        /**
         * Validation
         */
        $giver = $game->getFilteredCards(array('senatorID' => $senatorID))->first() ;
        if ($giver->getLocation()['type']!=='party' || $giver->getLocation()['value']->getUser_id()!==$user_id)
        {
            throw new \Exception(_('ERROR - Contribution from Senator in wrong party'));
        }
        if ((int)$amount<=0 || (int)$amount>$giver->getTreasury())
        {
            throw new \Exception(_('ERROR - Invalid amount for Contribution'));
        }
        $INFgain = 0 ;
        $INFgainMessage = '' ;
        if ($amount>=50)
        {
            $INFgain = 7 ;
            $INFgainMessage = ' He gains 7 INF.' ;
        }
        elseif ($amount>=25)
        {
            $INFgain = 3 ;
            $INFgainMessage = ' He gains 3 INF.' ;
        }
        elseif ($amount>=10)
        {
            $INFgain = 1 ;
            $INFgainMessage = ' He gains 1 INF.' ;
        }
        else
        {
            $INFgain = 0 ;
            $INFgainMessage = ' He doesn\'t gain any INF.' ;
        }
        $giver->changeTreasury(-$amount) ;
        $giver->changeINF($INFgain) ;
        $game->changeTreasury($amount) ;
        $game->log(
            _('%1s ([['.$user_id.']]) gives %2dT to Rome.%3s') ,
            'log' ,
            array(
                $giver->getName() ,
                $amount ,
                $INFgainMessage
            ) 
        ) ;
    }

    
    /**
     * Generates the revenues for Rome : 100T, Allied Enthusiasm, Provinces (both for aligned and unaligned Senators)
     * @param \Entities\Game $game
     */
    private function doRomeRevenue($game)
    {
        $game->log(_('State revenues') , 'alert') ;
        $game->changeTreasury(100) ;
        $game->log(_('Rome collects 100T.')) ;
        // Allied Enthusiasm event
        $alliedEnthusiasmEvent = $game->getEventProperty('name' , 'Allied Enthusiasm' , 'ALL') ;
        if ($alliedEnthusiasmEvent['level']>0)
        {
            $name = ($alliedEnthusiasmEvent['level'] ? 'name' : 'increased_name') ;
            $description = ($alliedEnthusiasmEvent['level']==1 ? 'description' : 'increased_description') ;
            array_push($messages , array($this->events[162][$name].' : '.$this->events[162][$description]) );
            $game->changeTreasury($alliedEnthusiasmEvent['level']==1 ? 50 : 75);
            $game->setEventLevel ('name' , 'Allied Enthusiasm' , 0) ;
            $game->log($alliedEnthusiasmEvent[$name].' : '.$alliedEnthusiasmEvent[$description]) ;
        }
        // Provinces revenues for aligned Senators
        foreach ($game->getParties() as $party)
        {
            foreach ($party->getSenators()->getCards() as $senator)
            {
                foreach ($senator->getCardsControlled()->getCards() as $province)
                {
                    if ($province->getPreciseType()=='Province')
                    {
                        $revenue = $province->rollRevenues('rome' , -$game->getEventProperty('name' , 'Evil Omens'));
                        $game->changeTreasury($revenue);
                        $game->log(_('%1$s : Rome\'s revenue is %2$dT .') , 'log' , array($province->getName() , $revenue) ) ;
                    }
                }
            }
        }
        // Provinces revenues for unaligned Senators
        if ($game->getDeck('forum')->getNumberOfCards()>0)
        {
            foreach ($game->getDeck('forum')->getCards() as $senator)
            {
                if ($senator->getPreciseType()=='Senator' || $senator->getPreciseType()=='Statesman')
                {
                    foreach ($senator->getCardsControlled()->getCards() as $province)
                    {
                        if ($province->getPreciseType()=='Province')
                        {
                            $revenue = $province->rollRevenues('rome' , -$game->getEventProperty('name' , 'Evil Omens'));
                            $game->changeTreasury($revenue);
                            $game->log(_('%1$s : Rome\'s revenue is %2$dT .') , 'log' , array($province->getName() , $revenue) ) ;
                        }
                    }
                }
            }
        }
    }
    
    /**
     * 
     * @param \Entities\Game $game
     */
    private function doStateExpenses($game)
    {
        $game->log(_('State expenses') , 'alert') ;
        // Wars
        $nbUnprosecutedWars = $game->getDeck('unprosecutedWars')->getNumberOfCards();
        $nbActiveWars = $game->getDeck('activeWars')->getNumberOfCards();
        $game->log(_('Rome pays %1$dT for %2$d unprosecuted war%3$s and %4$d active war%5$s.') , 'log' , 
            array(
                ($nbUnprosecutedWars+$nbActiveWars)*20 ,
                $nbUnprosecutedWars ,
                ($nbUnprosecutedWars > 1 ? 's' : '') ,
                $nbActiveWars ,
                ($nbActiveWars > 1 ? 's' : '')
            )
        ) ;
        $game->changeTreasury( - 20 * $nbUnprosecutedWars - 20 * $nbActiveWars ) ;
        // Land bills
        $landBills = $game->getLandBillsTotalCost() ;
        if ($landBills['total']>0)
        {
            $game->log(_('Rome pays %1$dT for %2$s') , 'log' , array($landBills['total'] , $landBills['message'])) ;
            $game->changeTreasury( - $landBills['total'] ) ;
        }
        // Forces
        $nbLegions = 0 ;
        $nbFleets = 0 ;
        foreach ($game->getLegions() as $legion)
        {
            $nbLegions += ($legion->romeMaintenance() ? 1 : 0 ) ;
        }
        foreach ($game->getFleets() as $fleet)
        {
            $nbFleets += ($fleet->romeMaintenance() ? 1 : 0 ) ;
        }
        $game->log(_('Rome pays %1$dT for %2$d legion%3$s and %4$d fleet%5$s.') , 'log' , 
            array(
                2*($nbLegions+$nbFleets) ,
                $nbLegions ,
                ($nbLegions > 1 ? 's' : '') ,
                $nbFleets ,
                ($nbFleets > 1 ? 's' : '') ,
            )
        ) ;
        $game->changeTreasury( - 2 *($nbLegions+$nbFleets) ) ;
        // Returning governors
        foreach($game->getParties() as $party) {
            /* @var $party Party */
            foreach ($party->getSenators()->getCards() as $senator) {
                // returningGovernor is used during the Senate phase : returning governors cannot be appointed governor again on the turn of their return without their approval
                $senator->setReturningGovernor(FALSE) ;
                foreach ($senator->getCardsControlled()->getCards() as $card) {
                    if ($card->getPreciseType()=='Province') {
                        $card->changeMandate(1);
                        if ($card->getMandate() == 3) {
                            $game->log(_('%1$s returns from %2$s which is placed in the Forum.') , 'log' , array($senator->getName() , $card->getName())) ;
                            $card->setMandate(0);
                            $senator->getCardsControlled()->getFirstCardByProperty('cardId' , $card->getID() , $game->getDeck('forum')) ;
                            $senator->setReturningGovernor(TRUE);
                        } else {
                            $game->log(_('%1$s spends %2$s game turn in %3$s') , 'log' , array($senator->getName() , ( ($card->getMandate()==1) ? _('First') : _('Second') ) , $card->getName())) ;
                        }
                    }
                }
            }
        }
        // Handle unaligned senators who are governors
        foreach ($game->getDeck('forum')->getCards() as $senator)
        {
            if ($senator->getPreciseType()=='Family')
            {
                $senator->setReturningGovernor(FALSE) ;
                foreach ($senator->getCardsControlled()->getCards() as $card)
                {
                    /* @var $card Province */
                    if ($card->getPreciseType()=='Province')
                    {
                        $card->changeMandate(1) ;
                        if ($card->getMandate() == 3) {
                            $game->log(_('%1$s (unaligned) returns from %2$s which is placed in the Forum.') , 'log' , array($senator->getName() , $card->getName())) ;
                            $card->setMandate(0);
                            $senator->getCardsControlled()->getFirstCardByProperty('senatorID' , $senator->getSenatorID() , getDeck('forum')) ;
                            $senator->setReturningGovernor(TRUE);
                        } else {
                            $game->log(_('%1$s (unaligned) spends %2$s game turn in %3$s') , 'log' , array($senator->getName() , ( ($card->getMandate()==1) ? _('First') : _('Second') ) , $card->getName())) ;
                        }
                    }
                }
            }
        }
    }
}
