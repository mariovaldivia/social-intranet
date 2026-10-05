<?php

namespace App\Controller;

use App\Repository\SiteActivityRepository;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Site activities assigned to the logged-in user (outside EasyAdmin).
 */
#[Route('/my-activities')]
class MyActivitiesController extends AbstractController
{
    #[Route('/', name: 'app_my_activities', methods: ['GET'])]
    public function index(SiteActivityRepository $activityRepository, ClockInterface $clock): Response
    {
        $today = $clock->now();

        return $this->render('my_activities/index.html.twig', [
            'overdue' => $activityRepository->findOverdueForUser($this->getUser(), $today),
            'upcoming' => $activityRepository->findUpcomingForUser($this->getUser(), $today),
        ]);
    }

    #[Route('/history', name: 'app_my_activities_history', methods: ['GET'])]
    public function history(SiteActivityRepository $activityRepository, ClockInterface $clock): Response
    {
        return $this->render('my_activities/history.html.twig', [
            'activities' => $activityRepository->findPastForUser($this->getUser(), $clock->now()),
        ]);
    }
}
