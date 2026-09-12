<?php

namespace App\Services\Evaluation;

/**
 * Result of evaluating one practice response. `feedback` is learner-facing Persian text;
 * there is deliberately no score field anywhere in the system (PRD §9).
 */
final readonly class Verdict
{
    public const CORRECT = 'correct';

    public const PARTIAL = 'partial';

    public const INCORRECT = 'incorrect';

    public function __construct(public string $verdict, public string $feedback) {}

    public function isCorrect(): bool
    {
        return $this->verdict === self::CORRECT;
    }

    public function isPartial(): bool
    {
        return $this->verdict === self::PARTIAL;
    }
}
