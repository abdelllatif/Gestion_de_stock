<?php

namespace App\Controller;

use App\Entity\Chantier;
use App\Entity\User;
use App\Form\ChantierType;
use App\Repository\ChantierRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ChantierController extends AbstractController
{
    // API: Get all chantiers as JSON
    #[Route('/chantiers/api', name: 'app_chantier_api', methods: ['GET'])]
public function getChantiers(ChantierRepository $chantierRepository): JsonResponse
{
    $chantiers = $chantierRepository->findAll();
    $result = [];
    foreach ($chantiers as $chantier) {
            $result[] = [
                'id' => $chantier->getId(),
                'nom' => $chantier->getNom(),
            ];
        }
        return new JsonResponse($result);
    }

    // Page: List all chantiers (Twig)
    #[Route('/chantiers', name: 'app_chantier_index', methods: ['GET'])]
    public function index(ChantierRepository $chantierRepository, UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        return $this->render('chantier/index.html.twig', [
            'chantiers' => $chantierRepository->findAll(),
            'users' => $users,
            'activeLink' => 'chantier',
        ]);
    }

    // Create new chantier (API)
    #[Route('/chantiers/new', name: 'app_chantier_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $chantier = new Chantier();
        $data = $request->request->all();
        $chantier->setNom($data['nom'] ?? '');
        $chantier->setType($data['type'] ?? '');
        $chantier->setDescription($data['description'] ?? '');
        $chantier->setDateDebut(new \DateTime($data['date_debut'] ?? 'now'));
        if (!empty($data['date_fin'])) {
            $chantier->setDateFin(new \DateTime($data['date_fin']));
        }
        $chantier->setTeleResponsable($data['tele_responsable'] ?? '');
        $chantier->setAddress($data['address'] ?? '');
        if (!empty($data['users'])) {
            $userIds = json_decode($data['users'], true);
            foreach ($userIds as $userId) {
                $user = $userRepository->find($userId);
                if ($user) {
                    $chantier->addUser($user);
                }
            }
        }
        $errors = $validator->validate($chantier);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['message' => implode(', ', $errorMessages)], 400);
        }
        $entityManager->persist($chantier);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Chantier créé avec succès']);
    }

    // Show chantier details (API)
    #[Route('/chantiers/{id}', name: 'app_chantier_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Chantier $chantier): JsonResponse
    {
        $users = [];
        foreach ($chantier->getUsers() as $user) {
            $users[] = [
                'id' => $user->getId(),
                'value' => $user->getPrenom() . ' ' . $user->getNom(),
            ];
        }
        return new JsonResponse([
            'id' => $chantier->getId(),
            'nom' => $chantier->getNom(),
            'type' => $chantier->getType(),
            'description' => $chantier->getDescription(),
            'date_debut' => $chantier->getDateDebut()->format('Y-m-d'),
            'date_fin' => $chantier->getDateFin() ? $chantier->getDateFin()->format('Y-m-d') : '',
            'tele_responsable' => $chantier->getTeleResponsable(),
            'address' => $chantier->getAddress(),
            'users' => $users,
        ]);
    }

    // Edit chantier (API)
    #[Route('/chantiers/{id}/edit', name: 'app_chantier_edit', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function edit(Request $request, Chantier $chantier, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = $request->request->all();
        $chantier->setNom($data['nom'] ?? $chantier->getNom());
        $chantier->setType($data['type'] ?? $chantier->getType());
        $chantier->setDescription($data['description'] ?? $chantier->getDescription());
        $chantier->setDateDebut(new \DateTime($data['date_debut'] ?? $chantier->getDateDebut()->format('Y-m-d')));
        $chantier->setDateFin(!empty($data['date_fin']) ? new \DateTime($data['date_fin']) : null);
        $chantier->setTeleResponsable($data['tele_responsable'] ?? $chantier->getTeleResponsable());
        $chantier->setAddress($data['address'] ?? $chantier->getAddress());
        foreach ($chantier->getUsers() as $user) {
            $chantier->removeUser($user);
        }
        if (!empty($data['users'])) {
            $userIds = json_decode($data['users'], true);
            foreach ($userIds as $userId) {
                $user = $userRepository->find($userId);
                if ($user) {
                    $chantier->addUser($user);
                }
            }
        }
        $errors = $validator->validate($chantier);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['message' => implode(', ', $errorMessages)], 400);
        }
        $entityManager->flush();
        return new JsonResponse(['message' => 'Chantier modifié avec succès']);
    }

    // Delete chantier (API)
    #[Route('/chantiers/{id}', name: 'app_chantier_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function delete(Chantier $chantier, EntityManagerInterface $entityManager): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $entityManager->remove($chantier);
        $entityManager->flush();
        return new JsonResponse(['message' => 'Chantier supprimé avec succès']);
    }

    // Search users (API)
    #[Route('/users/search', name: 'app_chantier_users_search', methods: ['GET'])]
    public function searchUsers(UserRepository $userRepository, Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.prenom LIKE :query OR u.nom LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'value' => $user->getPrenom() . ' ' . $user->getNom(),
            ];
        }
        return new JsonResponse($results);
    }

    // Set chantier in session (API)
    #[Route('/set-chantier-session', name: 'app_set_chantier_session', methods: ['POST'])]
    public function setChantierSession(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $chantierId = $data['chantierId'] ?? null;
        if (!$chantierId) {
            return new JsonResponse(['success' => false, 'message' => 'No chantier ID provided'], 400);
        }
        $session = $request->getSession();
        $session->set('selected_chantier_id', $chantierId);
        return new JsonResponse(['success' => true]);
    }
}