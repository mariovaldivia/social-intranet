<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TimelineController extends AbstractController
{
    #[Route('/', name: 'app_timeline')]
    public function index(): Response
    {
        return $this->render('timeline/index.html.twig', [
            'controller_name' => 'TimelineController',
        ]);
    }
}
