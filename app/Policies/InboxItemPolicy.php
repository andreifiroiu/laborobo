<?php

namespace App\Policies;

use App\Models\InboxItem;
use App\Models\User;

class InboxItemPolicy
{
    public function view(User $user, InboxItem $inboxItem): bool
    {
        return $user->currentTeam?->id === $inboxItem->team_id;
    }

    public function update(User $user, InboxItem $inboxItem): bool
    {
        return $user->currentTeam?->id === $inboxItem->team_id;
    }

    public function delete(User $user, InboxItem $inboxItem): bool
    {
        return $user->currentTeam?->id === $inboxItem->team_id;
    }
}
