# LoginManager

Simple, barebones login manager for PHP, JavaScript, Python

version 1.0.0

```php
// setup
$lm = new LoginManager();
$lm
->option('secret_salt', 'SALT')
->option('set_token', function($name, $value, $expires) {
    // for example use HTTP cookies to store auth token
    // other option would be storing it in $_SESSION
    $_COOKIE[$name] = $value;
    setcookie(
        $name,
        $value,
        $expires,
        "",
        "",
        false,
        true
    );
})
->option('unset_token', function($name) {
    // for example use HTTP cookies to store auth token
    // other option would be storing it in $_SESSION
    unset($_COOKIE[$name]);
    setcookie(
        $name,
        "",
        0,
        "",
        "",
        false,
        true
    );
})
->option('get_token', function($name) {
    // for example use HTTP cookies to store auth token
    // other option would be storing it in $_SESSION
    return isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
})
->option('get_user', function($username, $password = false) use ($mymodel) {
    $user = $mymodel->findByUserNameAndPassword($username, $password);
    return empty($user) ? null : new LoginManagerUser($user->username, $user->password, $user /*original user object*/);
})
;
// use it
$app->on('/login', function() use ($lm) {
    $lm->login($_POST['username'], $_POST['password'], !empty($_POST['rememberme']));
});
$app->on('/logout', function() use ($lm) {
    $lm->logout();
});
$app->on('/admin', function() use ($lm) {
    if (!$lm->isLoggedIn()) $app->redirect('/login');
    else $app->output('admin.tpl', array('user'=>$lm->getUser() /*original user object*/));
});
```