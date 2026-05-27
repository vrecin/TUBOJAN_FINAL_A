<?php

namespace App\Security;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(private Security $security) {}

    public function handle(
        Request $request,
        AccessDeniedException $accessDeniedException
    ): ?Response {
        // STAFF tries to access ADMIN pages
        if ($this->security->isGranted('ROLE_STAFF')) {
            return new RedirectResponse('/product');
        }

        // let Symfony handle others (403 page)
        return null;
    }
}