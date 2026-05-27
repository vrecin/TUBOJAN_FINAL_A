<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class ActivityLogger
{
    private $em;
    private $security;
    private $requestStack;

    public function __construct(
        EntityManagerInterface $em,
        Security $security,
        RequestStack $requestStack
    ) {
        $this->em = $em;
        $this->security = $security;
        $this->requestStack = $requestStack;
    }

    public function log(string $action, ?string $targetData = null): void
    {
        $user = $this->security->getUser();
        $request = $this->requestStack->getCurrentRequest();

        $log = new ActivityLog();
        $log->setAction($action);
        $log->setTargetData($targetData);
        $log->setCreatedAt(new \DateTime());
        $log->setIpAddress($request?->getClientIp());

        if ($user) {
            $log->setUser($user);
            $log->setUsername($user->getUserIdentifier());
            $log->setRole(implode(', ', $user->getRoles()));
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}