<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


final class AdminViewProfileController extends AbstractController
{
    #[Route('/admin/view/profile', name: 'app_admin_view_profile')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have access to the admin dashboard.');
            return $this->redirectToRoute('app_product_index');
        }
        
        return $this->render('admin_view_profile/index.html.twig', [
            'controller_name' => 'AdminViewProfileController',
        ]);
    }
}
