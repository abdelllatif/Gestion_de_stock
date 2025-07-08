<?php

namespace App\Controller;

use App\Entity\ArticleCategorie;
use App\Entity\MachineCategorie;
use App\Repository\ArticleCategorieRepository;
use App\Repository\MachineCategorieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CategorieController extends AbstractController
{
    #[Route("/categories/articles", name: "app_categorie_article")]
    public function indexArticleCategories(ArticleCategorieRepository $articleCategorieRepository): Response
    {
        $categories = $articleCategorieRepository->findAll();
        
        return $this->render("categorie/articles.html.twig", [
            "categories" => $categories,
            'activeLink' => 'categories',
        ]);
    }
    
    #[Route("/categories/machines", name: "app_categorie_machine")]
    public function indexMachineCategories(MachineCategorieRepository $machineCategorieRepository): Response
    {
        $categories = $machineCategorieRepository->findAll();
        
        return $this->render("categorie/machines.html.twig", [
            "categories" => $categories,
            'activeLink' => 'categories',
        ]);
    }
    
    #[Route("/categorie/article/create", name: "app_categorie_article_create", methods: ["POST"])]
    public function createArticleCategorie(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data["nom"]) || empty($data["nom"])) {
            return $this->json(["errors" => ["Le nom de la catégorie est obligatoire"]], 400);
        }
        
        $categorie = new ArticleCategorie();
        $categorie->setNom($data["nom"]);
        
        if (isset($data["description"])) {
            $categorie->setDescription($data["description"]);
        }
        
        $errors = $validator->validate($categorie);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(["errors" => $errorMessages], 400);
        }
        
        $entityManager->persist($categorie);
        $entityManager->flush();
        
        return $this->json([
            "message" => "Catégorie d\'article créée avec succès",
            "categorie" => [
                "id" => $categorie->getId(),
                "nom" => $categorie->getNom(),
                "description" => $categorie->getDescription(),
            ]
        ], 201);
    }
    
    #[Route("/categorie/machine/create", name: "app_categorie_machine_create", methods: ["POST"])]
    public function createMachineCategorie(
        Request $request, 
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data["nom"]) || empty($data["nom"])) {
            return $this->json(["errors" => ["Le nom de la catégorie est obligatoire"]], 400);
        }
        
        $categorie = new MachineCategorie();
        $categorie->setNom($data["nom"]);
        
        if (isset($data["description"])) {
            $categorie->setDescription($data["description"]);
        }
        
        // TypeCons est obligatoire pour les catégories de machines
        $typeCons = $data["typeCons"] ?? 0;
        $categorie->setTypeCons($typeCons);
        
        $errors = $validator->validate($categorie);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(["errors" => $errorMessages], 400);
        }
        
        $entityManager->persist($categorie);
        $entityManager->flush();
        
        return $this->json([
            "message" => "Catégorie de machine créée avec succès",
            "categorie" => [
                "id" => $categorie->getId(),
                "nom" => $categorie->getNom(),
                "description" => $categorie->getDescription(),
                "typeCons" => $categorie->getTypeCons(),
            ]
        ], 201);
    }
    
    #[Route("/categorie/article/{id}", name: "app_categorie_article_get", methods: ["GET"])]
    public function getArticleCategorie(
        ArticleCategorie $categorie
    ): JsonResponse {
        return $this->json([
            "id" => $categorie->getId(),
            "nom" => $categorie->getNom(),
            "description" => $categorie->getDescription(),
        ]);
    }
    
    #[Route("/categorie/machine/{id}", name: "app_categorie_machine_get", methods: ["GET"])]
    public function getMachineCategorie(
        MachineCategorie $categorie
    ): JsonResponse {
        return $this->json([
            "id" => $categorie->getId(),
            "nom" => $categorie->getNom(),
            "description" => $categorie->getDescription(),
            "typeCons" => $categorie->getTypeCons(),
        ]);
    }
    
    #[Route("/categorie/article/{id}/update", name: "app_categorie_article_update", methods: ["PUT"])]
    public function updateArticleCategorie(
        ArticleCategorie $categorie,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data["nom"])) {
            $categorie->setNom($data["nom"]);
        }
        
        if (isset($data["description"])) {
            $categorie->setDescription($data["description"]);
        }
        
        $errors = $validator->validate($categorie);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(["errors" => $errorMessages], 400);
        }
        
        $entityManager->flush();
        
        return $this->json([
            "message" => "Catégorie d\'article mise à jour avec succès",
            "categorie" => [
                "id" => $categorie->getId(),
                "nom" => $categorie->getNom(),
                "description" => $categorie->getDescription(),
            ]
        ]);
    }
    
    #[Route("/categorie/machine/{id}/update", name: "app_categorie_machine_update", methods: ["PUT"])]
    public function updateMachineCategorie(
        MachineCategorie $categorie,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        
        if (isset($data["nom"])) {
            $categorie->setNom($data["nom"]);
        }
        
        if (isset($data["description"])) {
            $categorie->setDescription($data["description"]);
        }
        
        if (isset($data["typeCons"])) {
            $categorie->setTypeCons($data["typeCons"]);
        }
        
        $errors = $validator->validate($categorie);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(["errors" => $errorMessages], 400);
        }
        
        $entityManager->flush();
        
        return $this->json([
            "message" => "Catégorie de machine mise à jour avec succès",
            "categorie" => [
                "id" => $categorie->getId(),
                "nom" => $categorie->getNom(),
                "description" => $categorie->getDescription(),
                "typeCons" => $categorie->getTypeCons(),
            ]
        ]);
    }
    
    #[Route("/categorie/article/{id}/delete", name: "app_categorie_article_delete", methods: ["DELETE"])]
    public function deleteArticleCategorie(
        ArticleCategorie $categorie,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier si la catégorie est utilisée par des articles
        if (!$categorie->getArticles()->isEmpty()) {
            return $this->json([
                "errors" => ["Cette catégorie est utilisée par des articles et ne peut pas être supprimée"]
            ], 400);
        }
        
        $entityManager->remove($categorie);
        $entityManager->flush();
        
        return $this->json(["message" => "Catégorie d\'article supprimée avec succès"]);
    }
    
    #[Route("/categorie/machine/{id}/delete", name: "app_categorie_machine_delete", methods: ["DELETE"])]
    public function deleteMachineCategorie(
        MachineCategorie $categorie,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        // Vérifier si la catégorie est utilisée par des machines
        if (!$categorie->getMachines()->isEmpty()) {
            return $this->json([
                "errors" => ["Cette catégorie est utilisée par des machines et ne peut pas être supprimée"]
            ], 400);
        }
        
        $entityManager->remove($categorie);
        $entityManager->flush();
        
        return $this->json(["message" => "Catégorie de machine supprimée avec succès"]);
    }
}
