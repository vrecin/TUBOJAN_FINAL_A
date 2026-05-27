<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {
        $form = $this->createForm(ContactType::class);

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/contact/submit', name: 'app_contact_submit', methods: ['POST'])]
    public function submit(Request $request, MailerInterface $mailer): JsonResponse
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted()) {
            return $this->json([
                'success' => false,
                'message' => 'Form not submitted'
            ], 400);
        }

        if (!$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true, true) as $error) {
                $origin = $error->getOrigin();
                $fieldName = $origin ? $origin->getName() : 'global';
                $errors[] = [
                    'field' => $fieldName,
                    'message' => $error->getMessage()
                ];
            }

            return $this->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors
            ], 400);
        }

        $data = $form->getData();
        $fullName = trim($data['name'] . ' ' . ($data['lastname'] ?? ''));

        try {
            $email = (new Email())
                ->from(new Address('leatubojan16@gmail.com', 'Moodura Contact Form'))
                ->to(new Address('leatubojan16@gmail.com'))
                ->replyTo(new Address($data['email'], $fullName))
                ->subject('New Contact Form Submission from Moodura')
                ->html($this->renderView('emails/contact_notification.html.twig', [
                    'name' => $fullName,
                    'email' => $data['email'],
                    'message' => $data['message'],
                ]));

            $mailer->send($email);

            return $this->json([
                'success' => true,
                'message' => 'Message sent successfully!'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to send: ' . $e->getMessage()
            ], 500);
        }
    }
}
