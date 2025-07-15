<?php

namespace App\Controller;

use App\Entity\Mecanicien;
use App\Repository\MecanicienRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class MecanicienController extends AbstractController
{
    #[Route('/mecanicien', name: 'app_mecanicien')]
    public function index(MecanicienRepository $mecanicienRepository): Response
    {
        $mecaniciens = $mecanicienRepository->findAll();
        
        return $this->render('mecanicien/index.html.twig', [
            'activeLink' => 'mecanicien',
            'mecaniciens' => $mecaniciens,
        ]);
    }

    #[Route('/mecanicien/new', name: 'app_mecanicien_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation des données
                $errors = [];
                if (empty($data['nom'])) $errors[] = 'Le nom est requis';
                if (empty($data['tele'])) $errors[] = 'Le numéro de téléphone est requis';

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                // Créer un nouveau mécanicien
                $mecanicien = new Mecanicien();
                $mecanicien->setNom($data['nom'])
                         ->setTele($data['tele']);
                
                $entityManager->persist($mecanicien);
                $entityManager->flush();

                return $this->json([
                    'success' => true, 
                    'message' => 'Mécanicien ajouté avec succès',
                    'mecanicien' => [
                        'id' => $mecanicien->getId(),
                        'nom' => $mecanicien->getNom(),
                        'tele' => $mecanicien->getTele()
                    ]
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]], 500);
            }
        }

        return $this->render('mecanicien/new.html.twig', [
            'activeLink' => 'mecanicien',
        ]);
    }

    #[Route('/mecanicien/{id}', name: 'app_mecanicien_show', methods: ['GET'])]
    public function show(Mecanicien $mecanicien): Response
    {
        return $this->render('mecanicien/show.html.twig', [
            'mecanicien' => $mecanicien,
            'activeLink' => 'mecanicien',
        ]);
    }

    #[Route('/mecanicien/{id}/edit', name: 'app_mecanicien_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Mecanicien $mecanicien, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation des données
                $errors = [];
                if (empty($data['nom'])) $errors[] = 'Le nom est requis';
                if (empty($data['tele'])) $errors[] = 'Le numéro de téléphone est requis';

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                // Mettre à jour les données du mécanicien
                $mecanicien->setNom($data['nom'])
                         ->setTele($data['tele']);
                
                $entityManager->flush();

                return $this->json([
                    'success' => true, 
                    'message' => 'Mécanicien modifié avec succès',
                    'mecanicien' => [
                        'id' => $mecanicien->getId(),
                        'nom' => $mecanicien->getNom(),
                        'tele' => $mecanicien->getTele()
                    ]
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la modification: ' . $e->getMessage()]], 500);
            }
        }

        return $this->render('mecanicien/edit.html.twig', [
            'mecanicien' => $mecanicien,
            'activeLink' => 'mecanicien',
        ]);
    }

    #[Route('/mecanicien/{id}/delete', name: 'app_mecanicien_delete', methods: ['DELETE'])]
    public function delete(Mecanicien $mecanicien, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($mecanicien);
            $entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Mécanicien supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/api/mecaniciens', name: 'api_mecaniciens')]
    public function getAll(MecanicienRepository $mecanicienRepository): JsonResponse
    {
        $mecaniciens = $mecanicienRepository->findAll();
        $data = [];
        
        foreach ($mecaniciens as $mecanicien) {
            $data[] = [
                'id' => $mecanicien->getId(),
                'nom' => $mecanicien->getNom(),
                'tele' => $mecanicien->getTele()
            ];
        }
        
        return $this->json($data);
    }
}
