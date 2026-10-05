<?php

namespace App\Controller;

use App\Entity\SiteActivity;
use App\Enum\ActivityStatus;
use App\Form\ActivityCancelType;
use App\Security\Voter\SiteActivityVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Detail of a site activity, for planners and the people assigned to it,
 * and the status changes they can make from it while it is scheduled
 * (start it, or cancel it with a reason). Editing is in PlanningController.
 */
#[Route('/activities/{id}', requirements: ['id' => '\d+'])]
class ActivityController extends AbstractController
{
    #[Route('', name: 'app_activity_show', methods: ['GET'])]
    public function show(SiteActivity $activity): Response
    {
        $this->denyAccessUnlessGranted(SiteActivityVoter::VIEW, $activity);

        return $this->renderDetail($activity, $this->createCancelForm($activity));
    }

    #[Route('/start', name: 'app_activity_start', methods: ['POST'])]
    public function start(Request $request, SiteActivity $activity, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SiteActivityVoter::START, $activity);

        if ($this->isCsrfTokenValid('start-activity'.$activity->getId(), $request->getPayload()->getString('_token'))) {
            $activity->setStatus(ActivityStatus::InProgress);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_activity_show', ['id' => $activity->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/cancel', name: 'app_activity_cancel', methods: ['POST'])]
    public function cancel(Request $request, SiteActivity $activity, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SiteActivityVoter::CANCEL, $activity);

        $form = $this->createCancelForm($activity);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            // 422 with the dialog open showing the error
            return $this->renderDetail($activity, $form);
        }

        $activity->setStatus(ActivityStatus::Cancelled);
        $entityManager->flush();

        return $this->redirectToRoute('app_activity_show', ['id' => $activity->getId()], Response::HTTP_SEE_OTHER);
    }

    private function createCancelForm(SiteActivity $activity): FormInterface
    {
        return $this->createForm(ActivityCancelType::class, $activity, [
            'action' => $this->generateUrl('app_activity_cancel', ['id' => $activity->getId()]),
        ]);
    }

    private function renderDetail(SiteActivity $activity, FormInterface $cancelForm): Response
    {
        return $this->render('activity/show.html.twig', [
            'activity' => $activity,
            'cancel_form' => $cancelForm,
        ]);
    }
}
