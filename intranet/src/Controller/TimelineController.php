<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Like;
use App\Entity\Post;
use App\Form\PostType;
use App\Repository\LikeRepository;
use App\Repository\PostRepository;
use App\Repository\UserRepository;

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
            'form' => $form,
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
    public function wall(EntityManagerInterface $entityManager, string $username, UserRepository $userRepository, PostRepository $postRepository): Response
    {
        $user = $userRepository->findOneByUsername($username);
        return $this->render('timeline/wall.html.twig', [
            'user' => $user,
            'posts' => $postRepository->findByUser($user)
        ]);
    }

    #[Route('/post/{id}/like', name: 'like_post')]
    public function likePost(EntityManagerInterface $entityManager, Post $post, LikeRepository $likeRepository): Response
    {
        $user = $this->getUser();
        $previousLike = $likeRepository->findOneBy([
            'post' => $post,
            'user' => $user
        ]);
        if($previousLike){
            return $this->redirectToRoute('app_timeline', [], Response::HTTP_SEE_OTHER);
        }
        $like = new Like();
        $like->setUser($user);
        $like->setPost($post);
        $entityManager->persist($like);
        $entityManager->flush();
        return $this->redirectToRoute('app_timeline', [], Response::HTTP_SEE_OTHER);
    }
}
