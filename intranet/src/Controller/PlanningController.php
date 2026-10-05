<?php

namespace App\Controller;

use App\Entity\SiteActivity;
use App\Form\SiteActivityType;
use App\Repository\CompanyRepository;
use App\Repository\SiteActivityRepository;
use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Clock\ClockInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Planning of site activities on a monthly calendar (outside EasyAdmin).
 * Requires ROLE_PLANNER (admins have it through the role hierarchy).
 */
#[Route('/planning')]
#[IsGranted('ROLE_PLANNER')]
class PlanningController extends AbstractController
{
    #[Route('/', name: 'app_planning', methods: ['GET'])]
    public function index(
        Request $request,
        ClockInterface $clock,
        SiteActivityRepository $activityRepository,
        CompanyRepository $companyRepository,
        SiteRepository $siteRepository,
        UserRepository $userRepository,
    ): Response {
        $today = $clock->now();
        // ?month=2026-10; anything else falls back to the current month
        $month = \DateTimeImmutable::createFromFormat('!Y-m', (string) $request->query->get('month'), $today->getTimezone())
            ?: $today->modify('first day of this month')->setTime(0, 0);

        // Whole weeks, Monday to Sunday, covering the month
        $gridStart = $month->modify('monday this week');
        $gridEnd = $month->modify('last day of this month')->modify('sunday this week');

        $filters = [
            'company' => $this->find($companyRepository, $request->query->get('company')),
            'site' => $this->find($siteRepository, $request->query->get('site')),
            'user' => $this->find($userRepository, $request->query->get('user')),
        ];

        $byDay = [];
        foreach ($activityRepository->findForPlanning($gridStart, $gridEnd, ...array_values($filters)) as $activity) {
            $byDay[$activity->getDate()->format('Y-m-d')][] = $activity;
        }

        $days = [];
        for ($day = $gridStart; $day <= $gridEnd; $day = $day->modify('+1 day')) {
            $days[] = $day;
        }

        return $this->render('planning/index.html.twig', [
            'month' => $month,
            'previous_month' => $month->modify('-1 month')->format('Y-m'),
            'next_month' => $month->modify('+1 month')->format('Y-m'),
            'today' => $today->format('Y-m-d'),
            'weeks' => array_chunk($days, 7),
            'activities_by_day' => $byDay,
            'filters' => $filters,
            'companies' => $companyRepository->findBy([], ['legalName' => 'ASC']),
            'sites' => $siteRepository->createQueryBuilder('s')->innerJoin('s.company', 'c')->addSelect('c')
                ->orderBy('c.legalName')->addOrderBy('s.name')->getQuery()->getResult(),
            'users' => $userRepository->createQueryBuilder('u')->leftJoin('u.profile', 'p')->addSelect('p')
                ->orderBy('p.name')->addOrderBy('u.email')->getQuery()->getResult(),
        ]);
    }

    #[Route('/new', name: 'app_planning_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ClockInterface $clock, SiteRepository $siteRepository): Response
    {
        $activity = new SiteActivity();
        // Prefilled from the calendar: ?date=2026-10-20 (the "+" of a day) and ?site=
        $date = \DateTime::createFromFormat('!Y-m-d', (string) $request->query->get('date'));
        $activity->setDate($date ?: \DateTime::createFromInterface($clock->now()));
        if ($site = $this->find($siteRepository, $request->query->get('site'))) {
            $activity->setSite($site);
        }

        $form = $this->createForm(SiteActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activity->setCreatedBy($this->getUser());
            $entityManager->persist($activity);
            $entityManager->flush();

            return $this->redirectToMonth($activity);
        }

        return $this->render('planning/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_planning_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, SiteActivity $activity, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SiteActivityType::class, $activity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // Back to the detail page the planner came from
            return $this->redirectToRoute('app_activity_show', ['id' => $activity->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('planning/edit.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_planning_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, SiteActivity $activity, EntityManagerInterface $entityManager): Response
    {
        $response = $this->redirectToMonth($activity);

        if ($this->isCsrfTokenValid('delete-activity'.$activity->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($activity);
            $entityManager->flush();
        }

        return $response;
    }

    private function redirectToMonth(SiteActivity $activity): Response
    {
        return $this->redirectToRoute('app_planning', ['month' => $activity->getDate()->format('Y-m')], Response::HTTP_SEE_OTHER);
    }

    /**
     * @template T of object
     * @param \Doctrine\Persistence\ObjectRepository<T> $repository
     * @return T|null
     */
    private function find(\Doctrine\Persistence\ObjectRepository $repository, mixed $id): ?object
    {
        return ctype_digit((string) $id) ? $repository->find((int) $id) : null;
    }
}
