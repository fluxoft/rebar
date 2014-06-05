Rebar
=====
http://en.wikipedia.org/wiki/Rebar

This is a framework. There are many like it. This one is mine.

Use at your own risk. This is changing on an hourly basis at the moment; I just stuck it up here so I could more easily
share it between a couple different projects I'm using it on at once.

Check back in a couple months and maybe there will be a stable release. (How did you find this, anyway?)

[![Build Status](https://travis-ci.org/fluxoft/rebar.svg?branch=master)](https://travis-ci.org/fluxoft/rebar)

Why Another Framework?
----------------------
The idea behind this framework is that it should be simple to use and quick to deploy, but have as few moving parts as
possible, so that it's easily understood and maintained when I inevitably have to come back to revisit something a year
or more from now.

If you're like me, you're thinking, "So is it MVC?" Nope, it's RAMP.

RAMP?
-----
It seemed to me to be in vogue now to make up a new development paradigm every couple months, so I jumped on the train
with everybody else. But here's my problem. As a guy who grew up playing with GI Joe action figures, my primary
objection to the acronym soup that has arisen as a result of people defining and redefining web patterns is that the
authors are not spending enough time trying to come up with acronyms that spell out real words. For instance, where is
the equivalent to the H.I.S.S. or the M.O.B.A.T. (which are the Cobra and G.I. Joe tanks, respectively) in these
development patterns?

R.A.M.P. == Request Router, Actor, Model, Presenter

A request is routed to an actor, which takes actions that may involve models, and then presents the data in some form.

Plus, it's a ramp! Pretend you're Evel Knievel and jump a canyon!

How Does It Work?
-----------------
At its simplest, I use Rebar by directing all traffic to an index.php which calls the Router class.

```
**/index.php**
$loader = require_once 'vendor/autoload.php';

$router = new Fluxoft\Rebar\Router(array(
	'namespace' => 'SiteSpecificClasses'
));
$router->Route();
```

If no special routes are configured, the Router will split the path into parts and use those parts to instantiate an
Actor, call a method (action), and pass in the rest as "URL params."  For instance, a request to _/test/url/123/456_
will create an object from the following class and call its Url method, passing in the rest of the path parts:

```
**/app/Actors/Test.php**
namespace SiteSpecificClasses\Actors;

class Test extends \Fluxoft\Rebar\Actor {
    public function Url(array $params) {
        $this->Set('url', $params['url'];
    }
}
```

If no Presenter is specified in the controller, the default Debug presenter will then render the data as so:

```
url (Array) => [
    0 => 123
    1 => 456
]
```
