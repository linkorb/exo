<?php

namespace Exo\Model;

use Collection\TypedArray;
use Collection\Identifiable;
use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;
use Symfony\Component\Console\Exception\RuntimeException;
use Exo\Utils;

class Package extends AbstractModel implements Identifiable
{
    protected $name;
    protected $description;
    protected $path;
    protected $configSchema;
    protected $actions = [];

    public function __construct()
    {
        $this->actions = new TypedArray(Action::class);
    }

    public function identifier()
    {
        return $this->getName();
    }

    public function validateConfig(array $config)
    {
        if (!$this->getConfigSchema()) {
            return true;
        }
        Utils::validateArray($config, $this->getConfigSchema());
    }

}
