<?php

namespace App\Controller;

use App\Entity\SiteActivity;
use App\Security\Voter\SiteActivityVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Detail of a site activity, for planners and the people assigned to it.
 * Editing is in PlanningController (planners only).
 */
class ActivityController extends AbstractController
{
    #[Route('/activities/{id}', name: 'app_activity_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(SiteActivity $activity): Response
    {
        $this->denyAccessUnlessGranted(SiteActivityVoter::VIEW, $activity);

        return $this->render('activity/show.html.twig', [
            'activity' => $activity,
        ]);
    }
}
