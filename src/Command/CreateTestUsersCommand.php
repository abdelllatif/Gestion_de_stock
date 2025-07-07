<?php

namespace App\Command;

use App\Entity\Role;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-test-users',
    description: 'Crée des utilisateurs de test avec différents rôles',
)]
class CreateTestUsersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Vérifier si les rôles existent déjà, sinon les créer
        $roleRepo = $this->entityManager->getRepository(Role::class);
        
        $adminRole = $roleRepo->findOneBy(['role_group' => Role::ROLE_ADMIN]);
        if (!$adminRole) {
            $adminRole = new Role();
            $adminRole->setNom('Admin');
            $adminRole->setRoleGroup(Role::ROLE_ADMIN);
            $this->entityManager->persist($adminRole);
        }
        
        $userRole = $roleRepo->findOneBy(['role_group' => Role::ROLE_USER]);
        if (!$userRole) {
            $userRole = new Role();
            $userRole->setNom('Utilisateur');
            $userRole->setRoleGroup(Role::ROLE_USER);
            $this->entityManager->persist($userRole);
        }
        
        $managerRole = $roleRepo->findOneBy(['role_group' => Role::ROLE_MANAGER]);
        if (!$managerRole) {
            $managerRole = new Role();
            $managerRole->setNom('Manager');
            $managerRole->setRoleGroup(Role::ROLE_MANAGER);
            $this->entityManager->persist($managerRole);
        }
        
        $this->entityManager->flush();
        
        $io->success('Rôles créés ou vérifiés avec succès');

        $usersData = [
            [
                'username' => 'admin',
                'password' => 'admin123',
                'nom' => 'Admin',
                'prenom' => 'Système',
                'email' => 'admin@example.com',
                'tele' => '0600000000',
                'is_admin' => true,
                'roles' => [$adminRole]
            ],
            [
                'username' => 'manager',
                'password' => 'manager123',
                'nom' => 'Dupont',
                'prenom' => 'Jean',
                'email' => 'manager@example.com',
                'tele' => '0611111111',
                'is_admin' => false,
                'roles' => [$managerRole]
            ],
            [
                'username' => 'user',
                'password' => 'user123',
                'nom' => 'Martin',
                'prenom' => 'Sophie',
                'email' => 'user@example.com',
                'tele' => '0622222222',
                'is_admin' => false,
                'roles' => [$userRole]
            ],
            [
                'username' => 'tech',
                'password' => 'tech123',
                'nom' => 'Dubois',
                'prenom' => 'Pierre',
                'email' => 'tech@example.com',
                'tele' => '0633333333',
                'is_admin' => false,
                'roles' => [$userRole]
            ]
        ];

        $count = 0;
        foreach ($usersData as $data) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = $this->entityManager->getRepository(User::class)
                ->findOneBy(['username' => $data['username']]);
            
            if ($existingUser) {
                $io->note("L'utilisateur '{$data['username']}' existe déjà. Mise à jour des informations.");
                $user = $existingUser;
            } else {
                $user = new User();
                $user->setUsername($data['username']);
                $count++;
            }

            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            
            // Configurer l'utilisateur
            $user->setPassword($hashedPassword)
                ->setNom($data['nom'])
                ->setPrenom($data['prenom'])
                ->setEmail($data['email'])
                ->setTele($data['tele'])
                ->setIsAdmin($data['is_admin']);
                
            // Attribuer les rôles
            $user->setRoles($data['roles']);

            $this->entityManager->persist($user);
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success("$count nouveaux utilisateurs créés avec succès!");
        } else {
            $io->success("Les utilisateurs ont été mis à jour.");
        }
        
        $io->table(
            ['Nom d\'utilisateur', 'Mot de passe', 'Rôle'],
            [
                ['admin', 'admin123', 'Administrateur'],
                ['manager', 'manager123', 'Manager'],
                ['user', 'user123', 'Utilisateur standard'],
                ['tech', 'tech123', 'Utilisateur standard']
            ]
        );
        
        $io->note('Vous pouvez maintenant vous connecter avec ces utilisateurs');

        return Command::SUCCESS;
    }
}
