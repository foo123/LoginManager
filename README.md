# LoginManager

Simple, barebones login manager for PHP, JavaScript, Python

version **1.1.0**

![Login Manager](/loginmanager.jpg)

**see also:**

* [ModelView](https://github.com/foo123/modelview.js) a simple, fast, powerful and flexible MVVM framework for JavaScript
* [tico](https://github.com/foo123/tico) a tiny, super-simple MVC framework for PHP
* [LoginManager](https://github.com/foo123/LoginManager) a simple, barebones agnostic login manager for PHP, JavaScript, Python
* [SimpleCaptcha](https://github.com/foo123/simple-captcha) a simple, image-based, mathematical captcha with increasing levels of difficulty for PHP, JavaScript, Python
* [Dromeo](https://github.com/foo123/Dromeo) a flexible, and powerful agnostic router for PHP, JavaScript, Python
* [PublishSubscribe](https://github.com/foo123/PublishSubscribe) a simple and flexible publish-subscribe pattern implementation for PHP, JavaScript, Python
* [Importer](https://github.com/foo123/Importer) simple class &amp; dependency manager and loader for PHP, JavaScript, Python
* [Contemplate](https://github.com/foo123/Contemplate) a fast and versatile isomorphic template engine for PHP, JavaScript, Python
* [HtmlWidget](https://github.com/foo123/HtmlWidget) html widgets, made as simple as possible, both client and server, both desktop and mobile, can be used as (template) plugins and/or standalone for PHP, JavaScript, Python (can be used as [plugins for Contemplate](https://github.com/foo123/Contemplate/blob/master/src/js/plugins/plugins.txt))
* [Paginator](https://github.com/foo123/Paginator)  simple and flexible pagination controls generator for PHP, JavaScript, Python
* [Formal](https://github.com/foo123/Formal) a simple and versatile (Form) Data validation framework based on Rules for PHP, JavaScript, Python
* [Dialect](https://github.com/foo123/Dialect) a cross-vendor &amp; cross-platform SQL Query Builder, based on [GrammarTemplate](https://github.com/foo123/GrammarTemplate), for PHP, JavaScript, Python
* [DialectORM](https://github.com/foo123/DialectORM) an Object-Relational-Mapper (ORM) and Object-Document-Mapper (ODM), based on [Dialect](https://github.com/foo123/Dialect), for PHP, JavaScript, Python
* [Unicache](https://github.com/foo123/Unicache) a simple and flexible agnostic caching framework, supporting various platforms, for PHP, JavaScript, Python
* [Xpresion](https://github.com/foo123/Xpresion) a simple and flexible eXpression parser engine (with custom functions and variables support), based on [GrammarTemplate](https://github.com/foo123/GrammarTemplate), for PHP, JavaScript, Python
* [Regex Analyzer/Composer](https://github.com/foo123/RegexAnalyzer) Regular Expression Analyzer and Composer for PHP, JavaScript, Python


**Example:**

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
// optional
->option('get_guest', function() {
    return new LoginManagerUser('guest', null, (object)array('id'=>0,'username'=>'guest'));
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