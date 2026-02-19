<?php

namespace App\Contracts;

interface AiServiceInterface
{
    public function analyzeTicket(string $description): array;
}
