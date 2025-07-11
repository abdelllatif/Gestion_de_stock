<?php

namespace App\Controller;

use App\Entity\Chauffeur;
use App\Repository\ChauffeurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class ChauffeurController extends AbstractController
{
    #[Route('/chauffeur', name: 'app_chauffeur')]
    public function index(ChauffeurRepository $chauffeurRepository): Response
    {
        $chauffeurs = $chauffeurRepository->findAll();
        
        return $this->render('chauffeur/index.html.twig', [
            'activeLink' => 'chauffeur',
            'chauffeurs' => $chauffeurs,
        ]);
    }

    #[Route('/chauffeur/new', name: 'app_chauffeur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation des données
                $errors = [];
                if (empty($data['nom'])) $errors[] = 'Le nom est requis';
                if (empty($data['licence'])) $errors[] = 'Le numéro de licence est requis';
                if (empty($data['tele'])) $errors[] = 'Le numéro de téléphone est requis';

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                // Vérifier si le chauffeur avec la même licence existe déjà
                $existingChauffeur = $entityManager->getRepository(Chauffeur::class)->findOneBy(['licence' => $data['licence']]);
                if ($existingChauffeur) {
                    return $this->json(['success' => false, 'errors' => ['Ce numéro de licence existe déjà']], 400);
                }

                // Créer un nouveau chauffeur
                $chauffeur = new Chauffeur();
                $chauffeur->setNom($data['nom'])
                        ->setLicence($data['licence'])
                        ->setTele($data['tele']);
                
                $entityManager->persist($chauffeur);
                $entityManager->flush();

                return $this->json([
                    'success' => true, 
                    'message' => 'Chauffeur ajouté avec succès',
                    'chauffeur' => [
                        'id' => $chauffeur->getId(),
                        'nom' => $chauffeur->getNom(),
                        'licence' => $chauffeur->getLicence(),
                        'tele' => $chauffeur->getTele()
                    ]
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]], 500);
            }
        }

        return $this->render('chauffeur/new.html.twig', [
            'activeLink' => 'chauffeur',
        ]);
    }

    #[Route('/chauffeur/{id}', name: 'app_chauffeur_show', methods: ['GET'])]
    public function show(Chauffeur $chauffeur): Response
    {
        return $this->render('chauffeur/show.html.twig', [
            'chauffeur' => $chauffeur,
            'activeLink' => 'chauffeur',
        ]);
    }

    #[Route('/chauffeur/{id}/edit', name: 'app_chauffeur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Chauffeur $chauffeur, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation des données
                $errors = [];
                if (empty($data['nom'])) $errors[] = 'Le nom est requis';
                if (empty($data['licence'])) $errors[] = 'Le numéro de licence est requis';
                if (empty($data['tele'])) $errors[] = 'Le numéro de téléphone est requis';

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                // Vérifier si un autre chauffeur a déjà cette licence
                $existingChauffeur = $entityManager->getRepository(Chauffeur::class)->findOneBy(['licence' => $data['licence']]);
                if ($existingChauffeur && $existingChauffeur->getId() !== $chauffeur->getId()) {
                    return $this->json(['success' => false, 'errors' => ['Ce numéro de licence existe déjà']], 400);
                }

                // Mettre à jour les données du chauffeur
                $chauffeur->setNom($data['nom'])
                        ->setLicence($data['licence'])
                        ->setTele($data['tele']);
                
                $entityManager->flush();

                return $this->json([
                    'success' => true, 
                    'message' => 'Chauffeur modifié avec succès',
                    'chauffeur' => [
                        'id' => $chauffeur->getId(),
                        'nom' => $chauffeur->getNom(),
                        'licence' => $chauffeur->getLicence(),
                        'tele' => $chauffeur->getTele()
                    ]
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la modification: ' . $e->getMessage()]], 500);
            }
        }

        return $this->render('chauffeur/edit.html.twig', [
            'chauffeur' => $chauffeur,
            'activeLink' => 'chauffeur',
        ]);
    }

    #[Route('/chauffeur/{id}/delete', name: 'app_chauffeur_delete', methods: ['DELETE'])]
    public function delete(Chauffeur $chauffeur, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($chauffeur);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Chauffeur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/chauffeurs', name: 'api_chauffeurs')]
    public function getAll(ChauffeurRepository $chauffeurRepository): JsonResponse
    {
        $chauffeurs = $chauffeurRepository->findAll();
        $data = [];
        
        foreach ($chauffeurs as $chauffeur) {
            $data[] = [
                'id' => $chauffeur->getId(),
                'nom' => $chauffeur->getNom(),
                'licence' => $chauffeur->getLicence(),
                'tele' => $chauffeur->getTele()
            ];
        }
        
        return $this->json($data);
    }
}
