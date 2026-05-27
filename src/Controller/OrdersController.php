<?php
// src/Controller/OrdersController.php
namespace App\Controller;

use App\Entity\Orders;
use App\Form\OrdersType;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
final class OrdersController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Route(name: 'app_orders_index', methods: ['GET'])]
     // Both admin and staff can view
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('orders/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_orders_new', methods: ['GET', 'POST'])]

    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Orders();
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUser($this->getUser());
            $order->setStatus(Orders::STATUS_PENDING);

            foreach ($order->getItems() as $item) {
                if ($item->getProduct()) {
                    $item->setPrice($item->getProduct()->getPrice());
                    $item->setOrder($order);
                }
            }

            $entityManager->persist($order);
            $entityManager->flush();

            $this->logger->log(
                'Create',
                'Order created: #'.$order->getId().' | Items: '.$order->getItems()->count()
            );

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_orders_show', methods: ['GET'])]

    public function show(Orders $order): Response
    {
        return $this->render('orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_orders_edit', methods: ['GET', 'POST'])]

    public function edit(Request $request, Orders $order, EntityManagerInterface $entityManager): Response
    {
        $oldStatus = $order->getStatus();

        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->logger->log('Update', 'Order updated: #'.$order->getId());

            if ($oldStatus !== $order->getStatus()) {
                $this->logger->log(
                    'Status Change',
                    'Order #'.$order->getId().' status changed from "'.$oldStatus.'" to "'.$order->getStatus().'"'
                );
            }

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_orders_approve', methods: ['POST'])]

    public function approve(Orders $order, EntityManagerInterface $entityManager): Response
    {
        if ($order->getStatus() !== Orders::STATUS_PENDING) {
            $this->addFlash('error', 'Only pending orders can be approved.');
            return $this->redirectToRoute('app_orders_index');
        }

        $order->setStatus(Orders::STATUS_CONFIRMED);
        $order->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        $this->logger->log('Approve', 'Order #'.$order->getId().' approved by '.$this->getUser()->getUsername());

        $this->addFlash('success', 'Order #'.$order->getId().' has been approved.');

        return $this->redirectToRoute('app_orders_index');
    }

    #[Route('/{id}/complete', name: 'app_orders_complete', methods: ['POST'])]

    public function complete(Orders $order, EntityManagerInterface $entityManager): Response
    {
        if ($order->getStatus() !== Orders::STATUS_CONFIRMED) {
            $this->addFlash('error', 'Only approved orders can be completed.');
            return $this->redirectToRoute('app_orders_index');
        }

        $order->setStatus(Orders::STATUS_COMPLETED);
        $order->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        $this->logger->log('Complete', 'Order #'.$order->getId().' marked as completed');

        $this->addFlash('success', 'Order #'.$order->getId().' has been completed.');

        return $this->redirectToRoute('app_orders_index');
    }

    #[Route('/{id}/cancel', name: 'app_orders_cancel', methods: ['POST'])]
  
    public function cancel(Orders $order, EntityManagerInterface $entityManager): Response
    {
        if ($order->getStatus() === Orders::STATUS_COMPLETED) {
            $this->addFlash('error', 'Completed orders cannot be cancelled.');
            return $this->redirectToRoute('app_orders_index');
        }

        $order->setStatus(Orders::STATUS_CANCELLED);
        $order->setUpdatedAt(new \DateTime());
        $entityManager->flush();

        $this->logger->log('Cancel', 'Order #'.$order->getId().' cancelled by '.$this->getUser()->getUsername());

        $this->addFlash('success', 'Order #'.$order->getId().' has been cancelled.');

        return $this->redirectToRoute('app_orders_index');
    }

    #[Route('/{id}', name: 'app_orders_delete', methods: ['POST'])]
  
    public function delete(Request $request, Orders $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {
            $orderId = $order->getId();
            $itemCount = $order->getItems()->count();

            $entityManager->remove($order);
            $entityManager->flush();

            $this->logger->log('Delete', 'Order deleted: #'.$orderId.' | Items: '.$itemCount);
        }

        return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
    }
}