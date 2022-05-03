<?php
// src/Controller/NewsController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Security;

use App\Entity\News;
use App\Form\NewsType;

/**
 * @Route("/")
 */
class NewsController extends AbstractController
{
    public function __construct(Security $security)
    {
       $this->security = $security;
    }

    /**
     * @Route("/", name="home")
     * @Route("/order/{type}", name="home_ordered")
     */
    public function home($type = null): Response
    {
        if ($type == 'likes') {
            $news = $this->getDoctrine()
                ->getRepository(News::class)
                ->findSortedByLike();
        } else {
            $news = $this->getDoctrine()
                ->getRepository(News::class)
                ->findBy(array(), array('created_at' => 'DESC'));
        }

        return $this->render('news/list.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * @Route("/mynews", name="my_news")
     * @Route("/mynews/order/{type}", name="my_news_ordered")
     */
    public function myNews($type = null): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('home');
        }

        if ($type == 'likes') {
            $news = $this->getDoctrine()
            ->getRepository(News::class)
            ->findByUserSortedByLike($user->getId());
        } else {
            $news = $this->getDoctrine()
            ->getRepository(News::class)
            ->findBy(array('creator' => $user->getId()), array('created_at' => 'DESC'));
        }

        return $this->render('news/owned.html.twig', [
            'news' => $news
        ]);
    }

    /**
     * @Route("/{newId}/show", name="show_news")
     */
    public function show($newId): Response
    {
        $new = $this->getDoctrine()
            ->getRepository(News::class)
            ->find($newId);
        
        return $this->render('news/show.html.twig', [
            'new' => $new
        ]);
    }

    /**
     * @Route("/create", name="create_news")
     */
    public function create(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user) {
            return $this->redirectToRoute('home');
        }

        $new = new News();
        $new->setCreator($user);

        $form = $this->createForm(NewsType::class, $new);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $new = $form->getData();
            $new->updatedTimestamps();

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($new);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->renderForm('news/create.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{newId}/edit", name="edit_news")
     */
    public function update($newId, Request $request): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $new = $entityManager->getRepository(News::class)->find($newId);

        if (!$this->verifyUser($new)) {
            return $this->redirectToRoute('home');
        }

        if (!$new) {
            throw $this->createNotFoundException(
                'No news item found for id '.$id
            );
        }
        
        $form = $this->createForm(NewsType::class, $new);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $new = $form->getData();
            $new->updatedTimestamps();
            
            $entityManager->persist($new);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->renderForm('news/edit.html.twig', [
            'form' => $form,
            'new' => $new,
        ]);
    }

    /**
     * @Route("/{newId}/remove", name="remove_news")
     */
    public function remove($newId): Response
    {
        $new = $this->getDoctrine()
            ->getRepository(News::class)
            ->find($newId);

        if ($this->verifyUser($new)) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($new);
            $entityManager->flush();
        }
        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/{newId}/like", name="add_like")
     */
    public function like($newId): Response
    {

        $entityManager = $this->getDoctrine()->getManager();

        $new = $this->getDoctrine()
            ->getRepository(News::class)
            ->find($newId);
            
        $user = $this->security->getUser();

        if (!$user) return $this->redirectToRoute('home');

        if ($new->getLikes()->contains($user)) {
            $new->removeLike($user);
        } else {
            $new->addLike($user);
        }
        $entityManager->persist($new);
        $entityManager->flush();

        return $this->redirectToRoute('show_news', ['newId' => $new->getId()]);
    }

    public function verifyUser($new): bool
    {
        return $new->getCreator() == $this->security->getUser();
    }

}