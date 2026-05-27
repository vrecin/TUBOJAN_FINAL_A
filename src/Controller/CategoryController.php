<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/category')]
final class CategoryController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    private function denyStaff(): ?Response
    {
        $user = $this->getUser();
        if ($user && in_array('ROLE_STAFF', $user->getRoles()) && !in_array('ROLE_ADMIN', $user->getRoles())) {
            $this->addFlash('error', 'You do not have access to the category management.');
            return $this->redirectToRoute('app_product_index');
        }
        return null;
    }

    #[Route('/{id}/show', name: 'app_category_show', methods: ['GET'])]
public function show(Category $category): Response
{
    return $this->render('category/show.html.twig', [
        'category' => $category,
    ]);
}

    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(Request $request, CategoryRepository $categoryRepository): Response
    {
        if ($resp = $this->denyStaff()) {
            return $resp;
        }
             $search = $request->query->get('search');

            if ($search) {
                $categories = $categoryRepository->createQueryBuilder('c')
                    ->where('c.name LIKE :search')
                    ->setParameter('search', '%'.$search.'%')
                    ->getQuery()
                    ->getResult();
            } else {
                $categories = $categoryRepository->findAll();
            }

        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($resp = $this->denyStaff()) {
            return $resp;
        }

        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $category->setCreatedBy($this->getUser());
            $entityManager->persist($category);
            $entityManager->flush();

            $this->logger->log('Create', 'Category created: '.$category->getName().' (ID: '.$category->getId().')');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($resp = $this->denyStaff()) {
            return $resp;
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->logger->log('Update', 'Category updated: '.$category->getName().' (ID: '.$category->getId().')');

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($resp = $this->denyStaff()) {
            return $resp;
        }

        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();

            $this->logger->log('Delete', 'Category deleted: '.$category->getName().' (ID: '.$category->getId().')');
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
