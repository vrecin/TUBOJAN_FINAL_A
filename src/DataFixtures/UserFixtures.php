<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher; 
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User(); 
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setName('System Administrator');
        $admin->setRoles(['ROLE_ADMIN']); 
        $admin->setIsActive(true);
        
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'admin123'); 
        $admin->setPassword($hashedPassword); 
        
        $manager->persist($admin);

        $staff = new User();
        $staff->setUsername('staff');
        $staff->setEmail('staff@example.com');
        $staff->setName('Staff Member');
        $staff->setRoles(['ROLE_STAFF']); 
        $staff->setIsActive(true);
        
        $hashedPassword = $this->passwordHasher->hashPassword($staff, 'staff123'); 
        $staff->setPassword($hashedPassword); 
        
        $manager->persist($staff);

        $manager->flush();
    }
}