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
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
* @OA\Tag(name="Absences")
*/
class AbsenceController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/absences/{id}",
    *     summary="Obtient les absences d'un élève",
    *     tags={"Absences"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de l'élève",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Absences de l'élève",
    *         @OA\JsonContent(
    *             @OA\Property(property="absences", type="array", @OA\Items(
    *                 @OA\Property(property="idCours", type="integer"),
    *                 @OA\Property(property="cours", type="string"),
    *                 @OA\Property(property="date", type="string", format="date"),
    *                 @OA\Property(property="heure", type="string", format="time"),
    *                 @OA\Property(property="justifie", type="boolean"),
    *                 @OA\Property(property="justificatif", type="string", nullable=true)
    *             ))
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Utilisateur non trouvé"
    *     )
    * )
    */
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
                    'idCours' => $participe->getCours()->getId(),
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

    /**
    * @OA\Get(
    *     path="/api/absences",
    *     summary="Obtient toutes les absences",
    *     tags={"Absences"},
    *     @OA\Response(
    *         response="200",
    *         description="Toutes les absences",
    *         @OA\JsonContent(
    *             @OA\Property(property="utilisateurs_absences", type="array", @OA\Items(
    *                 @OA\Property(property="idUtilisateur", type="integer"),
    *                 @OA\Property(property="idCours", type="integer"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="cours", type="string"),
    *                 @OA\Property(property="date", type="string", format="date"),
    *                 @OA\Property(property="heure", type="string", format="time"),
    *                 @OA\Property(property="justifie", type="boolean"),
    *                 @OA\Property(property="justificatif", type="string", nullable=true)
    *             ))
    *         )
    *     )
    * )
    */
    #[Route('/api/absences', name: 'absences', methods:['GET'])]
    public function getAllAbsence(ParticipeRepository $participeRepository): JsonResponse
    {
        
        $participes = $participeRepository->findAll();
        $utilisateursAbsences = [];

        foreach ($participes as $participe) {
            if ($participe->isPresence() === false) {
                
                $utilisateursAbsences[] = [
                    'idUtilisateur' => $participe->getUtilisateur()->getId(),
                    'idCours' => $participe->getCours()->getId(),
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

    /**
    * @OA\Get(
    *     path="/api/absences/ecole/{id}",
    *     summary="Obtient toutes les absences d'une école",
    *     tags={"Absences"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de l'école",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Toutes les absences de l'école",
    *         @OA\JsonContent(
    *             @OA\Property(property="utilisateurs_absences", type="array", @OA\Items(
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="utilisateurs", type="array", @OA\Items(
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string"),
    *                     @OA\Property(property="prenom", type="string"),
    *                     @OA\Property(property="badge", type="integer"),
    *                     @OA\Property(property="absences", type="array", @OA\Items(
    *                         @OA\Property(property="idCours", type="integer"),
    *                         @OA\Property(property="cours", type="string"),
    *                         @OA\Property(property="date", type="string", format="date"),
    *                         @OA\Property(property="heure", type="string", format="time"),
    *                         @OA\Property(property="justifie", type="boolean"),
    *                         @OA\Property(property="justificatif", type="string", nullable=true)
    *                     ))
    *                 ))
    *             ))
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="École non trouvée"
    *     )
    * )
    */
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
                            'idCours' => $participe->getCours()->getId(),
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

    /**
    * @OA\Get(
    *     path="/api/absences/classe/{id}",
    *     summary="Obtient toutes les absences d'une classe",
    *     tags={"Absences"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de la classe",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Toutes les absences de la classe",
    *         @OA\JsonContent(
    *             @OA\Property(property="utilisateurs_absences", type="array", @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="badge", type="integer"),
    *                 @OA\Property(property="absences", type="array", @OA\Items(
    *                     @OA\Property(property="idCours", type="integer"),
    *                     @OA\Property(property="cours", type="string"),
    *                     @OA\Property(property="date", type="string", format="date"),
    *                     @OA\Property(property="heure", type="string", format="time"),
    *                     @OA\Property(property="justifie", type="boolean"),
    *                     @OA\Property(property="justificatif", type="string", nullable=true)
    *                 ))
    *             ))
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Classe non trouvée"
    *     )
    * )
    */
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
                        'idCours' => $participe->getCours()->getId(),
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

    /**
    * @OA\Post(
    *     path="/api/absence/uploadJustificatif",
    *     summary="Télécharge un justificatif pour une absence",
    *     tags={"Absences"},
    *     @OA\RequestBody(
    *         required=true,
    *         description="Données de la requête",
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="cours",
    *                     description="ID du cours",
    *                     type="integer",
    *                     format="int64"
    *                 ),
    *                 @OA\Property(
    *                     property="user",
    *                     description="ID de l'utilisateur",
    *                     type="integer",
    *                     format="int64"
    *                 ),
    *                 @OA\Property(
    *                     property="file",
    *                     description="Fichier justificatif",
    *                     type="string",
    *                     format="binary"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Justificatif téléchargé avec succès"
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Cours ou élève non trouvée"
    *     )
    * )
    */
    #[Route('/api/absence/uploadJustificatif', name: 'api_absence_upload_justificatif', methods: ['POST'])]
    public function uploadJustificatif(Request $request, UtilisateursRepository $userRepository, CoursRepository $coursRepository, ParticipeRepository $participeRepository, ManagerRegistry $managerRegistry): JsonResponse
    {
        $coursID = $request->get('cours', -1);
        $userID = $request->get('user', -1);
        
        // Fetch the user and course from the database
        $user = $userRepository->find($userID);
        $cours = $coursRepository->find($coursID);

        if(!$user || !$cours){
            return new JsonResponse(['error' => 'Cours ou élève non trouvée.', 'user' => $userID, 'coursId'=> $coursID], 404);
        }
        
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


    /**
    * @OA\Get(
    *     path="/api/document/{filename}",
    *     summary="Récupère un document",
    *     tags={"Absences"},
    *     @OA\Parameter(
    *         name="filename",
    *         in="path",
    *         description="Nom du fichier à récupérer",
    *         required=true,
    *         @OA\Schema(type="string")
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Fichier trouvé",
    *         @OA\MediaType(
    *             mediaType="application/octet-stream"
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Fichier non trouvé"
    *     )
    * )
    */
    #[Route('/api/document/{filename}', name: 'get_document', methods:["GET"])]
    public function getDocument(string $filename): BinaryFileResponse
    {
        $filePath = $this->getParameter('justificatif_directory') . '/' . $filename;

        // Vérifiez si le fichier existe
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Le document demandé n\'existe pas.');
        }

        // Créez une réponse avec le fichier en tant que contenu
        return new BinaryFileResponse($filePath);
    }
}
