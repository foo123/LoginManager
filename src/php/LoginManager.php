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
class LoginManagerUser
{
    public $username = '';
    public $password = '';
    public $user = null;

    public function __construct($username, $password, $user = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->user = $user;
    }
}
class LoginManager
{
    const VERSION = '1.0.0';

    private $_opts = array();
    private $_user = null;

    public function __construct()
    {
        $this->_user = null;
        $this
            ->option('auth_token', 'authtoken')
            ->option('auth_method', 'cookie'/*'session'*/)
            ->option('get_cookie', function($name) {return null;})
            ->option('get_session', function($name) {return null;})
            ->option('set_cookie', function($name, $value) {})
            ->option('set_session', function($name, $value) {})
            ->option('login_user', function($username, $password) {return null;})
            ->option('get_user', function($username) {return null;})
            ->option('remember_duration', 6 * 30)
            ->option('loggedin_duration', 1)
        ;
    }

    public function option($key, $val = null)
    {
        $nargs = func_num_args();
        if (1 == $nargs)
        {
            return isset($this->_opts[$key]) ? $this->_opts[$key] : null;
        }
        elseif (1 < $nargs)
        {
            $this->_opts[$key] = $val;
        }
        return $this;
    }

    public function getUser()
    {
        return $this->_user;
    }

    public function login($username, $password, $remember = false)
    {
        $user = call_user_func($this->option('login_user'), $username, $password);
        if (empty($user)) return false;
        $this->setAuthToken(new LoginManagerUser($username, $password, $user), $remember);
        return true;
    }

    public function logout()
    {
        $this->unsetAuthToken();
        return true;
    }

    public function currentUser()
    {
    }

    private function salt()
    {
        static $cached_salt = null;
        if (isset($cached_salt)) return $cached_salt;

        $values = array(
            'key' => (string)($this->option('secret_key') ? $this->option('secret_key') : ''),
            'salt' => (string)($this->option('secret_salt') ? $this->option('secret_salt') : '')
        );

        $cached_salt = $values['key'] . $values['salt'];

        return $cached_salt;
    }

    private function hash($data)
    {
        return hash_hmac('md5', $data, $this->salt());
    }

    private function generateAuthToken($user, $expiration)
    {
        if (!$user) return '';

        $pass_frag = substr($user->password, 8, 4);
        $key = $this->hash($user->username . '|' . $pass_frag . '|' . $expiration);

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists('hash') ? 'sha256' : 'sha1';
        $hash = hash_hmac($algo, $user->username . '|' . $expiration, $key);
        $token = $user->username . '|' . $expiration . '|' . $hash;

        return $token;
    }

    private function validateAuthToken($token = '', $user = null)
    {
        $this->_user = null;
        if (empty($token) || !$user) return false;

        $token_parts = explode('|', $token);
        if (count($token_parts) !== 3) return false;

        list($username, $expiration, $hmac) = $token_parts;
        $expired = $expiration;

        // Quick check to see if an honest cookie has expired
        if ($expired < time()) return false;

        $pass_frag = substr($user->password, 8, 4);
        $key = $this->hash($username . '|' . $pass_frag . '|' . $expiration);

        // If ext/hash is not present, compat.php's hash_hmac() does not support sha256.
        $algo = function_exists('hash') ? 'sha256' : 'sha1';
        $hash = hash_hmac($algo, $username . '|' . $expiration, $key);
        if (!hash_equals($hash, $hmac)) return false;

        $this->_user = $user;
        return true;
    }

    private function setAuthToken($user, $remember = false)
    {
        if ($remember)
        {
            $expiration = time() + (int)$this->option('remember_duration') * 24 * 60 * 60;
            $expire = $expiration + (12 * 60 * 60);
        }
        else
        {
            $expiration = time() + (int)$this->option('loggedin_duration') * 24 * 60 * 60;
            $expire = 0;
        }

        switch ($this->option('auth_method'))
        {
            case 'session':
                call_user_func($this->option('set_session'), $this->option('auth_token'), $this->generateAuthToken($user, $expiration));
                $this->_user = $user;
                break;

            case 'cookie':
            default:
                call_user_func($this->option('set_cookie'), $this->option('auth_token'), (object)array(
                    'name' => $this->option('auth_token'),
                    'value' => $this->generateAuthToken($user, $expiration),
                    'expires' => $expire
                ));
                $this->_user = $user;
                break;
        }

        return $this;
    }

    private function unsetAuthToken()
    {
        switch ($this->option('auth_method'))
        {
            case 'session':
                call_user_func($this->option('set_session'), $this->option('auth_token'), null);
                $this->_user = null;
                break;

            case 'cookie':
            default:
                call_user_func($this->option('set_cookie'), $this->option('auth_token'), null);
                $this->_user = null;
                break;
        }

        return $this;
    }
}
}