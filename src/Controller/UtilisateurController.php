<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RoleRepository;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;

final class UtilisateurController extends AbstractController
{
    private function getGroupedRoles(RoleRepository $roleRepository): array
    {
        $roles = $roleRepository->findAll();
        $groupedRoles = [];
        foreach ($roles as $role) {
            $group = $role->getRoleGroup() ?: 'Autre';
            $groupedRoles[$group][] = $role;
        }
        return $groupedRoles;
    }

    #[Route('/utilisateur', name: 'app_utilisateur')]
    public function index(RoleRepository $roleRepository, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('utilisateur/index.html.twig', [
            'activeLink' => 'utilisateur',
            'groupedRoles' => $this->getGroupedRoles($roleRepository),
            'users' => $users
        ]);
    }

    #[Route('/utilisateur/new', name: 'app_utilisateur_new', methods: ['POST'])]
    public function addUser(
        Request $request,
        EntityManagerInterface $em,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        UrlGeneratorInterface $urlGenerator,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $prenom = trim($request->request->get('prenom', ''));
        $nom = trim($request->request->get('nom', ''));
        $email = trim($request->request->get('email', ''));
        $numero = trim($request->request->get('numero', ''));
        $username = trim($request->request->get('username', ''));
        $password = $request->request->get('motdepasse', '');
        $confirmPassword = $request->request->get('confirmermotdepasse', '');
        $isAdmin = $request->request->getBoolean('add_is_admin', false);
        $roleIds = $request->request->all('roles') ?? [];

        if (empty($prenom) || empty($nom) || empty($email) || empty($numero) || empty($username) || empty($password) || empty($confirmPassword)) {
            return $this->returnErrorResponse($request, 'Tous les champs sont obligatoires.', 400);
        }

        if ($password !== $confirmPassword) {
            return $this->returnErrorResponse($request, 'Les mots de passe ne correspondent pas.', 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->returnErrorResponse($request, 'L\'email n\'est pas valide.', 400);
        }

        if (strlen($prenom) > 255 || strlen($nom) > 255 || strlen($email) > 255 || strlen($numero) > 255 || strlen($username) > 180) {
            return $this->returnErrorResponse($request, 'Un ou plusieurs champs dépassent la longueur maximale (255 caractères, 180 pour le nom d\'utilisateur).', 400);
        }

        $existingUser = $userRepository->findOneBy(['username' => $username]);
        if ($existingUser) {
            return $this->returnErrorResponse($request, 'Ce nom d\'utilisateur existe déjà.', 400);
        }

        $existingEmail = $userRepository->findOneBy(['email' => $email]);
        if ($existingEmail) {
            return $this->returnErrorResponse($request, 'Cet email existe déjà.', 400);
        }

        if (!$isAdmin && empty($roleIds)) {
            return $this->returnErrorResponse($request, 'Veuillez sélectionner au moins un rôle pour un utilisateur non-admin.', 400);
        }

        $user = new User();
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setEmail($email);
        $user->setTele($numero);
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setIsAdmin($isAdmin);

        if (!$isAdmin) {
            $roles = [];
            foreach ($roleIds as $roleId) {
                $role = $roleRepository->find($roleId);
                if ($role) {
                    $roles[] = $role;
                }
            }
            $user->setRoles($roles);
        }

        try {
            $em->persist($user);
            $em->flush();
            $message = 'Utilisateur ajouté avec succès.';
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => $message], 200);
            }
            $this->addFlash('success', $message);
            return new RedirectResponse($urlGenerator->generate('app_utilisateur'));
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return $this->returnErrorResponse($request, 'Un doublon existe (vérifiez le nom d\'utilisateur ou l\'email).', 400);
        } catch (\Exception $e) {
            return $this->returnErrorResponse($request, 'Erreur lors de l\'ajout de l\'utilisateur : ' . $e->getMessage(), 500);
        }
    }

    #[Route('/utilisateur/{id}/edit', name: 'app_utilisateur_edit', methods: ['POST'])]
    public function updateUser(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        RoleRepository $roleRepository,
        UserRepository $userRepository,
        UrlGeneratorInterface $urlGenerator,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->returnErrorResponse($request, 'Vous devez être connecté.', 403);
        }
        $isAdmin = method_exists($currentUser, 'isAdmin') ? $currentUser->isAdmin() : false;
        // Non-admins can only edit their own profile
        if (!$isAdmin && $currentUser->getId() !== $id) {
            return $this->returnErrorResponse($request, 'Accès refusé.', 403);
        }
        $user = $userRepository->find($id);
        if (!$user) {
            return $this->returnErrorResponse($request, 'Utilisateur non trouvé.', 404);
        }
        $prenom = trim($request->request->get('prenom', ''));
        $nom = trim($request->request->get('nom', ''));
        $email = trim($request->request->get('email', ''));
        $numero = trim($request->request->get('numero', ''));
        $username = trim($request->request->get('username', ''));
        $password = $request->request->get('motdepasse', '');
        $confirmPassword = $request->request->get('confirmermotdepasse', '');
        // Only admins can change roles and admin status
        $editIsAdmin = $isAdmin ? $request->request->getBoolean('edit_is_admin', false) : $user->isAdmin();
        $roleIds = $isAdmin ? ($request->request->all('roles') ?? []) : [];
        if (empty($prenom) || empty($nom) || empty($email) || empty($numero) || empty($username)) {
            return $this->returnErrorResponse($request, 'Tous les champs sont obligatoires (sauf le mot de passe si non modifié).', 400);
        }
        if ($password && $password !== $confirmPassword) {
            return $this->returnErrorResponse($request, 'Les mots de passe ne correspondent pas.', 400);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->returnErrorResponse($request, 'L\'email n\'est pas valide.', 400);
        }
        if (strlen($prenom) > 255 || strlen($nom) > 255 || strlen($email) > 255 || strlen($numero) > 255 || strlen($username) > 180) {
            return $this->returnErrorResponse($request, 'Un ou plusieurs champs dépassent la longueur maximale (255 caractères, 180 pour le nom d\'utilisateur).', 400);
        }
        $existingUser = $userRepository->findOneBy(['username' => $username]);
        if ($existingUser && $existingUser->getId() !== $id) {
            return $this->returnErrorResponse($request, 'Ce nom d\'utilisateur existe déjà.', 400);
        }
        $existingEmail = $userRepository->findOneBy(['email' => $email]);
        if ($existingEmail && $existingEmail->getId() !== $id) {
            return $this->returnErrorResponse($request, 'Cet email existe déjà.', 400);
        }
        // If not admin, skip role check
        if ($isAdmin === true) {
            if (!$editIsAdmin && empty($roleIds)) {
                return $this->returnErrorResponse($request, 'Veuillez sélectionner au moins un rôle pour un utilisateur non-admin.', 400);
            }
        }
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setEmail($email);
        $user->setTele($numero);
        $user->setUsername($username);
        if ($password) {
            $user->setPassword($passwordHasher->hashPassword($user, $password));
        }
        // Only admin can change admin status and roles
        if ($isAdmin) {
            $user->setIsAdmin($editIsAdmin);
            // Remove all roles first
            foreach ($user->getRoleEntities() as $role) {
                $user->removeRole($role);
            }
            if (!$editIsAdmin) {
                $roles = [];
                foreach ($roleIds as $roleId) {
                    $role = $roleRepository->find($roleId);
                    if ($role) {
                        $roles[] = $role;
                    }
                }
                $user->setRoles($roles);
            }
        }
        try {
            $em->persist($user);
            $em->flush();
            $message = 'Utilisateur modifié avec succès.';
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => $message], 200);
            }
            $this->addFlash('success', $message);
            return new RedirectResponse($urlGenerator->generate('app_utilisateur'));
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            return $this->returnErrorResponse($request, 'Un doublon existe (vérifiez le nom d\'utilisateur ou l\'email).', 400);
        } catch (\Exception $e) {
            return $this->returnErrorResponse($request, 'Erreur lors de la modification de l\'utilisateur : ' . $e->getMessage(), 500);
        }
    }

    private function returnErrorResponse(Request $request, string $message, int $status = 400): Response
    {
        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['message' => $message], $status);
        }
        $this->addFlash('error', $message);
        return new RedirectResponse($urlGenerator->generate('app_utilisateur'));
    }

    #[Route('/api/user/{id}', name: 'app_user_api', methods: ['GET'])]
    public function getUserData(int $id, UserRepository $userRepository): JsonResponse
    {
        $user = $userRepository->find($id);
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non trouvé.'], 404);
        }

        return new JsonResponse([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'email' => $user->getEmail(),
            'tele' => $user->getTele(),
            'isAdmin' => $user->isAdmin(),
            'roles' => $user->getRolesArray()
        ], 200);
    }

    #[Route('/verify/username', name: 'app_verify_username', methods: ['POST'])]
    public function verifyUsername(Request $request, UserRepository $userRepository): JsonResponse
    {
        $username = trim($request->request->get('username', ''));
        $excludeId = $request->request->get('exclude', null);
        
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', $username);
        
        if ($excludeId) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        $exists = $qb->getQuery()->getOneOrNullResult() !== null;
        return new JsonResponse($exists);
    }

    #[Route('/verify/email', name: 'app_verify_email', methods: ['POST'])]
    public function verifyEmail(Request $request, UserRepository $userRepository): JsonResponse
    {
        $email = trim($request->request->get('email', ''));
        $excludeId = $request->request->get('exclude', null);
        
        $qb = $userRepository->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);
        
        if ($excludeId) {
            $qb->andWhere('u.id != :id')
               ->setParameter('id', $excludeId);
        }
        
        $exists = $qb->getQuery()->getOneOrNullResult() !== null;
        return new JsonResponse($exists);
    }

    #[Route('/profile', name: 'app_profile')]
    public function profile(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }
        return $this->render('utilisateur/profile.html.twig', [
            'user' => $user,
            'activeLink' => 'utilisateur',
        ]);
    }

    #[Route('/hash-password', name: 'app_generate_hash')]
public function generatePassword(UserPasswordHasherInterface $passwordHasher): Response
{
    $user = new User();
    $hashedPassword = $passwordHasher->hashPassword($user, 'farabi');
    return new Response($hashedPassword);
}
}