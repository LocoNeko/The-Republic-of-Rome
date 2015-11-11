<?php
namespace Controllers ;

use Silex\Application;
use Silex\ControllerProviderInterface;
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
            $game = $app['getGame']((int)$game_id) ;
            if ($game===FALSE)
            {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not found.') , (int)$game_id ));
                return $app->redirect('/') ;
            }
            elseif(!$game->gameStarted())
            {
                $app['session']->getFlashBag()->add('alert', sprintf(_('Error - Game %1$s not started.') , (int)$game_id ));
                return $app->redirect('/') ;
            }
            else
            {
                return $app['twig']->render('BoardElements/Main.twig', array(
                    'layout_template' => 'layout.twig' ,
                    'game' => $game
                ));
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
            $game = $app['getGame']((int)$game_id) ;
            $user_id = (int)$app['user']->getId() ;
            if ($game!==FALSE)
            {
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
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_RevenueDone');

        /*
        * POST target
        * Verb : Redistribute
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/Redistribute', function($game_id , Request $request) use ($app)
        {
            $game = $app['getGame']((int)$game_id) ;
            $user_id = (int)$app['user']->getId() ;
            if ($game!==FALSE)
            {
                $this->doTransfer($game , $user_id , $request->request->all()) ;
                $this->entityManager->persist($game);
                $this->entityManager->flush();
                return $app->json( 'SUCCESS' , 201);
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_Redistribute');
        
        /*
        * POST target
        * Verb : RedistributionDone
        * JSON data : user_id
        */
        $controllers->post('/{game_id}/RedistributionDone', function($game_id , Request $request) use ($app)
        {
            $game = $app['getGame']((int)$game_id) ;
            $user_id = (int)$app['user']->getId() ;
            if ($game!==FALSE)
            {
                // TO DO : Offer an interface to the HRAO for disbanding legions that were released by rebels
                // The HRAO cannot setDone to TRUE as long as there is released legions
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
            }
            else
            {
                $app['session']->getFlashBag()->add('danger', sprintf(_('Error - Game %1$s not found.') , $game_id ));
                return $app->json( sprintf(_('Error - Game %1$s not found.') , $game_id ) , 201);
            }
        })
        ->bind('verb_RedistributionDone');


        return $controllers ;
    }

    /**
     * Performs all Revenue operations both standard (senators, knights, etc...) and special (Concessions drought income, Provincial spoils, rebel legions maintenance)
     * For special revenue, the player's choice must have been submitted through JSON data. The function checks if all submitted data is correct and if it was all used properly
     * @param Game $game
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
                $choice = $data[$concession['card']->getId()] ;
                unset($data[$concession['card']->getId()]) ;
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
            else
            {
                $game->log(_('ERROR - Drought-related choices were made without a drought. Stop cheating.') , 'error') ;
            }
        }

        // Handle Provincial spoils, choice should be in 'data'
        foreach($base['provinces'] as $province)
        {
            $choice = $data[$province['card']->getId()] ;
            unset($data[$province['card']->getId()]) ;
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
                            $romeMessage=(' He decides to let Rome pay for it.') ;
                        }
                        else
                        {
                            // The senator is forced to let Rome pay because of his treasury
                            if ($province['senator']->getTreasury()<$revenue)
                            {
                                $game->changeTreasury($revenue);
                                $romeMessage=(' Rome pays for it since he can\'t.') ;
                            }

                            // The Senator decided to pay for it
                            else
                            {
                                $province['senator']->changeTreasury($revenue);
                                $romeMessage=(' He decides to pay for it.') ;
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
                        _('Province development : A %1$d is rolled%2$s%3$s. %4$s') ,
                        'log' ,
                        array( $roll , ($modifier==1 ? _(' (modified by +1 since senator is not corrupt)') : '') , $game->getEvilOmensMessage(-1) , $developMessage )
                    );
                }
            }
            // Province was overrun by Barbarians and/or internal disorder. No revenue nor development this turn.
            else
            {
                // TO DO
            }

        }

        // Handle rebel legions maintenance, choice should be in 'data'
        foreach($base['rebels'] as $rebel)
        {
            //TO DO
        }
        
        if (count($data)>0)
        {
            $game->log(_('ERROR - Some revenue decisions have not been processed') , 'error') ;
        }
        $party->setIsDone(TRUE) ;
    }
    
    /**
     * This function transfers money based on the $data submitted.
     * @param Game $game
     * @param int $user_id
     * @param array $data array 'fromSenator','fromParty','toSenator','toParty','amount'<br>
     * For senators : card_id|'' , for parties : user_id|'' , for amount : (int)
     * @return boolean Success or failure
     */
    private function doTransfer($game , $user_id , $data)
    {
        $party = $game->getParty($user_id) ;
        /* Checks on the data :
         * - fromSenator & fromParty cannot both be set
         * - fromSenator & fromParty cannot both be null
         * - toSenator & toParty cannot both be set
         * - toSenator & toParty cannot both be null
         * - fromParty can only be $user_id
         * - fromSenator & toSenator can only be in the party 
         */
        if (
            ((int)$data['amount']<=0) ||
            ($data['fromSenator']!='' && $data['fromParty']!='') ||
            ($data['fromSenator']=='' && $data['fromParty']=='') ||
            ($data['toSenator']!='' && $data['toParty']!='') ||
            ($data['toSenator']=='' && $data['toParty']=='') ||
            ($data['fromParty']!='' && $data['fromParty']!=$user_id) ||
            ($data['fromSenator']!='' && ($party->getSenators()->getFirstCardByProperty('id', $data['fromSenator']) === FALSE) ) ||
            ($data['toSenator']!='' && ($party->getSenators()->getFirstCardByProperty('id', $data['toSenator']) === FALSE) )
        )
        {
            $game->log(_('ERROR - Redistributing from or to wrong Seantor or with invalid amount') , 'error' ) ;
            return FALSE ;
        }
        else 
        {
            $fromText = '' ;
            $toText = '' ;
            // FROM
            if ($data['fromParty']!='')
            {
                if ($party->getTreasury()<(int)$data['amount'])
                {
                    $game->log(_('ERROR - Insufficient funds for Redistribution') , 'error' ) ;
                    return FALSE ;
                }
                else
                {
                    $party->changeTreasury(-(int)$data['amount']) ; $fromText=_(' party treasury');
                }
            }
            else
            {
                $fromSenator = $party->getSenators()->getFirstCardByProperty('id' , $data['fromSenator']) ;
                if ($fromSenator->getTreasury() <(int)$data['amount'])
                {
                    $game->log(_('ERROR - Insufficient funds for Redistribution') , 'error' ) ;
                    return FALSE ;
                }
                else
                {
                    $fromSenator->changeTreasury(-(int)$data['amount']) ; $fromText=$fromSenator->getName();
                }
            }
        
            // TO
            if ($data['toParty']!='')
            {
                $game->getParty($data['toParty'])->changeTreasury((int)$data['amount']) ;
                $toText = ( ($data['toParty']==$user_id) ? _('party treasury') : _('[['.$data['toParty'].']]') ) ;
            }
            else
            {
                $toSenator = $party->getSenators()->getFirstCardByProperty('id' , $data['toSenator']) ;
                $toSenator->changeTreasury((int)$data['amount']) ;
                $toText = $toSenator->getName() ;
            }
            $game->log(_('[['.$user_id.']] {transfer '.(int)$data['amount'].'T , transfers money} from '.$fromText.' to '.$toText) , 'log' ) ;
        }
        return TRUE ;
    }
    
    /**
     * Generates the revenues for Rome : 100T, Allied Enthusiasm, Provinces (both for aligned and unaligned Senators)
     * @param Game $game
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
        foreach ($game->getDeck('Forum')->getCards() as $senator)
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