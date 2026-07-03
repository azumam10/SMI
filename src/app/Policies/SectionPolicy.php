<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Section;
use Illuminate\Auth\Access\HandlesAuthorization;

class SectionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Section');
    }

    public function view(AuthUser $authUser, Section $section): bool
    {
        return $authUser->can('View:Section');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Section');
    }

    public function update(AuthUser $authUser, Section $section): bool
    {
        return $authUser->can('Update:Section');
    }

    public function delete(AuthUser $authUser, Section $section): bool
    {
        return $authUser->can('Delete:Section');
    }

}