<?php

namespace App\Enums;

enum TicketSentiment: string
{
    case POSITIVE = 'Positive';
    case NEUTRAL = 'Neutral';
    case NEGATIVE = 'Negative';

    public function label(): string
    {
        return match ($this) {
            self::POSITIVE => 'Positive',
            self::NEUTRAL => 'Neutral',
            self::NEGATIVE => 'Negative',
        };
    }
}
