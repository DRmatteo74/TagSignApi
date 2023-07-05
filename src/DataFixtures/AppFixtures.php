<?php

namespace App\DataFixtures;

use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Ecole;
use App\Entity\Participe;
use App\Entity\Salle;
use App\Entity\Utilisateurs;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Génère des utilisateurs
        $user = new Utilisateurs();
        $user->setLogin("admin");
        $user->setRoles(["ROLE_ADMIN"]);
        $user->setNom("ADMIN");
        $user->setPrenom("Admin");
        $user->setPassword($this->userPasswordHasher->hashPassword($user, "admin"));
        $manager->persist($user);

        $user1 = new Utilisateurs();
        $user1->setLogin("eleve");
        $user1->setRoles(["ROLE_ELEVE"]);
        $user1->setNom("ELEVE");
        $user1->setPrenom("eleve");
        $user1->setPassword($this->userPasswordHasher->hashPassword($user1, "eleve"));
        $manager->persist($user1);

        $user2 = new Utilisateurs();
        $user2->setLogin("ap");
        $user2->setRoles(["ROLE_AP"]);
        $user2->setNom("AP");
        $user2->setPrenom("Ap");
        $user2->setPassword($this->userPasswordHasher->hashPassword($user2, "ap"));
        $manager->persist($user2);

        $user3 = new Utilisateurs();
        $user3->setLogin("prof");
        $user3->setRoles(["ROLE_PROF"]);
        $user3->setNom("PROF");
        $user3->setPrenom("prof");
        $user3->setPassword($this->userPasswordHasher->hashPassword($user3, "prof"));
        $manager->persist($user3);

        // Génère des classes et des écoles
        $classeList = array("B-1", "B-2", "B-3", "M-1", "M-2");
        $ecoleList = array("ESGI", "ICAN");

        foreach ($ecoleList as $e) {
            $ecole = new Ecole();
            $ecole->setNom($e);
            $manager->persist($ecole);

            foreach ($classeList as $c) {
                $classe = new Classe();
                $classe->setNom($c);
                $classe->setEcole($ecole);
                $manager->persist($classe);
            }
        }
        
        // Génère des salles de 101-112 à 701-712
        for ($i = 101; $i <= 712; $i += 100) {
            for ($j = $i; $j <= $i + 11; $j++) {
                $salle = new Salle();
                $salle->setSalle($j);
                $salle->setLecteur(substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 12));
                $manager->persist($salle);
            }
        }     

        // Génère des cours avec les participants
        $cours = new Cours();
        $cours->setNom("Cours d'anglais");
        $cours->setDistanciel(false);
        $manager->persist($cours);

        $participe = new Participe();
        $participe->setCours($cours->getId());
        $participe->setUtilisateur($user1->getId());
        $participe->setPresence(false);
        $manager->persist($participe);

        $manager->flush();
        $manager->clear();
    }
}
