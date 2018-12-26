<?php

namespace Exo\Model;

use Collection\TypedArray;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class Service extends AbstractModel
{
    protected $name;
    protected $description;
    protected $actions = [];

    public function __construct()
    {
        $this->actions = new TypedArray(Action::class);
    }
}
