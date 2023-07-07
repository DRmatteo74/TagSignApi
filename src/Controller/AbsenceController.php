<?php

namespace App\Controller;

use App\Entity\Participe;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\EcoleRepository;
use App\Repository\ParticipeRepository;
use App\Repository\UtilisateursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
* @OA\Tag(name="Absences")
*/
class AbsenceController extends AbstractController
{
    #[Route('/api/absences/{id}', name: 'absenceEleve', methods:['GET'])]
    public function getAbsenceOfEleve(int $id, UtilisateursRepository $utilisateursRepository): JsonResponse
    {
        $user = $utilisateursRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $participes = $user->getParticipes();
        $absences = [];

        $timezone = new \DateTimeZone('Europe/Paris');
        $now = new \DateTime('now', $timezone);

        foreach ($participes as $participe) {
            $cours = $participe->getCours();
            $heure = clone($cours->getHeure());
            $heure = $heure->modify('+1 hour 30 minutes');

            if(($cours->getDate()->format('Y-m-d') == $now->format('Y-m-d') && $heure ->format('H:i:s') >= $now->format('H:i:s')) || $cours->getDate()->format('Y-m-d') > $now->format('Y-m-d')){
                continue;
            }

            if ($participe->isPresence() === false) {
                $absences[] = [
                    'cours' => $participe->getCours()->getNom(),
                    'date' => $participe->getCours()->getDate(),
                    'heure' => $participe->getCours()->getHeure(),
                    'justifie' => $participe->isJustificatifValide(),
                    'justificatif' => $participe->getJustificatif(),
                ];
            }
        }

        return new JsonResponse(['absences' => $absences]);
    }

    #[Route('/api/absences', name: 'absences', methods:['GET'])]
    public function getAllAbsence(ParticipeRepository $participeRepository): JsonResponse
    {
        
        $participes = $participeRepository->findAll();
        $utilisateursAbsences = [];

        foreach ($participes as $participe) {
            if ($participe->isPresence() === false) {
                
                $utilisateursAbsences[] = [
                    'idUtilisateur' => $participe->getUtilisateur()->getId(),
                    'nom' => $participe->getUtilisateur()->getNom(),
                    'prenom' => $participe->getUtilisateur()->getPrenom(),
                    'cours' => $participe->getCours()->getNom(),
                    'date' => $participe->getCours()->getDate(),
                    'heure' => $participe->getCours()->getHeure(),
                    'justifie' => $participe->isJustificatifValide(),
                    'justificatif' => $participe->getJustificatif(),
                ];
            }
        }

        return new JsonResponse(['utilisateurs_absences' => $utilisateursAbsences]);

    }

    #[Route('/api/absences/ecole/{id}', name: 'absenceEcole', methods:['GET'])]
    public function getAllAbsenceOfEcole(int $id, EcoleRepository $ecoleRepository): JsonResponse
    {
        $ecole = $ecoleRepository->find($id);
    
        if (!$ecole) {
            return new JsonResponse(['error' => 'École non trouvée.'], 404);
        }
    
        $utilisateursAbsences = [];
    
        foreach ($ecole->getClasses() as $classe) {
            $classeInfo = [
                'nom' => $classe->getNom(),
                'utilisateurs' => [],
            ];
    
            foreach ($classe->getUtilisateurs() as $utilisateur) {
                $participes = $utilisateur->getParticipes();
                $absences = [];
    
                foreach ($participes as $participe) {
                    if ($participe->isPresence() === false) {
                        $absences[] = [
                            'cours' => $participe->getCours()->getNom(),
                            'date' => $participe->getCours()->getDate(),
                            'heure' => $participe->getCours()->getHeure(),
                            'justifie' => $participe->isJustificatifValide(),
                            'justificatif' => $participe->getJustificatif(),
                        ];
                    }
                }
    
                if (!empty($absences)) {
                    $userInfo = [
                        'id' => $utilisateur->getId(),
                        'nom' => $utilisateur->getNom(),
                        'prenom' => $utilisateur->getPrenom(),
                        'badge' => $utilisateur->getBadge(),
                        'absences' => $absences,
                    ];
    
                    $classeInfo['utilisateurs'][] = $userInfo;
                }
            }
    
            if (!empty($classeInfo['utilisateurs'])) {
                $utilisateursAbsences[] = $classeInfo;
            }
        }
    
        return new JsonResponse(['utilisateurs_absences' => $utilisateursAbsences]);
    }

    #[Route('/api/absences/classe/{id}', name: 'absenceClasse', methods:['GET'])]
    public function getAllAbsenceOfClasse(int $id, ClasseRepository $classeRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);

        if (!$classe) {
            return new JsonResponse(['error' => 'Classe non trouvée.'], 404);
        }

        $utilisateursAbsences = [];

        foreach ($classe->getUtilisateurs() as $utilisateur) {
            $participes = $utilisateur->getParticipes();

            $absences = [];

            foreach ($participes as $participe) {
                if ($participe->isPresence() === false) {
                    $absences[] = [
                        'cours' => $participe->getCours()->getNom(),
                        'date' => $participe->getCours()->getDate(),
                        'heure' => $participe->getCours()->getHeure(),
                        'justifie' => $participe->isJustificatifValide(),
                        'justificatif' => $participe->getJustificatif(),
                    ];
                }
            }
            
            $userInfo = [
                'id' => $utilisateur->getId(),
                'nom' => $utilisateur->getNom(),
                'prenom' => $utilisateur->getPrenom(),
                'badge' => $utilisateur->getBadge(),
                'absences' => $absences
            ];

            if (!empty($absences)) {
                array_push($utilisateursAbsences, $userInfo);
            }
        }

        return new JsonResponse(['utilisateurs_absences' => $utilisateursAbsences]);
    }

    #[Route('/api/absence/uploadJustificatif', name: 'api_absence_upload_justificatif', methods: ['POST'])]
    public function uploadJustificatif(Request $request, UtilisateursRepository $userRepository, CoursRepository $coursRepository, ParticipeRepository $participeRepository, ManagerRegistry $managerRegistry): JsonResponse
    {
        $coursID = $request->query->getInt('cours', -1);
        $userID = $request->query->getInt('user', -1);

        // Fetch the user and course from the database
        $user = $userRepository->find($userID);
        $cours = $coursRepository->find($coursID);
        
        // Create a new Participe entity
        $participe = $participeRepository->findOneBy(['utilisateur' => $user, 'cours' => $cours]);

        // Handle the file upload
        $uploadedFile = $request->files->get('file');
        $destination = $this->getParameter('justificatif_directory');

        $newFilename = sprintf('%s.%s', uniqid(), $uploadedFile->getClientOriginalExtension());
        $uploadedFile->move($destination, $newFilename);

        $participe->setJustificatif($newFilename);

        // Save the Participe entity
        $entityManager = $managerRegistry->getManager();
        $entityManager->persist($participe);
        $entityManager->flush();

        return new JsonResponse(['success' => true]);
    }
}
