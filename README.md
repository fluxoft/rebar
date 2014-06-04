[![Build Status](https://travis-ci.org/fluxoft/rebar.svg?branch=master)](https://travis-ci.org/fluxoft/rebar)

http://en.wikipedia.org/wiki/Rebar

This is a framework. There are many like it. This one is mine.

Use at your own risk. This is changing on an hourly basis at the moment; I just stuck it up here so I could more easily
share it between a couple different projects I'm using it on at once.

Check back in a couple months and maybe there will be a stable release. (Why are you reading this, anyway?)

The idea behind this framework is that it should be simple to use and quick to deploy, but have as few moving parts as
possible, so that it's easily understood and maintained when I inevitably have to come back to revisit something a year
or more from now.

At its simplest, I use Rebar by directing all traffic to an index.php which calls the Router class.

index.php
```
$loader = require_once 'vendor/autoload.php';

$router = new Fluxoft\Rebar\Router(array(
	'namespace' => 'SiteSpecificClasses'
));
$router->Route();
```

If no special routes are configured, the Router will split the path into parts and use those parts to instantiate a
controller, call a method, and pass in the rest as "URL params."  For instance, _/test/url/123/456_ will create an object
from the following class and call its Url method, passing in the rest of the path parts, along with :

\SiteSpecificClasses\Test
```
namespace SiteSpecificClasses;

class Test extends \Fluxoft\Rebar\Controller {
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