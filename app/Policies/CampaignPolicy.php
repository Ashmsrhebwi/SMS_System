<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function send(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function duplicate(User $user, Campaign $campaign): bool
    {
        return true;
    }

    public function export(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Campaign $campaign): bool
    {
        return $user->isAdmin();
    }
}
