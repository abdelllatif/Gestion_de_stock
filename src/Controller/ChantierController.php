<?php

namespace App\Controller;

use App\Entity\Chantier;
use App\Entity\User;
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
use Psr\Log\LoggerInterface;

final class ChantierController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    // API: Get all chantiers as JSON
    #[Route('/chantiers/api', name: 'app_chantier_api', methods: ['GET'])]
    public function getChantiers(ChantierRepository $chantierRepository): JsonResponse
    {
        try {
            $chantiers = $chantierRepository->findAll();
            $result = [];
            foreach ($chantiers as $chantier) {
                $result[] = [
                    'id' => $chantier->getId(),
                    'nom' => $chantier->getNom(),
                ];
            }
            return new JsonResponse($result);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching chantiers: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Unable to fetch chantiers'], 500);
        }
    }

    // Page: List all chantiers (Twig)
    #[Route('/chantiers', name: 'app_chantier_index', methods: ['GET'])]
    public function index(ChantierRepository $chantierRepository, UserRepository $userRepository, Request $request): Response
    {
        try {
            $session = $request->getSession();
            $selectedChantier = $session->get('selected_chantier');
            $chantierId = $selectedChantier['id'] ?? null;
            $chantierNom = $selectedChantier['nom'] ?? null;
            // If no chantier is selected, ensure session is cleared
            if (!$chantierId) {
                $session->remove('selected_chantier');
            } else {
                $chantier = $chantierRepository->find($chantierId);
                if (!$chantier) {
                    $session->remove('selected_chantier');
                    $this->logger->warning('Chantier not found for ID: ' . $chantierId . ', clearing session');
                } else {
                    $this->logger->info('Selected chantier ID: ' . $chantierId . ', Name: ' . $chantier->getNom());
                }
            }

            return $this->render('chantier/index.html.twig', [
                'chantiers' => $chantierRepository->findAll(),
                'users' => $userRepository->findAll(),
                'activeLink' => 'chantier',
                'chantier_nom' => $chantierNom,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error rendering chantier index: ' . $e->getMessage());
            throw $this->createNotFoundException('Unable to load chantier index page');
        }
    }

    // Create new chantier (API)
    #[Route('/chantiers/new', name: 'app_chantier_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        try {
            $chantier = new Chantier();
            $data = $request->request->all();

            $chantier->setNom($data['nom'] ?? '');
            $chantier->setType($data['type'] ?? '');
            $chantier->setTeleResponsable($data['tele_responsable'] ?? '');
            $chantier->setAddress($data['address'] ?? '');
            $chantier->setResponsable($data['responsable'] ?? '');

            if (!empty($data['users'])) {
                $userIds = json_decode($data['users'], true);
                if (is_array($userIds)) {
                    foreach ($userIds as $userId) {
                        $user = $userRepository->find($userId);
                        if ($user) {
                            $chantier->addUser($user);
                        }
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
        } catch (\Exception $e) {
            $this->logger->error('Error creating chantier: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Erreur lors de la création du chantier'], 500);
        }
    }

    // Show chantier details (API)
    #[Route('/chantiers/{id}', name: 'app_chantier_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Chantier $chantier): JsonResponse
    {
        try {
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
                'tele_responsable' => $chantier->getTeleResponsable(),
                'address' => $chantier->getAddress(),
                'responsable' => $chantier->getResponsable(),
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error showing chantier: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Erreur lors de l\'affichage du chantier'], 500);
        }
    }

    // Edit chantier (API)
    #[Route('/chantiers/{id}/edit', name: 'app_chantier_edit', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function edit(Request $request, Chantier $chantier, EntityManagerInterface $entityManager, ValidatorInterface $validator, UserRepository $userRepository): JsonResponse
    {
        try {
            $data = $request->request->all();

            $chantier->setNom($data['nom'] ?? $chantier->getNom());
            $chantier->setType($data['type'] ?? $chantier->getType());
            $chantier->setTeleResponsable($data['tele_responsable'] ?? $chantier->getTeleResponsable());
            $chantier->setAddress($data['address'] ?? $chantier->getAddress());
            $chantier->setResponsable($data['responsable'] ?? $chantier->getResponsable());

            foreach ($chantier->getUsers() as $user) {
                $chantier->removeUser($user);
            }

            if (!empty($data['users'])) {
                $userIds = json_decode($data['users'], true);
                if (is_array($userIds)) {
                    foreach ($userIds as $userId) {
                        $user = $userRepository->find($userId);
                        if ($user) {
                            $chantier->addUser($user);
                        }
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
        } catch (\Exception $e) {
            $this->logger->error('Error editing chantier: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Erreur lors de la modification du chantier'], 500);
        }
    }

    // Delete chantier (API)
    #[Route('/chantiers/{id}', name: 'app_chantier_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Chantier $chantier, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($chantier);
            $entityManager->flush();
            return new JsonResponse(['message' => 'Chantier supprimé avec succès']);
        } catch (\Exception $e) {
            $this->logger->error('Error deleting chantier: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Erreur lors de la suppression du chantier'], 500);
        }
    }

    // Search users (API)
    #[Route('/users/search', name: 'app_chantier_users_search', methods: ['GET'])]
    public function searchUsers(UserRepository $userRepository, Request $request): JsonResponse
    {
        try {
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
        } catch (\Exception $e) {
            $this->logger->error('Error searching users: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Erreur lors de la recherche des utilisateurs'], 500);
        }
    }

    // Set chantier in session (API)
    #[Route('/set-chantier-session', name: 'app_set_chantier_session', methods: ['POST'])]
    public function setChantierSession(Request $request, ChantierRepository $chantierRepository): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $chantierId = $data['chantierId'] ?? null;
            $chantierNom = $data['chantierNom'] ?? null;
            if (!$chantierId || !$chantierNom) {
                $this->logger->error('No chantier ID or name provided in setChantierSession');
                return new JsonResponse(['success' => false, 'message' => 'Aucun ID ou nom de chantier fourni'], 400);
            }

            // Verify chantier exists
            $chantier = $chantierRepository->find($chantierId);
            if (!$chantier) {
                $this->logger->error('Chantier not found for ID: ' . $chantierId);
                return new JsonResponse(['success' => false, 'message' => 'Chantier non trouvé'], 404);
            }

            $session = $request->getSession();
            $session->set('selected_chantier', [
                'id' => $chantierId,
                'nom' => $chantierNom
            ]);
            $this->logger->info('Chantier ID and name set in session: ' . $chantierId . ' - ' . $chantierNom);

            return new JsonResponse(['success' => true, 'message' => 'Chantier sélectionné avec succès']);
        } catch (\Exception $e) {
            $this->logger->error('Session error in setChantierSession: ' . $e->getMessage());
            return new JsonResponse(['success' => false, 'message' => 'Erreur lors de la sélection du chantier'], 500);
        }
    }
}