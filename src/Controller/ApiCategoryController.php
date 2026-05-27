<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/categories')]
final class ApiCategoryController extends AbstractController
{
    #[Route('', name: 'api_categories_list', methods: ['GET'])]
    public function list(
        Request $request,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $search = $request->query->get('search');
        $limit = (int) ($request->query->get('limit') ?? 50);
        $offset = (int) ($request->query->get('offset') ?? 0);

        // --- Build base query for COUNT ---
        $countQb = $categoryRepository->createQueryBuilder('c');
        
        if ($search) {
            $countQb->andWhere('c.name LIKE :search')
                    ->setParameter('search', '%' . $search . '%');
        }
        
        $total = (int) $countQb->select('COUNT(c.id)')
                               ->getQuery()
                               ->getSingleScalarResult();

        // --- Build FRESH query for DATA ---
        $dataQb = $categoryRepository->createQueryBuilder('c');
        
        if ($search) {
            $dataQb->andWhere('c.name LIKE :search')
                   ->setParameter('search', '%' . $search . '%');
        }
        
        $categories = $dataQb->setFirstResult($offset)
                             ->setMaxResults($limit)
                             ->orderBy('c.createdAt', 'DESC')
                             ->getQuery()
                             ->getResult();

        $data = [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'items' => array_map(function (Category $category) {
                return [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'createdBy' => [
                        'id' => $category->getCreatedBy()?->getId(),
                        'username' => $category->getCreatedBy()?->getUsername(),
                    ],
                    'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
                ];
            }, $categories),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_category_show', methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        $data = [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'createdBy' => [
                'id' => $category->getCreatedBy()?->getId(),
                'username' => $category->getCreatedBy()?->getUsername(),
            ],
            'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('', name: 'api_category_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName($data['name']);
        $category->setCreatedBy($this->getUser());

        $em->persist($category);
        $em->flush();

        return new JsonResponse([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'createdBy' => [
                'id' => $category->getCreatedBy()?->getId(),
                'username' => $category->getCreatedBy()?->getUsername(),
            ],
            'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_category_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Category $category,
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        if (isset($data['name']) && !empty($data['name'])) {
            $category->setName($data['name']);
        }

        $em->flush();

        return new JsonResponse([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'createdBy' => [
                'id' => $category->getCreatedBy()?->getId(),
                'username' => $category->getCreatedBy()?->getUsername(),
            ],
            'createdAt' => $category->getCreatedAt()?->format('Y-m-d H:i:s'),
        ], Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_category_delete', methods: ['DELETE'])]
    public function delete(
        Category $category,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $em->remove($category);
        $em->flush();

        return new JsonResponse(['message' => 'Category deleted successfully'], Response::HTTP_OK);
    }
}