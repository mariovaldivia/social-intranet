<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use App\Form\PostType;

class TimelineController extends AbstractController
{
    #[Route('/', name: 'app_timeline')]
    public function index(EntityManagerInterface $entityManager, PostRepository $postRepository): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post, [
            'action' => $this->generateUrl('app_post_new'),
            'method' => 'POST',
        ]);
        return $this->render('timeline/index.html.twig', [
            'posts' => $postRepository->findAll(),
            'form' => $form
        ]);
    }

    #[Route('/new', name: 'app_post_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setUser($this->getUser());
            $entityManager->persist($post);
            $entityManager->flush();

            return $this->redirectToRoute('app_timeline', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/wall/{username}', name: 'app_wall')]
    public function wall(EntityManagerInterface $entityManager, string $username, UserRepository $userRepository): Response
    {
        return $this->render('timeline/wall.html.twig', [
            'user' => $userRepository->findOneByUsername($username)
        ]);
    }
}
