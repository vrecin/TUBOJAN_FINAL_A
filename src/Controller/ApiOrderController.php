<?php
// src/Controller/ApiOrderController.php
namespace App\Controller;

use App\Entity\Orders;
use App\Entity\OrderItem;
use App\Repository\CartRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class ApiOrderController extends AbstractController
{
    #[Route('/checkout', methods: ['POST'])]
    public function checkout(
        Request $request,
        CartRepository $cartRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $user = $this->getUser();
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart || $cart->getCartItems()->isEmpty()) {
            return new JsonResponse(['error' => 'Cart is empty'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $shippingAddress = $data['shipping_address'] ?? null;

        // Calculate totals
        $subtotal = 0;
        foreach ($cart->getCartItems() as $item) {
            $subtotal += $item->getProduct()->getPrice() * $item->getQuantity();
        }

        $shipping = $subtotal > 50 ? 0 : 5.99;
        $tax = $subtotal * 0.08;
        $total = $subtotal + $shipping + $tax;

        // Create order
        $order = new Orders();
        $order->setUser($user);
        $order->setSubtotal(number_format($subtotal, 2, '.', ''));
        $order->setTax(number_format($tax, 2, '.', ''));
        $order->setShipping(number_format($shipping, 2, '.', ''));
        $order->setTotal(number_format($total, 2, '.', ''));
        $order->setStatus(Orders::STATUS_PENDING);
        $order->setShippingAddress($shippingAddress);

        // Convert cart items to order items
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setPrice(number_format($cartItem->getProduct()->getPrice(), 2, '.', ''));
            $orderItem->setSubtotal(number_format(
                $cartItem->getProduct()->getPrice() * $cartItem->getQuantity(),
                2, '.', ''
            ));
            $order->addItem($orderItem);
            $em->persist($orderItem);
        }

        // Clear cart
        foreach ($cart->getCartItems() as $item) {
            $em->remove($item);
        }

        $em->persist($order);
        $em->flush();

        return new JsonResponse([
            'message' => 'Order placed successfully',
            'order' => [
                'id' => $order->getId(),
                'total' => $order->getTotal(),
                'status' => $order->getStatus(),
                'created_at' => $order->getCreatedAt()->format('c'),
            ],
        ]);
    }

    #[Route('', methods: ['GET'])]
    public function getOrders(OrderRepository $orderRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $orders = $orderRepository->findBy(
            ['user' => $this->getUser()],
            ['createdAt' => 'DESC']
        );

        $data = [];
        foreach ($orders as $order) {
            $items = [];
            foreach ($order->getItems() as $item) {
                $items[] = [
                    'id' => $item->getId(),
                    'product' => [
                        'id' => $item->getProduct()->getId(),
                        'name' => $item->getProduct()->getName(),
                        'image' => $item->getProduct()->getImage(),
                    ],
                    'quantity' => $item->getQuantity(),
                    'price' => $item->getPrice(),
                    'subtotal' => $item->getSubtotal(),
                ];
            }

            $data[] = [
                'id' => $order->getId(),
                'status' => $order->getStatus(),
                'subtotal' => $order->getSubtotal(),
                'shipping' => $order->getShipping(),
                'tax' => $order->getTax(),
                'total' => $order->getTotal(),
                'shipping_address' => $order->getShippingAddress(),
                'created_at' => $order->getCreatedAt()->format('c'),
                'items' => $items,
            ];
        }

        return new JsonResponse(['orders' => $data]);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function getOrder(int $id, OrderRepository $orderRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $order = $orderRepository->find($id);

        if (!$order || $order->getUser() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Order not found'], 404);
        }

        $items = [];
        foreach ($order->getItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'product' => [
                    'id' => $item->getProduct()->getId(),
                    'name' => $item->getProduct()->getName(),
                    'image' => $item->getProduct()->getImage(),
                ],
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
                'subtotal' => $item->getSubtotal(),
            ];
        }

        return new JsonResponse([
            'id' => $order->getId(),
            'status' => $order->getStatus(),
            'subtotal' => $order->getSubtotal(),
            'shipping' => $order->getShipping(),
            'tax' => $order->getTax(),
            'total' => $order->getTotal(),
            'shipping_address' => $order->getShippingAddress(),
            'created_at' => $order->getCreatedAt()->format('c'),
            'items' => $items,
        ]);
    }
}