<?php
    use Silex\Provider;

    $userServiceProvider = new SimpleUser\UserServiceProvider();
    $app->register($userServiceProvider);

    // Simple user - Firewalls
    $app['security.firewalls'] = array(
        'login' => array(
            'pattern' => '^'.$app['BASE_URL'].'/user/login$',
        ),
        'register' => array(
            'pattern' => '^'.$app['BASE_URL'].'/user/register$',
        ),
        'secured_area' => array(
            'pattern' => '^.*$',
            'anonymous' => false,
            'remember_me' => array(),
            'form' => array(
                'login_path' => $app['BASE_URL'].'/user/login',
                'check_path' => $app['BASE_URL'].'/user/login_check',
            ),
            'logout' => array(
                'logout_path' => $app['BASE_URL'].'/user/logout',
            ),
            'users' => $app->share(function($app) { return $app['user.manager']; }),
        ),
    );
    $app->register(new Provider\RememberMeServiceProvider());
    
    // Simple user - options
    $app['user.options'] = array(
        'templates' => array(
            'layout' => '/layout.twig',
            'register' => '/simpleuser/register.twig',
            'register-confirmation-sent' => '/simpleuser/register-confirmation-sent.twig',
            'login' => '/simpleuser/login.twig',
            'login-confirmation-needed' => '/simpleuser/login-confirmation-needed.twig',
            'forgot-password' => '/simpleuser/forgot-password.twig',
            'reset-password' => '/simpleuser/reset-password.twig',
            'view' => '/simpleuser/view.twig',
            'edit' => '/simpleuser/edit.twig',
            'list' => '/simpleuser/list.twig',
        ),
    );
    
    // Mount SimpleUser routes.
    $app->mount($app['BASE_URL'].'/user', $userServiceProvider);
