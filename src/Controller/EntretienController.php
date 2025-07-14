<?php

namespace App\Controller;

use App\Entity\Entretien;
use App\Entity\Vidange;
use App\Entity\Reparation;
use App\Entity\Machine;
use App\Entity\Chantier;
use App\Entity\Mecanicien;
use App\Entity\Chauffeur;
use App\Repository\EntretienRepository;
use App\Repository\MachineRepository;
use App\Repository\ChantierRepository;
use App\Repository\MecanicienRepository;
use App\Repository\ChauffeurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

final class EntretienController extends AbstractController
{
    #[Route('/entretien', name: 'app_entretien')]
    public function index(EntretienRepository $entretienRepository): Response
    {
        $entretiens = $entretienRepository->findAll();
        
        return $this->render('entretien/index.html.twig', [
            'activeLink' => 'entretien',
            'entretiens' => $entretiens,
        ]);
    }
    
    #[Route('/entretien/{id}', name: 'app_entretien_show', requirements: ['id' => '\d+'])]
    public function show(Entretien $entretien): Response
    {
        return $this->render('entretien/show.html.twig', [
            'activeLink' => 'entretien',
            'entretien' => $entretien,
        ]);
    }
    
    #[Route('/entretien/vidange', name: 'app_entretien_vidange')]
    public function vidange(EntityManagerInterface $entityManager): Response
    {
        $vidanges = $entityManager->getRepository(Vidange::class)->findAll();
        
        return $this->render('entretien/vidange.html.twig', [
            'activeLink' => 'entretien',
            'vidanges' => $vidanges,
        ]);
    }
    
    #[Route('/entretien/reparation', name: 'app_entretien_reparation')]
    public function reparation(EntityManagerInterface $entityManager): Response
    {
        $reparations = $entityManager->getRepository(Reparation::class)->findAll();
        
        return $this->render('entretien/reparation.html.twig', [
            'activeLink' => 'entretien',
            'reparations' => $reparations,
        ]);
    }
    
    #[Route('/entretien/new', name: 'app_entretien_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation des données
                $errors = [];
                if (empty($data['numero'])) $errors[] = 'Le numéro est requis';
                if (empty($data['date'])) $errors[] = 'La date est requise';
                if (empty($data['machine_id'])) $errors[] = 'La machine est requise';
                if (empty($data['chantier_id'])) $errors[] = 'Le chantier est requis';
                if (empty($data['has_vidange']) && empty($data['has_reparation'])) $errors[] = 'Au moins un type d\'intervention (vidange ou réparation) est requis';
                
                // Validation supplémentaire pour la vidange si sélectionnée
                if (!empty($data['has_vidange'])) {
                    if (empty($data['vidange']['type_huile'])) $errors[] = 'Le type d\'huile est requis pour la vidange';
                    if (empty($data['vidange']['quantite'])) $errors[] = 'La quantité d\'huile est requise pour la vidange';
                }
                
                // Validation supplémentaire pour la réparation si sélectionnée
                if (!empty($data['has_reparation'])) {
                    if (empty($data['reparation']['description'])) $errors[] = 'La description est requise pour la réparation';
                }

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                // Récupérer les entités liées
                $machine = $entityManager->getRepository(Machine::class)->find($data['machine_id']);
                if (!$machine) {
                    return $this->json(['success' => false, 'errors' => ['Machine introuvable']], 400);
                }
                
                $chantier = $entityManager->getRepository(Chantier::class)->find($data['chantier_id']);
                if (!$chantier) {
                    return $this->json(['success' => false, 'errors' => ['Chantier introuvable']], 400);
                }
                
                $mecanicien = null;
                if (!empty($data['mecanicien_id'])) {
                    $mecanicien = $entityManager->getRepository(Mecanicien::class)->find($data['mecanicien_id']);
                }
                
                $chauffeur = null;
                if (!empty($data['chauffeur_id'])) {
                    $chauffeur = $entityManager->getRepository(Chauffeur::class)->find($data['chauffeur_id']);
                }
                
                // Convertir la date
                $dateEntretien = \DateTime::createFromFormat('d/m/Y', $data['date']);
                if (!$dateEntretien) {
                    return $this->json(['success' => false, 'errors' => ['Format de date invalide']], 400);
                }
                
                // Créer l'entretien
                $entretien = new Entretien();
                $entretien->setNumero($data['numero']);
                $entretien->setDate($dateEntretien);
                $entretien->setMachine($machine);
                $entretien->setChantier($chantier);
                
                if ($mecanicien) {
                    $entretien->setMecanicien($mecanicien);
                }
                
                if ($chauffeur) {
                    $entretien->setChauffeur($chauffeur);
                }
                
                $entityManager->persist($entretien);
                
                // Créer la vidange si nécessaire
                if (!empty($data['has_vidange'])) {
                    $vidange = new Vidange();
                    $vidange->setDate($dateEntretien); // Utiliser la même date que l'entretien
                    $vidange->setTypeChangment($data['vidange']['type_huile']);
                    $vidange->setConsomation((string)$data['vidange']['quantite']);
                    $vidange->setConsoProchaineVidange(0.0); // Valeurs par défaut
                    $vidange->setProchaineFilterChange(0.0); // Valeurs par défaut
                    $vidange->setMontantTtc(0.0); // Valeur par défaut
                    
                    if (!empty($data['vidange']['notes'])) {
                        // Si vous n'avez pas de champ pour les notes, vous pouvez les mettre dans un autre champ
                        // ou les ignorer
                    }
                    
                    $vidange->setEntretien($entretien);
                    $entityManager->persist($vidange);
                }
                
                // Créer la réparation si nécessaire
                if (!empty($data['has_reparation'])) {
                    $reparation = new Reparation();
                    $reparation->setDate($dateEntretien); // Utiliser la même date que l'entretien
                    $reparation->setDesignations($data['reparation']['description']); // Nous utilisons 'description' du formulaire pour 'designations' de l'entité
                    $reparation->setObservation($data['reparation']['description']); // Par défaut, utiliser la description comme observation
                    $reparation->setConsomation(0.0); // Valeur par défaut
                    
                    if (!empty($data['reparation']['cout'])) {
                        $reparation->setMontantTtc((float)$data['reparation']['cout']); // Nous utilisons 'cout' du formulaire pour 'montantTtc' de l'entité
                    } else {
                        $reparation->setMontantTtc(0.0); // Valeur par défaut - correction pour utiliser un float
                    }
                    
                    $reparation->setEntretien($entretien);
                    $entityManager->persist($reparation);
                }
                
                $entityManager->flush();

                return $this->json([
                    'success' => true, 
                    'message' => 'Entretien créé avec succès',
                    'entretien_id' => $entretien->getId()
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la création: ' . $e->getMessage()]], 500);
            }
        }

        return $this->render('entretien/new.html.twig', [
            'activeLink' => 'entretien',
        ]);
    }
    
    #[Route('/entretien/{id}/edit', name: 'app_entretien_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entretien $entretien, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            try {
                $data = json_decode($request->getContent(), true);
                
                // Validation des données
                $errors = [];
                if (empty($data['numero'])) $errors[] = 'Le numéro est requis';
                if (empty($data['date'])) $errors[] = 'La date est requise';
                if (empty($data['machine_id'])) $errors[] = 'La machine est requise';
                if (empty($data['chantier_id'])) $errors[] = 'Le chantier est requis';
                if (empty($data['has_vidange']) && empty($data['has_reparation'])) $errors[] = 'Au moins un type d\'intervention (vidange ou réparation) est requis';
                
                // Validation supplémentaire pour la vidange si sélectionnée
                if (!empty($data['has_vidange'])) {
                    if (empty($data['vidange']['type_huile'])) $errors[] = 'Le type d\'huile est requis pour la vidange';
                    if (empty($data['vidange']['quantite'])) $errors[] = 'La quantité d\'huile est requise pour la vidange';
                }
                
                // Validation supplémentaire pour la réparation si sélectionnée
                if (!empty($data['has_reparation'])) {
                    if (empty($data['reparation']['description'])) $errors[] = 'La description est requise pour la réparation';
                }

                if (!empty($errors)) {
                    return $this->json(['success' => false, 'errors' => $errors], 400);
                }

                // Récupérer les entités liées
                $machine = $entityManager->getRepository(Machine::class)->find($data['machine_id']);
                if (!$machine) {
                    return $this->json(['success' => false, 'errors' => ['Machine introuvable']], 400);
                }
                
                $chantier = $entityManager->getRepository(Chantier::class)->find($data['chantier_id']);
                if (!$chantier) {
                    return $this->json(['success' => false, 'errors' => ['Chantier introuvable']], 400);
                }
                
                $mecanicien = null;
                if (!empty($data['mecanicien_id'])) {
                    $mecanicien = $entityManager->getRepository(Mecanicien::class)->find($data['mecanicien_id']);
                }
                
                $chauffeur = null;
                if (!empty($data['chauffeur_id'])) {
                    $chauffeur = $entityManager->getRepository(Chauffeur::class)->find($data['chauffeur_id']);
                }
                
                // Convertir la date
                $dateEntretien = \DateTime::createFromFormat('d/m/Y', $data['date']);
                if (!$dateEntretien) {
                    return $this->json(['success' => false, 'errors' => ['Format de date invalide']], 400);
                }
                
                // Mettre à jour l'entretien
                $entretien->setNumero($data['numero']);
                $entretien->setDate($dateEntretien);
                $entretien->setMachine($machine);
                $entretien->setChantier($chantier);
                $entretien->setMecanicien($mecanicien);
                $entretien->setChauffeur($chauffeur);
                
                // Gérer la vidange
                $vidange = null;
                foreach ($entretien->getVidanges() as $v) {
                    $vidange = $v;
                    break; // Nous ne prenons que la première vidange associée
                }
                
                if (!empty($data['has_vidange'])) {
                    if (!$vidange) {
                        $vidange = new Vidange();
                        $vidange->setEntretien($entretien);
                        $entityManager->persist($vidange);
                    }
                    
                    $vidange->setDate($dateEntretien);
                    $vidange->setTypeChangment($data['vidange']['type_huile']);
                    $vidange->setConsomation((string)$data['vidange']['quantite']);
                    // Conserver les autres valeurs si elles existent déjà
                    if (!$vidange->getConsoProchaineVidange()) {
                        $vidange->setConsoProchaineVidange(0.0);
                    }
                    if (!$vidange->getProchaineFilterChange()) {
                        $vidange->setProchaineFilterChange(0.0);
                    }
                    if (!$vidange->getMontantTtc()) {
                        $vidange->setMontantTtc(0.0);
                    }
                } else if ($vidange) {
                    // Si on avait une vidange mais qu'on ne l'a plus sélectionné, on la supprime
                    $entityManager->remove($vidange);
                }
                
                // Gérer la réparation
                $reparation = null;
                foreach ($entretien->getReparations() as $r) {
                    $reparation = $r;
                    break; // Nous ne prenons que la première réparation associée
                }
                
                if (!empty($data['has_reparation'])) {
                    if (!$reparation) {
                        $reparation = new Reparation();
                        $reparation->setEntretien($entretien);
                        $entityManager->persist($reparation);
                    }
                    
                    $reparation->setDate($dateEntretien);
                    $reparation->setDesignations($data['reparation']['description']);
                    $reparation->setObservation($data['reparation']['description']);
                    
                    if (!empty($data['reparation']['cout'])) {
                        $reparation->setMontantTtc((float)$data['reparation']['cout']);
                    } else if (!$reparation->getMontantTtc()) {
                        $reparation->setMontantTtc(0.0);
                    }
                    
                    if (!$reparation->getConsomation()) {
                        $reparation->setConsomation(0.0);
                    }
                } else if ($reparation) {
                    // Si on avait une réparation mais qu'on ne l'a plus sélectionné, on la supprime
                    $entityManager->remove($reparation);
                }
                
                $entityManager->flush();

                return $this->json([
                    'success' => true, 
                    'message' => 'Entretien modifié avec succès',
                    'entretien_id' => $entretien->getId()
                ]);

            } catch (\Exception $e) {
                return $this->json(['success' => false, 'errors' => ['Erreur lors de la modification: ' . $e->getMessage()]], 500);
            }
        }
        
        // Préparer les données pour pré-remplir le formulaire
        $vidange = null;
        foreach ($entretien->getVidanges() as $v) {
            $vidange = $v;
            break; // On prend la première vidange associée
        }
        
        $reparation = null;
        foreach ($entretien->getReparations() as $r) {
            $reparation = $r;
            break; // On prend la première réparation associée
        }

        return $this->render('entretien/edit.html.twig', [
            'activeLink' => 'entretien',
            'entretien' => $entretien,
            'vidange' => $vidange,
            'reparation' => $reparation,
        ]);
    }
    
    // API pour récupérer les machines
    #[Route('/api/machines', name: 'api_machines')]
    public function getAllMachines(MachineRepository $machineRepository): JsonResponse
    {
        $machines = $machineRepository->findAll();
        $data = [];
        
        foreach ($machines as $machine) {
            $data[] = [
                'id' => $machine->getId(),
                'nom' => $machine->getNom()
            ];
        }
        
        return $this->json($data);
    }
    
    // API pour récupérer les chantiers
    #[Route('/api/chantiers', name: 'api_chantiers')]
    public function getAllChantiers(ChantierRepository $chantierRepository): JsonResponse
    {
        $chantiers = $chantierRepository->findAll();
        $data = [];
        
        foreach ($chantiers as $chantier) {
            $data[] = [
                'id' => $chantier->getId(),
                'nom' => $chantier->getNom()
            ];
        }
        
        return $this->json($data);
    }
    
  
    #[Route('/api/mecaniciens', name: 'api_mecaniciens')]
    public function getAllMecaniciens(MecanicienRepository $mecanicienRepository): JsonResponse
    {
        $mecaniciens = $mecanicienRepository->findAll();
        $data = [];
        
        foreach ($mecaniciens as $mecanicien) {
            $data[] = [
                'id' => $mecanicien->getId(),
                'nom' => $mecanicien->getNom()
            ];
        }
        
        return $this->json($data);
    }
    
    // API pour récupérer les chauffeurs
    #[Route('/api/chauffeurs', name: 'api_chauffeurs')]
    public function getAllChauffeurs(ChauffeurRepository $chauffeurRepository): JsonResponse
    {
        $chauffeurs = $chauffeurRepository->findAll();
        $data = [];
        
        foreach ($chauffeurs as $chauffeur) {
            $data[] = [
                'id' => $chauffeur->getId(),
                'nom' => $chauffeur->getNom()
            ];
        }
        
        return $this->json($data);
    }
}
