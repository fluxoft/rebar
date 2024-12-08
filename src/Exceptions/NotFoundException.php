<?php

namespace Fluxoft\Rebar\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \InvalidArgumentException implements NotFoundExceptionInterface {}
