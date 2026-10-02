<?php

namespace App\Controller;

use App\Entity\Photo;
use App\Entity\PhotoAlbum;
use App\Form\PhotoAlbumType;
use App\Form\PhotoUploadType;
use App\Repository\PhotoAlbumRepository;
use App\Security\Voter\GalleryVoter;
use App\Services\TimelineService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/gallery')]
class GalleryController extends AbstractController
{
    #[Route('/', name: 'app_gallery_index', methods: ['GET'])]
    public function index(PhotoAlbumRepository $albumRepository): Response
    {
        return $this->render('gallery/index.html.twig', [
            'albums' => $albumRepository->findForGallery(),
        ]);
    }

    #[Route('/new', name: 'app_gallery_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $album = new PhotoAlbum();
        $form = $this->createForm(PhotoAlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $album->setCreatedBy($this->getUser());
            $entityManager->persist($album);
            $entityManager->flush();

            return $this->redirectToRoute('app_gallery_show', ['id' => $album->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('gallery/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_gallery_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(PhotoAlbum $album): Response
    {
        return $this->renderAlbum($album, $this->createUploadForm($album));
    }

    #[Route('/{id}/photos', name: 'app_gallery_upload', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function upload(Request $request, PhotoAlbum $album, EntityManagerInterface $entityManager, TimelineService $timeline): Response
    {
        $form = $this->createUploadForm($album);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            // Renders with status 422 so the errors are shown in place
            return $this->renderAlbum($album, $form);
        }

        $photos = [];
        foreach ($form->get('photos')->getData() as $file) {
            $photo = new Photo();
            $photo->setImageFile($file);
            $photo->setUploadedBy($this->getUser());
            $album->addPhoto($photo);
            $entityManager->persist($photo);
            $photos[] = $photo;
        }
        // Each upload is announced on the timeline with its photos
        $timeline->addPhotos($album, $photos, $this->getUser());
        $entityManager->flush();

        return $this->redirectToRoute('app_gallery_show', ['id' => $album->getId()], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/delete', name: 'app_gallery_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, PhotoAlbum $album, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(GalleryVoter::DELETE, $album);

        if ($this->isCsrfTokenValid('delete-album'.$album->getId(), $request->getPayload()->getString('_token'))) {
            // Photos and their timeline posts are removed in cascade, and
            // VichUploader deletes the files
            $entityManager->remove($album);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_gallery_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/photo/{id}/delete', name: 'app_gallery_photo_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deletePhoto(Request $request, Photo $photo, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(GalleryVoter::DELETE, $photo);
        $album = $photo->getAlbum();

        if ($this->isCsrfTokenValid('delete-photo'.$photo->getId(), $request->getPayload()->getString('_token'))) {
            $album->removePhoto($photo);
            $entityManager->remove($photo);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_gallery_show', ['id' => $album->getId()], Response::HTTP_SEE_OTHER);
    }

    private function createUploadForm(PhotoAlbum $album): FormInterface
    {
        return $this->createForm(PhotoUploadType::class, null, [
            'action' => $this->generateUrl('app_gallery_upload', ['id' => $album->getId()]),
        ]);
    }

    private function renderAlbum(PhotoAlbum $album, FormInterface $uploadForm): Response
    {
        return $this->render('gallery/show.html.twig', [
            'album' => $album,
            'upload_form' => $uploadForm,
        ]);
    }
}
