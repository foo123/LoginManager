<?php

define('ROOT', dirname(__FILE__));
include(ROOT.'/../../tico/tico/Tico.php');

class MyModel
{
    private $users = array(
        'foo' => array('username' => 'foo', 'password' => 'bar'),
        'bar' => array('username' => 'bar', 'password' => 'foo'),
    );

    public function getByUsernameAndPassword($username, $password = false)
    {
        return isset($this->users[$username]) && (false === $password || $password === $this->users[$username]['password']) ? (object)$this->users[$username] : null;
    }

    public function getGuestUser()
    {
        return (object)array('username' => 'guest');
    }
}

tico('http://localhost:8000', ROOT)
    ->option('webroot', ROOT)
    ->option('views', [tico()->path('/views')])
    ->option('case_insensitive_uris', true)
    ->set('model', function() {
        return new MyModel();
    })
    ->set('manager', function() {
        include(ROOT.'/../src/php/LoginManager.php');
        return (new LoginManager())
            ->option('secret_salt', 'SECRET_SALT')
            ->option('set_token', function($name, $value, $expires) {
                tico()->request()->cookies->set($name, $value);
                tico()->response()->headers->setCookie(new HttpCookie(
                    $name,
                    $value,
                    $expires,
                    '/',
                    'localhost',
                    false,
                    true
                ));
            })
            ->option('unset_token', function($name) {
                tico()->request()->cookies->set($name, null);
                tico()->response()->headers->setCookie(new HttpCookie(
                    $name,
                    '',
                    0,
                    '/',
                    'localhost',
                    false,
                    true
                ));
            })
            ->option('get_token', function($name) {
                return tico()->request()->cookies->get($name, null);
            })
            ->option('get_user', function($username, $password = false) {
                $user = tico()->get('model')->getByUsernameAndPassword($username, $password);
                return empty($user) ? null : new LoginManagerUser($user->username, $user->password, $user);
            })
            // optional
            ->option('get_guest', function() {
                return new LoginManagerUser('guest', null, tico()->get('model')->getGuestUser());
            })
        ;
    })
    ->middleware(function($next) {

        tico()->get('manager')->getUser();
        $next();

    })
    ->on('*', '/', function() {

        tico()->output(
            array(
                'title' => 'Index',
                'isLoggedIn' => tico()->get('manager')->isLoggedIn(),
                'user' => tico()->get('manager')->getUser()
            ),
            'index.tpl.php'
        );

    })
    ->on('*', '/logout', function() {

        tico()->get('manager')->logout();
        tico()->redirect(tico()->uri('/'), 302);

    })
    ->on(array('get', 'post'), '/login', function($params) {

        if (tico()->get('manager')->isLoggedIn())
        {
            tico()->redirect(tico()->uri('/'), 302);
        }
        elseif ('POST' === tico()->requestMethod())
        {
            tico()->get('manager')->login(tico()->request()->request->get('username', ''), tico()->request()->request->get('password', ''));
            tico()->redirect(tico()->uri('/'), 302);
        }
        else
        {
            tico()->output(
                array(
                    'title' => 'Login'
                ),
                'login.tpl.php'
            );
        }

    })
    ->on(false, function( ) {

        tico()->output(
            array(),
            '404.tpl.php',
            array('StatusCode' => 404)
        );

    })
    ->serve()
;

exit;