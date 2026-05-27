<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class ApiRegistrationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationService $emailVerificationService,
        private ValidatorInterface $validator
    ) {}

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // ✅ FIXED: Match FormType - added 'name', changed to 'plainPassword'
        $requiredFields = ['username', 'email', 'name', 'plainPassword'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->json([
                    'success' => false,
                    'message' => "$field is required"
                ], 400);
            }
        }

        // ✅ Match FormType constraints
        if (strlen($data['username']) < 3 || strlen($data['username']) > 180) {
            return $this->json([
                'success' => false,
                'message' => 'Username must be between 3 and 180 characters'
            ], 400);
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid email address'
            ], 400);
        }

        // ✅ Match FormType: plainPassword, min 6
        if (strlen($data['plainPassword']) < 6) {
            return $this->json([
                'success' => false,
                'message' => 'Your password should be at least 6 characters'
            ], 400);
        }

        // Check duplicates
        $existingUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => $data['username']]);

        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Username already exists'
            ], 409);
        }

        $existingEmail = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $data['email']]);

        if ($existingEmail) {
            return $this->json([
                'success' => false,
                'message' => 'Email already registered'
            ], 409);
        }

        // ✅ FIXED: Create user with name
        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setName($data['name']);  // Now included!

        // ✅ FIXED: Use plainPassword
        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['plainPassword']);
        $user->setPassword($hashedPassword);

        $user->setRoles(['ROLE_USER']);

        // Generate verification token
        $verificationToken = $this->emailVerificationService->generateVerificationToken();
        $user->setVerificationToken($verificationToken);
        $user->setIsVerified(false);

        // Validate entity
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errorMessages
            ], 400);
        }

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Generate verification URL
        $verificationUrl = $this->generateUrl(
            'app_verify_email',
            ['token' => $verificationToken],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Send verification email
        try {
            $this->emailVerificationService->sendVerificationEmail($user, $verificationUrl);
        } catch (\Exception $e) {
            // Log error but don't fail registration
        }

        // ✅ FIXED: Include name in response
        return $this->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),  // Now included!
                'isVerified' => $user->isVerified(),
                'roles' => $user->getRoles()
            ]
        ], 201);
    }
}