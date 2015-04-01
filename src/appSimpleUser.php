<?php
    use Silex\Provider;

    $userServiceProvider = new SimpleUser\UserServiceProvider();
    $app->register($userServiceProvider);

    // Simple user - Firewalls
    $app['security.firewalls'] = array(
        'login' => array(
            'pattern' => '^/user/login$',
        ),
        'register' => array(
            'pattern' => '^/user/register$',
        ),
        'secured_area' => array(
            'pattern' => '^.*$',
            'anonymous' => false,
            'remember_me' => array(),
            'form' => array(
                'login_path' => '/user/login',
                'check_path' => '/user/login_check',
            ),
            'logout' => array(
                'logout_path' => '/user/logout',
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
    $app->mount('/user', $userServiceProvider);
