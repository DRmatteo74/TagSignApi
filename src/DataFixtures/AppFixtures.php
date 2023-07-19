<?php

namespace App\DataFixtures;

use App\Entity\Classe;
use App\Entity\Cours;
use App\Entity\Ecole;
use App\Entity\Participe;
use App\Entity\Salle;
use App\Entity\Utilisateurs;
use DateTimeImmutable;
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
        $user3->setNom("VARDANYAN");
        $user3->setPrenom("Artur");
        $user3->setPassword($this->userPasswordHasher->hashPassword($user3, "prof"));
        $manager->persist($user3);

        $user4 = new Utilisateurs();
        $user4->setLogin("prof1");
        $user4->setRoles(["ROLE_PROF"]);
        $user4->setNom("BONNETON");
        $user4->setPrenom("Olivier");
        $user4->setPassword($this->userPasswordHasher->hashPassword($user4, "prof"));
        $manager->persist($user4);

        $user5 = new Utilisateurs();
        $user5->setLogin("prof2");
        $user5->setRoles(["ROLE_PROF"]);
        $user5->setNom("PARZEI");
        $user5->setPrenom("Marissa");
        $user5->setPassword($this->userPasswordHasher->hashPassword($user5, "prof"));
        $manager->persist($user5);

        $user6 = new Utilisateurs();
        $user6->setLogin("prof3");
        $user6->setRoles(["ROLE_PROF"]);
        $user6->setNom("CLOPPET");
        $user6->setPrenom("Perceval");
        $user6->setPassword($this->userPasswordHasher->hashPassword($user6, "prof"));
        $manager->persist($user6);

        // Génère des classes et des écoles
        $classeList = array("B-1", "B-2", "B-3", "M-1", "M-2");
        $ecoleList = array("ESGI", "ICAN");
        $saveClasse = null;

        foreach ($ecoleList as $e) {
            $ecole = new Ecole();
            $ecole->setNom($e);
            $manager->persist($ecole);

            foreach ($classeList as $c) {
                $classe = new Classe();
                $classe->setNom($c);
                $classe->setEcole($ecole);
                $manager->persist($classe);
                if($e == "ESGI" && $c == "B-2"){
                    $saveClasse = $classe;
                }
            }
        }

        // Génère les élèves
        $userNom = array("DI-RIENZO/Mattéo", "PEYRARD/Thibaut", "JEAN/Anthony", "DUVAL/Vincent", "HERNANDEZ/Mathis");
        $users = array();
        foreach($userNom as $u){
            $nom = explode("/", $u);
            $prenom = $nom[1];
            $nom = $nom[0];

            $user1 = new Utilisateurs();
            $user1->setLogin($prenom);
            $user1->setRoles(["ROLE_ELEVE"]);
            $user1->setNom($nom);
            $user1->setPrenom($prenom);
            $user1->setPassword($this->userPasswordHasher->hashPassword($user1, "eleve"));
            $user1->addClass($saveClasse);
            $manager->persist($user1);

            $users[] = $user1;

        }
        
        $salle1 = null;
        $salle2 = null;
        $salle3 = null;
        // Génère des salles de 101-112 à 701-712
        for ($i = 101; $i <= 712; $i += 100) {
            for ($j = $i; $j <= $i + 11; $j++) {
                $salle = new Salle();
                $salle->setSalle($j);
                $manager->persist($salle);
                if($j == 511){
                    $salle1 = $salle;
                }elseif($j == 709){
                    $salle2 = $salle;
                }elseif ($j == 501) {
                    $salle3 = $salle;
                }
            }
        }     

        // Génère des cours avec les participants
        $allCours = array();
        $cours1 = new Cours();
        $cours1->setNom("Anglais");
        $cours1->setDistanciel(false);
        $cours1->setDate(new DateTimeImmutable("2023-07-20"));
        $cours1->setHeure(new DateTimeImmutable('08:00:00'));
        $cours1->setSalle($salle1);
        $cours1->setClasse($saveClasse);
        $manager->persist($cours1);
        $allCours[] = $cours1;
        
        $participe = new Participe();
        $participe->setCours($cours1);
        $participe->setUtilisateur($user5);
        $participe->setPresence(false);
        $manager->persist($participe);
        
        $cours2 = new Cours();
        $cours2->setNom("Anglais");
        $cours2->setDistanciel(false);
        $cours2->setDate(new DateTimeImmutable("2023-07-20"));
        $cours2->setHeure(new DateTimeImmutable('09:45:00'));
        $cours2->setSalle($salle1);
        $cours2->setClasse($saveClasse);
        $manager->persist($cours2);
        $allCours[] = $cours2;
        
        $participe = new Participe();
        $participe->setCours($cours2);
        $participe->setUtilisateur($user5);
        $participe->setPresence(false);
        $manager->persist($participe);

        $cours3 = new Cours();
        $cours3->setNom("Projet Annuel");
        $cours3->setDistanciel(false);
        $cours3->setDate(new DateTimeImmutable("2023-07-20"));
        $cours3->setHeure(new DateTimeImmutable('11:30:00'));
        $cours3->setSalle($salle1);
        $cours3->setClasse($saveClasse);
        $manager->persist($cours3);
        $allCours[] = $cours3;
        
        $participe = new Participe();
        $participe->setCours($cours3);
        $participe->setUtilisateur($user3);
        $participe->setPresence(false);
        $manager->persist($participe);

        $cours4 = new Cours();
        $cours4->setNom("Projet Annuel");
        $cours4->setDistanciel(false);
        $cours4->setDate(new DateTimeImmutable("2023-07-20"));
        $cours4->setHeure(new DateTimeImmutable('14:00:00'));
        $cours4->setSalle($salle1);
        $cours4->setClasse($saveClasse);
        $manager->persist($cours4);
        $allCours[] = $cours4;
        
        $participe = new Participe();
        $participe->setCours($cours4);
        $participe->setUtilisateur($user3);
        $participe->setPresence(false);
        $manager->persist($participe);

        $cours5 = new Cours();
        $cours5->setNom("Droit informatique");
        $cours5->setDistanciel(false);
        $cours5->setDate(new DateTimeImmutable("2023-07-20"));
        $cours5->setHeure(new DateTimeImmutable('15:45:00'));
        $cours5->setSalle($salle2);
        $cours5->setClasse($saveClasse);
        $manager->persist($cours5);
        $allCours[] = $cours5;

        $participe = new Participe();
        $participe->setCours($cours5);
        $participe->setUtilisateur($user6);
        $participe->setPresence(false);
        $manager->persist($participe);

        $cours8 = new Cours();
        $cours8->setNom("Droit informatique");
        $cours8->setDistanciel(false);
        $cours8->setDate(new DateTimeImmutable("2023-07-20"));
        $cours8->setHeure(new DateTimeImmutable('17:30:00'));
        $cours8->setSalle($salle2);
        $cours8->setClasse($saveClasse);
        $manager->persist($cours8);
        $allCours[] = $cours8;
        
        $participe = new Participe();
        $participe->setCours($cours8);
        $participe->setUtilisateur($user6);
        $participe->setPresence(false);
        $manager->persist($participe);

        $cours6 = new Cours();
        $cours6->setNom("Algorithmie");
        $cours6->setDistanciel(false);
        $cours6->setDate(new DateTimeImmutable("2023-07-21"));
        $cours6->setHeure(new DateTimeImmutable('9:45:00'));
        $cours6->setSalle($salle3);
        $cours6->setClasse($saveClasse);
        $manager->persist($cours6);
        $allCours[] = $cours6;
        
        $participe = new Participe();
        $participe->setCours($cours6);
        $participe->setUtilisateur($user4);
        $participe->setPresence(false);
        $manager->persist($participe);

        $cours7 = new Cours();
        $cours7->setNom("Algorithmie");
        $cours7->setDistanciel(false);
        $cours7->setDate(new DateTimeImmutable("2023-07-21"));
        $cours7->setHeure(new DateTimeImmutable('11:30:00'));
        $cours7->setSalle($salle3);
        $cours7->setClasse($saveClasse);
        $manager->persist($cours7);
        $allCours[] = $cours7;

        $participe = new Participe();
        $participe->setCours($cours7);
        $participe->setUtilisateur($user4);
        $participe->setPresence(false);
        $manager->persist($participe);

        foreach($users as $u){
            foreach ($allCours as $c) {
                $participe = new Participe();
                $participe->setCours($c);
                $participe->setUtilisateur($u);
                $participe->setPresence(false);
                $manager->persist($participe);
            }
        }
        

        $manager->flush();
        $manager->clear();
    }
}
