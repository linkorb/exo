<?php

namespace Exo\Core\Model;

use Collection\Identifiable;
use Exo\Core\Utils\JsonUtils;

class Action extends AbstractModel implements Identifiable
{
    protected $name;
    protected $description;
    protected $inputSchema;
    protected $outputSchema;
    protected $interpreter;
    protected $handler;
    protected $package;

    public function identifier()
    {
        return $this->getName();
    }

    public function validateInput(array $input)
    {
        if (!$this->getInputSchema()) {
            return true;
        }
        JsonUtils::validateArray($input, $this->getInputSchema());
    }

    public function validateOutput(array $output)
    {
        if (!$this->getOutputSchema()) {
            return true;
        }
        JsonUtils::validateArray($output, $this->getOutputSchema());
    }
}
