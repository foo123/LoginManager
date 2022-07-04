<?php
/**
*   LoginManager
*   Simple, barebones login manager for PHP, JavaScript, Python
*
*   @version 1.0.0
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
    const VERSION = '1.0.0';

    private $opts = null;
    private $user = null;

    public function __construct()
    {
        $this->user = null;
        $this->opts = array();
        $this
            ->option('remember_duration', 6 * 30)
            ->option('loggedin_duration', 1)
            ->option('password_fragment', array(4, 8))
            ->option('auth_token', 'authtoken')
            ->option('set_token', function($name, $value, $expires) {})
            ->option('unset_token', function($name) {})
            ->option('get_token', function($name) {return null;})
            ->option('get_user', function($username) {return null;})
            ->option('login_user', function($username, $password) {return null;})
        ;
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
        return $this->user instanceof LoginManagerUser ? $this->user->getOriginalUser() : null;
    }

    public function isLoggedIn($recheck = false)
    {
        static $checked = null;

        if (!empty($this->user)) return true;
        if (!$recheck && null !== $checked) return $checked;

        $checked = $this->validateAuthToken(call_user_func($this->option('get_token'), $this->option('auth_token')));

        return $checked;
    }

    public function login($username, $password, $remember = false)
    {
        $user = call_user_func($this->option('login_user'), $username, $password);
        if (empty($user)) return false;
        if (!($user instanceof LoginManagerUser)) throw new LoginManagerException('User must be an instance of LoginManagerUser');
        $this->user = $user;
        $this->setAuthToken($remember);
        return true;
    }

    public function logout()
    {
        $this->unsetAuthToken();
        return true;
    }

    private function salt()
    {
        return (string)($this->option('secret_salt') ? $this->option('secret_salt') : '');
    }

    private function hash($data)
    {
        return hash_hmac('md5', $data, $this->salt());
    }

    private function generateAuthToken($user, $expiration)
    {
        if (empty($user)) return '';

        list($start, $end) = $this->option('password_fragment');
        $passw = substr($user->getPassword(), $start, $end-$start);
        $key = $this->hash($user->getUsername() . '|' . $passw . '|' . $expiration);

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists('hash') ? 'sha256' : 'sha1';
        $hmac = hash_hmac($algo, $user->getUsername() . '|' . $expiration, $key);
        $token = $user->getUsername() . '|' . $expiration . '|' . $hmac;

        return $token;
    }

    private function validateAuthToken($token)
    {
        $this->user = null;
        if (empty($token)) return false;

        $token_parts = explode('|', $token);
        if (count($token_parts) !== 3) return false;

        list($username, $expiration, $hmac) = $token_parts;
        $expired = $expiration;

        // Quick check to see if an honest cookie has expired
        if ($expired < time()) return false;

        $user = call_user_func($this->option('get_user'), $username);
        if (empty($user)) return false;
        if (!($user instanceof LoginManagerUser)) throw new LoginManagerException('User must be an instance of LoginManagerUser');

        list($start, $end) = $this->option('password_fragment');
        $passw = substr($user->getPassword(), $start, $end-$start);
        $key = $this->hash($username . '|' . $passw . '|' . $expiration);

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists('hash') ? 'sha256' : 'sha1';
        $hash = hash_hmac($algo, $username . '|' . $expiration, $key);
        if (!hash_equals($hash, $hmac)) return false;

        $this->user = $user;
        return true;
    }

    private function setAuthToken($remember = false)
    {
        if ($remember)
        {
            $expiration = time() + (int)$this->option('remember_duration') * 24 * 60 * 60;
        }
        else
        {
            $expiration = time() + (int)$this->option('loggedin_duration') * 24 * 60 * 60;
        }

        call_user_func($this->option('set_token'), $this->option('auth_token'), $this->generateAuthToken($this->user, $expiration), $expiration + (1 * 60 * 60));

        return $this;
    }

    private function unsetAuthToken()
    {
        call_user_func($this->option('unset_token'), $this->option('auth_token'));
        $this->user = null;

        return $this;
    }
}
}