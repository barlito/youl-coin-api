<?php

use Castor\Attribute\AsContext;
use Castor\Context;

use function Castor\import;

#[AsContext(default: true, name: 'my_context')]
function my_context(): Context
{
    return new Context(environment: ['STACK_NAME' => 'youl_coin']);
}

import('make/castor_entrypoint.php');