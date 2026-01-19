<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class InvalidTransitionException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $fromStatus,
        public readonly string $toStatus,
        public readonly ?string $reason = null,
    ) {
        parent::__construct($message);
    }

    public static function notAllowed(string $fromStatus, string $toStatus): self
    {
        return new self(
            message: "Transition from '{$fromStatus}' to '{$toStatus}' is not allowed.",
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            reason: 'invalid_transition',
        );
    }

    public static function aiAgentRestricted(string $fromStatus, string $toStatus): self
    {
        return new self(
            message: "AI agents cannot perform the transition from '{$fromStatus}' to '{$toStatus}'. Human approval required.",
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            reason: 'ai_agent_restricted',
        );
    }

    public static function commentRequired(string $fromStatus, string $toStatus): self
    {
        return new self(
            message: "A comment is required for the transition from '{$fromStatus}' to '{$toStatus}'.",
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            reason: 'comment_required',
        );
    }

    public static function permissionDenied(string $fromStatus, string $toStatus): self
    {
        return new self(
            message: "You do not have permission to perform the transition from '{$fromStatus}' to '{$toStatus}'.",
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            reason: 'permission_denied',
        );
    }

    public static function notDesignatedReviewer(string $fromStatus, string $toStatus): self
    {
        return new self(
            message: "Only the designated reviewer can perform the transition from '{$fromStatus}' to '{$toStatus}'.",
            fromStatus: $fromStatus,
            toStatus: $toStatus,
            reason: 'not_designated_reviewer',
        );
    }
}
