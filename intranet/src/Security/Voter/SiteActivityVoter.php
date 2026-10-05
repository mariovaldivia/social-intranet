<?php

namespace App\Security\Voter;

use App\Entity\SiteActivity;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * VIEW: planners (and admins) and the users assigned to the activity.
 * EDIT: planners only.
 *
 * @extends Voter<string, SiteActivity>
 */
class SiteActivityVoter extends Voter
{
    public const VIEW = 'ACTIVITY_VIEW';
    public const EDIT = 'ACTIVITY_EDIT';

    public function __construct(private AccessDecisionManagerInterface $accessDecisionManager)
    {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW, self::EDIT], true) && $subject instanceof SiteActivity;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        // ROLE_ADMIN inherits ROLE_PLANNER through the role hierarchy
        $isPlanner = $this->accessDecisionManager->decide($token, ['ROLE_PLANNER']);

        return match ($attribute) {
            self::EDIT => $isPlanner,
            self::VIEW => $isPlanner || $subject->getAssignedUsers()->contains($user),
        };
    }
}
