<?php

namespace App\Controller;

use App\Entity\Cart;
use App\Entity\CartItem;
use App\Repository\CartRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cart')]
class ApiCartController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function getCart(CartRepository $cartRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();

        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            return new JsonResponse([
                'items' => [],
                'total' => 0
            ]);
        }

        $items = [];
        $total = 0;

        foreach ($cart->getCartItems() as $item) {
            $product = $item->getProduct();
            $subtotal = $product->getPrice() * $item->getQuantity();

            $items[] = [
                'id' => $item->getId(),
                'product' => [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'price' => $product->getPrice(),
                    'image' => $product->getImage(),
                ],
                'quantity' => $item->getQuantity(),
                'subtotal' => $subtotal,
            ];

            $total += $subtotal;
        }

        return new JsonResponse([
            'items' => $items,
            'total' => $total
        ]);
    }

    #[Route('/add', methods: ['POST'])]
    public function addToCart(
        Request $request,
        EntityManagerInterface $em,
        ProductRepository $productRepository,
        CartRepository $cartRepository
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $data = json_decode($request->getContent(), true);

        $product = $productRepository->find($data['product_id'] ?? null);

        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        $user = $this->getUser();

        // get or create cart
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $cart = new Cart();
            $cart->setUser($user);
            $em->persist($cart);
        }

        // check if item exists
        $existingItem = null;

        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $existingItem = $item;
                break;
            }
        }

        if ($existingItem) {
            $existingItem->setQuantity(
                $existingItem->getQuantity() + ($data['quantity'] ?? 1)
            );
        } else {
            $item = new CartItem();
            $item->setCart($cart);
            $item->setProduct($product);
            $item->setQuantity($data['quantity'] ?? 1);

            $em->persist($item);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Added to cart successfully'
        ]);
    }

    #[Route('/item/{id}', methods: ['DELETE'])]
    public function removeItem(
        int $id,
        EntityManagerInterface $em
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $item = $em->getRepository(CartItem::class)->find($id);

        if (!$item) {
            return new JsonResponse(['error' => 'Item not found'], 404);
        }

        $em->remove($item);
        $em->flush();

        return new JsonResponse([
            'message' => 'Item removed'
        ]);
    }

    #[Route('/item/{id}', methods: ['PUT'])]
public function updateItem(
    int $id,
    Request $request,
    EntityManagerInterface $em
): JsonResponse {

    $this->denyAccessUnlessGranted('ROLE_USER');

    $item = $em->getRepository(CartItem::class)->find($id);

    if (!$item) {
        return new JsonResponse(['error' => 'Item not found'], 404);
    }

    // Verify the item belongs to the current user's cart
    if ($item->getCart()->getUser() !== $this->getUser()) {
        return new JsonResponse(['error' => 'Unauthorized'], 403);
    }

    $data = json_decode($request->getContent(), true);
    $quantity = $data['quantity'] ?? 1;

    if ($quantity < 1) {
        return new JsonResponse(['error' => 'Quantity must be at least 1'], 400);
    }

    $item->setQuantity($quantity);
    $em->flush();

    return new JsonResponse([
        'message' => 'Quantity updated',
        'quantity' => $item->getQuantity(),
        'subtotal' => $item->getProduct()->getPrice() * $item->getQuantity(),
    ]);
}

    #[Route('/clear', methods: ['DELETE'])]
    public function clearCart(
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): JsonResponse {

        $this->denyAccessUnlessGranted('ROLE_USER');

        $cart = $cartRepository->findOneBy(['user' => $this->getUser()]);

        if ($cart) {
            foreach ($cart->getCartItems() as $item) {
                $em->remove($item);
            }

            $em->flush();
        }

        return new JsonResponse([
            'message' => 'Cart cleared'
        ]);
    }
}