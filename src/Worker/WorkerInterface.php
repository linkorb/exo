<?php

namespace Exo\Worker;

interface WorkerInterface
{
    public function popRequest(): ?array;
    public function pushResponse(array $response): void;
}
