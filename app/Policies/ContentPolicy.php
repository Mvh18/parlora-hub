<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Shared rules for the content tables in the admin panel.
 *
 * The versioned JSON under `content/` is the source of truth, so the panel only
 * retouches existing rows: viewing, editing, reordering, archiving and
 * restoring are for users who pass `accessContentPanel`; creating, deleting
 * and replicating are denied to everyone. The panel runs with Filament's
 * strict authorization, so every ability it may check is declared here.
 *
 * Learner-facing abilities (Phase 4: `take`, `unlock`) belong in the concrete
 * policies, not here.
 */
abstract class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canUsePanel($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->canUsePanel($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->canUsePanel($user);
    }

    public function reorder(User $user): bool
    {
        return $this->canUsePanel($user);
    }

    public function archive(User $user, Model $record): bool
    {
        return $this->canUsePanel($user);
    }

    public function restore(User $user, Model $record): bool
    {
        return $this->canUsePanel($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Model $record): bool
    {
        return false;
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    /** Bulk restore is not offered; rows are restored one at a time. */
    public function restoreAny(User $user): bool
    {
        return false;
    }

    private function canUsePanel(User $user): bool
    {
        return Gate::forUser($user)->allows('accessContentPanel');
    }
}
