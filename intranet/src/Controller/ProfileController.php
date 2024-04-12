<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\Profile;
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\ProfileRepository;

#[Route('/profile')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_profile_index', methods: ['GET'])]
    public function index(ProfileRepository $profileRepository): Response
    {
        return $this->render('profile/index.html.twig', [
            'profiles' => $profileRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $profile = new Profile();
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profile->setCreatedBy($this->getUser());
            $entityManager->persist($profile);
            $user = new User();
            $user->setEmail($profile->getEmail());
            $user->setUsername($profile->generateUsername());
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $profile->getIdentification()
            );
            $user->setPassword($hashedPassword);
            $user->setProfile($profile);

            $entityManager->persist($user);
            $entityManager->flush();
            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/new.html.twig', [
            'profile' => $profile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_show', methods: ['GET'])]
    public function show(Profile $profile): Response
    {
        return $this->render('profile/show.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Profile $profile, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfileType::class, $profile);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('profile/edit.html.twig', [
            'profile' => $profile,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_profile_delete', methods: ['POST'])]
    public function delete(Request $request, Profile $profile, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$profile->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($profile);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_profile_index', [], Response::HTTP_SEE_OTHER);
    }
}
