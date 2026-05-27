<?php

namespace App\Controller;

use App\Repository\ActivityLogRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminDashboardController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        UserRepository $userRepository,
        ProductRepository $productRepository,
        ActivityLogRepository $activityLogRepository,
        CategoryRepository $categoryRepository
    ): Response {

        // Redirect staff to product page if they try to access admin dashboard
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have access to the admin dashboard.');
            return $this->redirectToRoute('app_product_index');
        }

        $totalUsers = $userRepository->count([]);

        $totalStaff = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_STAFF"%')
            ->getQuery()
            ->getSingleScalarResult();

        $totalProducts = $productRepository->count([]);
        $totalCategory = $categoryRepository->count([]);
        $recentLogs = $activityLogRepository->findBy(
            [],
            ['createdAt' => 'DESC'],
            5
        );

        return $this->render('admin_dashboard/index.html.twig', [
            'totalUsers'    => $totalUsers,
            'totalStaff'    => $totalStaff,
            'totalProducts' => $totalProducts,
            'totalCategory' => $totalCategory,
            'recentLogs'    => $recentLogs
        ]);
    }
}
