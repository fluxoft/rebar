Rebar
=====
http://en.wikipedia.org/wiki/Rebar

This is a framework. There are many like it. This one is mine.

This is beta software. Use at your own risk (although I feel pretty close to making a 1.0 release).

[![Build Status](https://travis-ci.org/fluxoft/rebar.svg?branch=master)](https://travis-ci.org/fluxoft/rebar)

Why Another Framework?
----------------------
Short answer: why not? Slightly longer answer: I wanted something that was an extremely lightweight solution
for knocking together a quick and dirty website that would allow me to easily add various components as
needed. Rebar should be a simple, easy-to-use framework that adds strength while offering flexibility.

It should have as few moving parts as possible, so that it's easily understood and maintained when I 
inevitably have to revisit something a year from now.

How Does It Work?
-----------------
At its simplest, I use Rebar by directing all traffic to an index.php which calls the Router class.

```
**/index.php**
$loader   = require_once 'vendor/autoload.php';
$request  = new Fluxoft\Rebar\Http\Request(
	Fluxoft\Rebar\Http\Environment::GetInstance()
);
$response = new Fluxoft\Rebar\Http\Response();

$router = new Fluxoft\Rebar\Router(array(
	'namespace' => 'SiteSpecificClasses'
));
$router->Route($request, $response);
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

I really wouldn't recommend anyone use this for anything other than maybe to play around a bit at this
point, as I am freely breaking backward-compatibility on a whim from time to time. Watch this space; if
I ever get to a point where I think this is "done" enough that other people could feel safe using it, I
will release a 1.x release and start caring about backwards compatibility. For now, I'm just experimenting.
