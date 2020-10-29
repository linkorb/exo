<?php

namespace Exo\Worker;

interface WorkerInterface
{
    public function popRequest(): ?array;
    public function pushResponse(array $request, array $response): void;
}
