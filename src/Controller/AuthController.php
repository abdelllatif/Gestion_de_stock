<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\UserRepository;

final class AuthController extends AbstractController
{
    #[Route('/', name: 'app_auth', methods: ['GET', 'POST'])]
    public function index(Request $request,  UserRepository $userRepository): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('dashboard');
        }
        if ($request->isMethod('POST')) {
          $email = $request->request->get('email');
          $password = $request->request->get('password');

            $user = $userRepository->findOneBy(['email' => $email, 'password' => $password]);
            if ($user) {
                $this->getUser()->setUser($user);
                $this->addFlash('success', 'Login successful');
                return $this->redirectToRoute('dashboard');
            } else {
                $this->addFlash('error', 'Invalid credentials');
            }
        }
        return $this->render('auth/index.html.twig', [
            'controller_name' => 'AuthController',
        ]);
    }
}
