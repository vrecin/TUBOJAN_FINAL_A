<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[IsGranted('ROLE_ADMIN')]
final class ChangeAdminPasswordController extends AbstractController
{
    #[Route('/change/admin/password', name: 'app_change_admin_password')]
    public function index(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }
        if ($this->isGranted('ROLE_STAFF') && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'You do not have access to the admin dashboard.');
            return $this->redirectToRoute('app_product_index');
        }
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $currentPassword = $form->get('currentPassword')->getData();
            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('app_change_admin_password');
            }

            $newPassword = $form->get('newPassword')->getData();
            $hashed = $passwordHasher->hashPassword($user, $newPassword);

            $user->setPassword($hashed);
            $em->flush();

            $this->addFlash('success', 'Password changed successfully!');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('change_admin_password/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
