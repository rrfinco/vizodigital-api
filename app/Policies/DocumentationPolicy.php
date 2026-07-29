<?php

namespace App\Policies;

use App\Models\User;

/**
 * CMS documentation gates. Uses hasPermissionTo() to avoid recursion with Gate::define.
 */
class DocumentationPolicy
{
    public function viewAdmin(User $user): bool
    {
        return $user->hasPermissionTo('docs.view_admin');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('docs.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('docs.update');
    }

    public function publish(User $user): bool
    {
        return $user->hasPermissionTo('docs.publish');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('docs.delete');
    }

    public function preview(User $user): bool
    {
        return $user->hasPermissionTo('docs.preview');
    }
}
