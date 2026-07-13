<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\PerformanceReview;
use Illuminate\Auth\Access\HandlesAuthorization;

class PerformanceReviewPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:PerformanceReview');
    }

    public function view(AuthUser $authUser, PerformanceReview $performanceReview): bool
    {
        return $authUser->can('View:PerformanceReview');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:PerformanceReview');
    }

    public function update(AuthUser $authUser, PerformanceReview $performanceReview): bool
    {
        return $authUser->can('Update:PerformanceReview');
    }

    public function delete(AuthUser $authUser, PerformanceReview $performanceReview): bool
    {
        return $authUser->can('Delete:PerformanceReview');
    }

}