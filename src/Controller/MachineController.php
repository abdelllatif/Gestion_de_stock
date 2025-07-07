<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Entity\MachineCategorie;
use App\Repository\MachineRepository;
use App\Repository\MachineCategorieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class MachineController extends AbstractController
{
    #[Route('/machine', name: 'app_machine')]
    public function index(MachineRepository $machineRepository, MachineCategorieRepository $categorieRepository): Response
    {
        return $this->render('machine/index.html.twig', [
            'activeLink' => 'machine',
            'machines' => $machineRepository->findAll(),
            'categories' => $categorieRepository->findAll()
        ]);
    }
    
    #[Route('/machine/create', name: 'app_machine_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, MachineCategorieRepository $categorieRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $errors = [];
        if (empty($data['nom'])) {
            $errors[] = "Le nom est requis";
        }
        if (empty($data['code'])) {
            $errors[] = "Le code est requis";
        }
        if (empty($data['nbr'])) {
            $errors[] = "Le numéro est requis";
        }
        
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }
        
        $machine = new Machine();
        $machine->setNom($data['nom']);
        $machine->setCode($data['code']);
        $machine->setNbr($data['nbr']);
        
        // Ajout des nouveaux champs
        if (!empty($data['marque'])) {
            $machine->setMarque($data['marque']);
        }
        if (!empty($data['modele'])) {
            $machine->setModele($data['modele']);
        }
        if (!empty($data['anneeFabriq'])) {
            $machine->setAnneeFabriq($data['anneeFabriq']);
        }
        
        // Gestion de la catégorie
        if (!empty($data['categorie'])) {
            $categorie = $categorieRepository->find($data['categorie']);
            if ($categorie) {
                $machine->setCategorie($categorie);
            }
        }
        
        $entityManager->persist($machine);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Machine ajoutée avec succès',
            'machine' => [
                'id' => $machine->getId(),
                'nom' => $machine->getNom(),
                'code' => $machine->getCode(),
                'nbr' => $machine->getNbr(),
                'categorie' => $machine->getCategorie() ? [
                    'id' => $machine->getCategorie()->getId(),
                    'nom' => $machine->getCategorie()->getNom()
                ] : null
            ]
        ]);
    }
    
    #[Route('/machine/{id}', name: 'app_machine_get', methods: ['GET'])]
    public function getMachine(int $id, MachineRepository $machineRepository): JsonResponse
    {
        $machine = $machineRepository->find($id);
        
        if (!$machine) {
            return $this->json(['error' => 'Machine non trouvée'], 404);
        }
        
        return $this->json([
            'id' => $machine->getId(),
            'nom' => $machine->getNom(),
            'code' => $machine->getCode(),
            'nbr' => $machine->getNbr(),
            'marque' => $machine->getMarque(),
            'modele' => $machine->getModele(),
            'anneeFabriq' => $machine->getAnneeFabriq(),
            'categorie' => $machine->getCategorie() ? $machine->getCategorie()->getId() : null
        ]);
    }
    
    #[Route('/machine/{id}/update', name: 'app_machine_update', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager, MachineRepository $machineRepository, MachineCategorieRepository $categorieRepository): JsonResponse
    {
        $machine = $machineRepository->find($id);
        
        if (!$machine) {
            return $this->json(['error' => 'Machine non trouvée'], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        $errors = [];
        if (empty($data['nom'])) {
            $errors[] = "Le nom est requis";
        }
        if (empty($data['code'])) {
            $errors[] = "Le code est requis";
        }
        if (empty($data['nbr'])) {
            $errors[] = "Le numéro est requis";
        }
        
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], 400);
        }
        
        $machine->setNom($data['nom']);
        $machine->setCode($data['code']);
        $machine->setNbr($data['nbr']);
        
        // Mise à jour des nouveaux champs
        if (isset($data['marque'])) {
            $machine->setMarque($data['marque']);
        }
        if (isset($data['modele'])) {
            $machine->setModele($data['modele']);
        }
        if (isset($data['anneeFabriq'])) {
            $machine->setAnneeFabriq($data['anneeFabriq']);
        }
        
        if (!empty($data['categorie'])) {
            $categorie = $categorieRepository->find($data['categorie']);
            if ($categorie) {
                $machine->setCategorie($categorie);
            }
        } else {
            $machine->setCategorie(null);
        }
        
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Machine mise à jour avec succès'
        ]);
    }
    
    #[Route('/machine/{id}/show', name: 'app_machine_show', methods: ['GET'])]
    public function show(int $id, MachineRepository $machineRepository, MachineCategorieRepository $categorieRepository): Response
    {
        $machine = $machineRepository->find($id);
        
        if (!$machine) {
            $this->addFlash('error', 'Machine non trouvée');
            return $this->redirectToRoute('app_machine');
        }
        
        return $this->render('machine/show.html.twig', [
            'activeLink' => 'machine',
            'machine' => $machine,
            'categories' => $categorieRepository->findAll()
        ]);
    }
    
    #[Route('/machine/new', name: 'app_machine_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->redirectToRoute('app_machine');
    }

    #[Route('/machine/{id}/edit', name: 'app_machine_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        return $this->redirectToRoute('app_machine');
    }
}
