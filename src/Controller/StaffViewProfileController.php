<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_STAFF')]
final class StaffViewProfileController extends AbstractController
{
    #[Route('/staff/view/profile', name: 'app_staff_view_profile')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_STAFF')) {
            return $this->redirectToRoute('app_product_index');
        }
        return $this->render('staff_view_profile/index.html.twig', [
            'controller_name' => 'StaffViewProfileController',
        ]);
    }
}
