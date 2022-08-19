<?php
/**
*   LoginManager
*   Simple, barebones login manager for PHP, JavaScript, Python
*
*   @version 1.1.1
*   https://github.com/foo123/LoginManager
*
**/

if (!class_exists('LoginManager', false))
{
class LoginManagerException extends Exception
{
    public function __construct($message = "", $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
class LoginManagerUser
{
    private $username = '';
    private $password = '';
    private $user = null;

    public function __construct($username = '', $password = '', $user = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->user = $user;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getOriginalUser()
    {
        return $this->user;
    }
}
class LoginManager
{
    const VERSION = '1.1.1';

    private $opts = null;
    private $user = null;
    private $guest = null;
    private $check = true;

    public function __construct()
    {
        $this->check = true;
        $this->user = null;
        $this->guest = null;
        $this->opts = array();
        // some default options
        $this->option('remember_duration', 6 * 30);
        $this->option('loggedin_duration', 1);
        $this->option('password_fragment', array(4, 8));
        $this->option('auth_token', 'authtoken');
        $this->option('set_token', function($name, $value, $expires) {});
        $this->option('unset_token', function($name) {});
        $this->option('get_token', function($name) {return null;});
        $this->option('get_user', function($username, $password = false) {return null;});
        $this->option('get_guest', function() {return null;});
    }

    public function option($key, $val = null)
    {
        $nargs = func_num_args();
        if (1 == $nargs)
        {
            return isset($this->opts[$key]) ? $this->opts[$key] : null;
        }
        elseif (1 < $nargs)
        {
            $this->opts[$key] = $val;
        }
        return $this;
    }

    public function getUser()
    {
        $this->checkLogin();
        return $this->user instanceof LoginManagerUser ? $this->user->getOriginalUser() : ($this->guest instanceof LoginManagerUser ? $this->guest->getOriginalUser() : $this->guest);
    }

    public function isLoggedIn()
    {
        $this->checkLogin();
        return $this->user instanceof LoginManagerUser;
    }

    public function login($username, $password, $remember = false)
    {
        $user = call_user_func($this->option('get_user'), $username, $password);
        if (empty($user)) return false;
        if (!($user instanceof LoginManagerUser)) throw new LoginManagerException('get_user callback must return an instance of LoginManagerUser');

        if ($remember)
        {
            $expiration = time() + (int)$this->option('remember_duration') * 24 * 60 * 60;
        }
        else
        {
            $expiration = time() + (int)$this->option('loggedin_duration') * 24 * 60 * 60;
        }

        $token = $user->getUsername() . '|' . $expiration . '|' . $this->hmac($user->getUsername(), $user->getPassword(), $expiration);

        call_user_func($this->option('set_token'), $this->option('auth_token'), $token, $expiration + (1 * 60 * 60));

        $this->user = $user;
        $this->guest = null;
        $this->check = true;

        return true;
    }

    public function logout()
    {
        call_user_func($this->option('unset_token'), $this->option('auth_token'));

        $this->user = null;
        $this->guest = null;
        $this->check = true;

        return true;
    }

    private function checkGuest()
    {
        $this->guest = call_user_func($this->option('get_guest'));
        return $this;
    }

    private function checkLogin()
    {
        if ($this->check)
        {
            $this->check = false;

            if ($this->user instanceof LoginManagerUser) return $this;

            $this->user = null;
            $this->guest = null;
            $token = call_user_func($this->option('get_token'), $this->option('auth_token'));
            if (empty($token)) return $this->checkGuest();

            $token_parts = explode('|', $token);
            if (count($token_parts) !== 3) return $this->checkGuest();

            list($username, $expiration, $hmac) = $token_parts;
            $expired = $expiration;

            if ($expired < time()) return $this->checkGuest();

            $user = call_user_func($this->option('get_user'), $username, false);
            if (empty($user)) return $this->checkGuest();
            if (!($user instanceof LoginManagerUser)) throw new LoginManagerException('get_user callback must return an instance of LoginManagerUser');

            $hmac2 = $this->hmac($user->getUsername(), $user->getPassword(), $expiration);
            if (!hash_equals($hmac2, $hmac)) return $this->checkGuest();

            $this->user = $user;
        }
        return $this;
    }

    private function hmac($username, $password, $expiration)
    {
        list($start, $end) = $this->option('password_fragment');
        $passw = substr((string)$password, $start, $end-$start);
        $salt = (string)($this->option('secret_salt') ? $this->option('secret_salt') : '');
        $key = hash_hmac('md5', $username . '|' . $passw . '|' . $expiration, $salt);
        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $hmac = hash_hmac(function_exists('hash') ? 'sha256' : 'sha1', $username . '|' . $expiration, $key);
        return $hmac;
    }
}
}