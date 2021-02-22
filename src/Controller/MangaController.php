<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Entity\Images;
use App\Form\MangaType;
use App\Repository\MangaRepository;
use App\Repository\ImagesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class MangaController extends AbstractController
{

    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @Route("/", name="manga_index", methods={"GET"})
     */
    public function index(ImagesRepository $imagesRepository, MangaRepository $mangaRepository): Response
    {
        return $this->render('manga/index.html.twig', [
            'images' => $imagesRepository->findAll(),
            'mangas' => $mangaRepository->findAll(),
        ]);
    }

    /**
     * @Route("/ajouter-un-manga", name="manga_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $manga = new Manga();
        $form = $this->createForm(MangaType::class, $manga);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                $file = md5(uniqid()) . '.' . $image->guessExtension();
                $image->move(
                    $this->getParameter('images_manga'),
                    $file
                );
                $newImage = new Images();
                $newImage->setName($file);
                $manga->addImage($newImage);
            }

            $this->addFlash(
                "success",
                "Bienvenue dans l'équipe ".$manga->getTitle()." !!"
            );

            $this->manager->persist($manga);
            $this->manager->flush();

            return $this->redirectToRoute('manga_index');
        }

        return $this->render('manga/new.html.twig', [
            'manga' => $manga,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/synopsis/{id}", name="manga_show", methods={"GET"})
     */
    public function show(ImagesRepository $imagesRepository, MangaRepository $mangaRepository, $id): Response
    {
        return $this->render('manga/show.html.twig', [
            'images' => $imagesRepository->find($id),
            'manga' => $mangaRepository->find($id)
        ]);
    }

    /**
     * @Route("/modification/{id}", name="manga_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Manga $manga): Response
    {
        $form = $this->createForm(MangaType::class, $manga);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $images = $form->get('images')->getData();
            foreach ($images as $image) {
                $file = md5(uniqid()).'.'.$image->guessExtension();
                $image->move(
                    $this->getParameter('images_manga'),
                    $file
                );
                $newImage = new Images();
                $newImage->setName($file);
                $manga->addImage($newImage);

            }

            $this->addFlash(
                'update',
                "Le manga " . $manga->getTitle() . " a bien été modifier"
            );

            $this->manager->flush();

            return $this->redirectToRoute('manga_index');
        }

        return $this->render('manga/edit.html.twig', [
            'manga' => $manga,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/suppression/{id}", name="manga_delete", methods={"DELETE"})
     */
    public function delete(Request $request, Manga $manga): Response
    {
        if ($this->isCsrfTokenValid('delete' . $manga->getId(), $request->request->get('_token'))) {
            $this->manager->remove($manga);
            $this->manager->flush();
        }

        return $this->redirectToRoute('manga_index');
    }

    /**
     * @Route("/suppression/image/{id}", name="manga_image_delete", methods={"DELETE"})
     */
    public function deleteOneImage(Images $image, Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($this->isCsrfTokenValid('delete' . $image->getId(), $data['_token'])) {
            $imageName = $image->getName();
            unlink($this->getParameter('images_manga') . '/' . $imageName);

            $this->manager->remove($image);
            $this->manager->flush();

            return new JsonResponse(['success' => 1]);
        } else {
            return new JsonResponse(['error' => 'Token Invalide'], 400);
        }
    }
}
