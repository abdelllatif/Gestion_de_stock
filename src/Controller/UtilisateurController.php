<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\RoleRepository;
use App\Entity\User;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;

final class UtilisateurController extends AbstractController
{
    #[Route('/utilisateur', name: 'app_utilisateur')]
    public function index(RoleRepository $roleRepository): Response
    {
        $roles = $roleRepository->findAll();
        $groupedRoles = [];
        foreach ($roles as $role) {
            $group = $role->getRoleGroup() ?: 'Autre';
            $groupedRoles[$group][] = $role;
        }
        return $this->render('utilisateur/index.html.twig', [
            'activeLink' => 'utilisateur',
            'groupedRoles' => $groupedRoles,
        ]);
    }

    #[Route('/utilisateur/new', name: 'app_utilisateur_new', methods: ['GET'])]
    public function new(RoleRepository $roleRepository): Response
    {
        $roles = $roleRepository->findAll();
        $groupedRoles = [];
        foreach ($roles as $role) {
            $group = $role->getRoleGroup() ?: 'Autre';
            $groupedRoles[$group][] = $role;
        }

        return $this->render('utilisateur/new.html.twig', [
            'activeLink' => 'utilisateur',
            'groupedRoles' => $groupedRoles,
        ]);
    }

    #[Route('/utilisateur/{id}/edit', name: 'app_utilisateur_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        // TODO: Fetch the user by $id and pass to the template if needed
        return $this->render('utilisateur/edit.html.twig', [
            'user_id' => $id,
            'activeLink' => 'utilisateur',
        ]);
    }

    #[Route('/utilisateur/new', name: 'app_utilisateur_new', methods: ['POST'])]
    public function addUser(
        Request $request,
        EntityManagerInterface $em,
        RoleRepository $roleRepository,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $prenom = $request->request->get('prenom');
        $nom = $request->request->get('nom');
        $email = $request->request->get('email');
        $numero = $request->request->get('numero');
        $roleIds = $request->request->get('roles', []);

        if (!$prenom || !$nom || !$email || !$numero) {
            $this->addFlash('error', 'Tous les champs sont obligatoires.');
            return new RedirectResponse($urlGenerator->generate('app_utilisateur'));
        }

        $user = new User();
        $user->setPrenom($prenom);
        $user->setNom($nom);
        $user->setEmail($email);
        $user->setTele($numero);
        // Set a default username and password for now (should be improved)
        $user->setUsername($email);
        $user->setPassword('password'); // TODO: Hash password in real app

        $isAdmin = 0;
        foreach ($roleIds as $roleId) {
            $role = $roleRepository->find($roleId);
            if ($role) {
                $user->addRole($role);
                if (strtoupper($role->getNom()) === 'ADMIN') {
                    $isAdmin = 1;
                }
            }
        }
        $user->setIsAdmin($isAdmin);

        try {
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur ajouté avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'ajout de l\'utilisateur.');
        }
        return new RedirectResponse($urlGenerator->generate('app_utilisateur'));
    }
}
