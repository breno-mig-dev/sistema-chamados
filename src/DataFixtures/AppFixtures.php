<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [
            ['name' => 'Admin', 'email' => 'admin@helpdesk.local', 'password' => 'admin123', 'roles' => ['ROLE_ADMIN']],
            ['name' => 'Técnico', 'email' => 'tech@helpdesk.local', 'password' => 'tech123', 'roles' => ['ROLE_TECHNICIAN']],
            ['name' => 'Usuário', 'email' => 'user@helpdesk.local', 'password' => 'user123', 'roles' => ['ROLE_USER']],
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setName($userData['name']);
            $user->setEmail($userData['email']);
            $user->setRoles($userData['roles']);
            $user->setPassword($this->passwordHasher->hashPassword($user, $userData['password']));
            $manager->persist($user);
        }

        $categories = ['Rede', 'Hardware', 'Software', 'Impressora', 'E-mail', 'Acesso'];

        foreach ($categories as $categoryName) {
            $category = new Category();
            $category->setName($categoryName);
            $category->setDescription("Problemas relacionados a $categoryName");
            $manager->persist($category);
        }

        $manager->flush();
    }
}
