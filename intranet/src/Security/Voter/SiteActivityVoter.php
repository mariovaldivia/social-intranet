<?php

namespace App\Security\Voter;

use App\Entity\SiteActivity;
use App\Entity\User;
use App\Enum\ActivityStatus;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * VIEW: planners (and admins) and the users assigned to the activity.
 * EDIT: planners only.
 * START / CANCEL: planners and assigned users, only while it is scheduled.
 * COMPLETE / NOT_DONE: planners and assigned users, only while in progress.
 *
 * @extends Voter<string, SiteActivity>
 */
class SiteActivityVoter extends Voter
{
    public const VIEW = 'ACTIVITY_VIEW';
    public const EDIT = 'ACTIVITY_EDIT';
    public const START = 'ACTIVITY_START';
    public const CANCEL = 'ACTIVITY_CANCEL';
    public const COMPLETE = 'ACTIVITY_COMPLETE';
    public const NOT_DONE = 'ACTIVITY_NOT_DONE';

    public function __construct(private AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT, self::START, self::CANCEL, self::COMPLETE, self::NOT_DONE], true) && $subject instanceof SiteActivity;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // ROLE_ADMIN inherits ROLE_PLANNER through the role hierarchy
        $isPlanner = $this->accessDecisionManager->decide($token, ['ROLE_PLANNER']);
        $isAssigned = $subject->getAssignedUsers()->contains($user);

        return match ($attribute) {
            self::EDIT => $isPlanner,
            self::VIEW => $isPlanner || $isAssigned,
            self::START, self::CANCEL => ($isPlanner || $isAssigned) && ActivityStatus::Scheduled === $subject->getStatus(),
            self::COMPLETE, self::NOT_DONE => ($isPlanner || $isAssigned) && ActivityStatus::InProgress === $subject->getStatus(),
        };
    }
}
