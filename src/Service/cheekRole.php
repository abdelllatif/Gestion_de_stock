<?php

namespace App\Service;

use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class cheekRole
{
    private Security $security;
    private RouterInterface $router;

    public function __construct(Security $security, RouterInterface $router)
    {
        $this->security = $security;
        $this->router = $router;
    }

    /**
     * Check if the user has a specific role.
     *
     * @param string|null $role
     * @return int|RedirectResponse
     */
    public function hasRole(?string $role = null): int|RedirectResponse
    {
        $user = $this->security->getUser();

        if (!$user) {
            return new RedirectResponse($this->router->generate('app_login'));
        }

        // if user is admin, allow access
        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return 0;
        }

        // Check roles if needed
        if ($role && in_array($role, $user->getRoles())) {
            return 0;
        }

        // Otherwise redirect to dashboard
        return new RedirectResponse($this->router->generate('dashboard'));
    }
}
