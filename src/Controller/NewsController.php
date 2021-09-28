<?php
// src/Controller/NewsController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\News;
use App\Form\NewsType;

/**
 * @Route("/")
 */
class NewsController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function home(): Response
    {
        $news = $this->getDoctrine()
            ->getRepository(News::class)
            ->findAll();

        return $this->render('news/list.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * @Route("/create", name="create_news")
     */
    public function new(Request $request): Response
    {
        $news = new News();

        $form = $this->createForm(NewsType::class, $news);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($news);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->renderForm('news/create.html.twig', [
            'form' => $form,
        ]);
    }

}