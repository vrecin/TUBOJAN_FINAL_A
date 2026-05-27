<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products')]
final class ApiProductController extends AbstractController
{
   #[Route('', name: 'api_products_list', methods: ['GET'])]
public function list(
    Request $request,
    ProductRepository $productRepository,
    SerializerInterface $serializer
): JsonResponse {
    $search = $request->query->get('search');
    $minPrice = $request->query->get('min_price');
    $maxPrice = $request->query->get('max_price');
    $categoryId = $request->query->get('category');
    $limit = (int) ($request->query->get('limit') ?? 50);
    $offset = (int) ($request->query->get('offset') ?? 0);

    // --- Build base query for COUNT ---
    $countQb = $productRepository->createQueryBuilder('p');

    if ($search) {
        $countQb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
    }

    if ($minPrice !== null && $minPrice !== '') {
        $countQb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', (float) $minPrice);
    }

    if ($maxPrice !== null && $maxPrice !== '') {
        $countQb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', (float) $maxPrice);
    }

    if ($categoryId) {
        $countQb->andWhere('p.category = :category')
                ->setParameter('category', $categoryId);
    }

    $total = (int) $countQb->select('COUNT(p.id)')
                           ->getQuery()
                           ->getSingleScalarResult();

    // --- Build FRESH query for DATA ---
    $dataQb = $productRepository->createQueryBuilder('p');

    if ($search) {
        $dataQb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
    }

    if ($minPrice !== null && $minPrice !== '') {
        $dataQb->andWhere('p.price >= :minPrice')
                ->setParameter('minPrice', (float) $minPrice);
    }

    if ($maxPrice !== null && $maxPrice !== '') {
        $dataQb->andWhere('p.price <= :maxPrice')
                ->setParameter('maxPrice', (float) $maxPrice);
    }

    if ($categoryId) {
        $dataQb->andWhere('p.category = :category')
                ->setParameter('category', $categoryId);
    }

    $products = $dataQb->setFirstResult($offset)
                         ->setMaxResults($limit)
                         ->orderBy('p.createdAt', 'DESC')
                         ->getQuery()
                         ->getResult();

    $data = [
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset,
        'items' => array_map(function (Product $product) {
            return [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'image' => $product->getImage(),
                'category' => [
                    'id' => $product->getCategory()?->getId(),
                    'name' => $product->getCategory()?->getName(),
                ],
                'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
                'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
            ];
        }, $products),
    ];

    return new JsonResponse($data, Response::HTTP_OK);
}

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'image' => $product->getImage(),
            'category' => [
                'id' => $product->getCategory()?->getId(),
                'name' => $product->getCategory()?->getName(),
            ],
            'createdBy' => [
                'id' => $product->getCreatedBy()?->getId(),
                'username' => $product->getCreatedBy()?->getUsername(),
            ],
            'createdAt' => $product->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        ValidatorInterface $validator
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Validate required fields
        $errors = [];
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        if (empty($data['price'])) {
            $errors['price'] = 'Price is required';
        }
        if (empty($data['category_id'])) {
            $errors['category_id'] = 'Category ID is required';
        }

        if (!empty($errors)) {
            return new JsonResponse(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryRepository->find($data['category_id']);
        if (!$category) {
            return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description'] ?? null);
        $product->setPrice((float) $data['price']);
        $product->setCategory($category);
        $product->setCreatedBy($this->getUser());
        $product->setCreatedAt(new \DateTime());

        $em->persist($product);
        $em->flush();

        $responseData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'category' => [
                'id' => $product->getCategory()?->getId(),
                'name' => $product->getCategory()?->getName(),
            ],
            'createdAt' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($responseData, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT', 'PATCH'])]
    public function update(
        Product $product,
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Restrict staff to own products
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        // Update fields if provided
        if (isset($data['name'])) {
            $product->setName($data['name']);
        }
        if (isset($data['description'])) {
            $product->setDescription($data['description']);
        }
        if (isset($data['price'])) {
            $product->setPrice((float) $data['price']);
        }
        if (isset($data['category_id'])) {
            $category = $categoryRepository->find($data['category_id']);
            if (!$category) {
                return new JsonResponse(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
            }
            $product->setCategory($category);
        }

        $product->setUpdatedAt(new \DateTime());
        $em->flush();

        $responseData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'price' => $product->getPrice(),
            'category' => [
                'id' => $product->getCategory()?->getId(),
                'name' => $product->getCategory()?->getName(),
            ],
            'updatedAt' => $product->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(
        Product $product,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_STAFF');

        // Restrict staff to own products
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN') && $product->getCreatedBy() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_FORBIDDEN);
        }

        $em->remove($product);
        $em->flush();

        return new JsonResponse(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
