<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Entity\Participe;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\EcoleRepository;
use App\Repository\ParticipeRepository;
use App\Repository\SalleRepository;
use App\Repository\UtilisateursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use League\Csv\Reader;
use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Cours")
*/
class CoursController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/cours",
    *     summary="Récupère tous les cours",
    *     tags={"Cours"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des cours",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="date", type="string", format="date-time"),
    *                 @OA\Property(property="heure", type="string", format="date-time"),
    *                 @OA\Property(property="distanciel", type="boolean"),
    *                 @OA\Property(
    *                     property="salle",
    *                     type="object",
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="salle", type="string"),
    *                     @OA\Property(property="lecteur", type="string")
    *                 ),
    *                 @OA\Property(
    *                     property="classe",
    *                     type="object",
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string"),
    *                     @OA\Property(
    *                         property="ecole",
    *                         type="object",
    *                         @OA\Property(property="id", type="integer"),
    *                         @OA\Property(property="nom", type="string")
    *                     )
    *                 )
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/cours', name: 'cours', methods:['GET'])]
    public function getAllCours(CoursRepository $coursRepository, SerializerInterface $serializer): JsonResponse
    {
        $coursList = $coursRepository->findAll();
        $jsonCoursList = $serializer->serialize($coursList, 'json', ['groups' => 'getCours']);
        return new JsonResponse($jsonCoursList, Response::HTTP_OK, [], true);
    }
    
    /**
    * @OA\Get(
    *     path="/api/cours/{id}",
    *     summary="Récupère les détails d'un cours",
    *     tags={"Cours"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du cours",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Détail du cours",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(property="nom", type="string"),
    *              @OA\Property(property="date", type="string", format="date-time"),
    *              @OA\Property(property="heure", type="string", format="date-time"),
    *              @OA\Property(property="distanciel", type="boolean"),
    *              @OA\Property(
    *                  property="salle",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="salle", type="string"),
    *                  @OA\Property(property="lecteur", type="string")
    *              ),
    *              @OA\Property(
    *                  property="classe",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string"),
    *                  @OA\Property(
    *                      property="ecole",
    *                      type="object",
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="nom", type="string")
    *                  )
    *              )
    *         )
    *     )
    * )
    */
    #[Route('/api/cours/{id}', name: 'detailCours', methods: ['GET'])]
    public function getDetailCours(Cours $cours, SerializerInterface $serializer): JsonResponse 
    {
        $jsonCours = $serializer->serialize($cours, 'json', ['groups' => 'getCours']);
        return new JsonResponse($jsonCours, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Delete(
    *     path="/api/cours/delete/{id}",
    *     summary="Supprime un cours",
    *     tags={"Cours"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du cours",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Cours supprimé avec succès"
    *     )
    * )
    */
    #[Route('/api/cours/delete/{id}', name: 'deleteCours', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteCours(Cours $cours, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($cours);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/cours/create",
    *     summary="Crée un nouveau cours",
    *     tags={"Cours"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="idSalle", type="integer", example=1),
    *                 @OA\Property(property="idClasse", type="integer", example=1),
    *                 @OA\Property(property="idIntervenant", type="integer", example=1),
    *                 @OA\Property(property="nom", type="string", example="Nom du cours"),
    *                 @OA\Property(property="date", type="string", format="date", example="2023-07-01"),
    *                 @OA\Property(property="heure", type="string", format="time", example="09:00:00"),
    *                 @OA\Property(property="distanciel", type="boolean", example=true)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="201",
    *         description="Cours créé",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(property="nom", type="string"),
    *              @OA\Property(property="date", type="string", format="date-time"),
    *              @OA\Property(property="heure", type="string", format="date-time"),
    *              @OA\Property(property="distanciel", type="boolean"),
    *              @OA\Property(
    *                  property="salle",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="salle", type="string"),
    *                  @OA\Property(property="lecteur", type="string")
    *              ),
    *              @OA\Property(
    *                  property="classe",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string"),
    *                  @OA\Property(
    *                      property="ecole",
    *                      type="object",
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="nom", type="string")
    *                  )
    *              )
    *         )
    *     )
    * )
    */
    #[Route('/api/cours/create', name:"createCours", methods: ['POST'])]
    public function createCours(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, SalleRepository $salleRepository, ClasseRepository $classeRepository, UtilisateursRepository $utilisateursRepository): JsonResponse 
    {
        $cours = $serializer->deserialize($request->getContent(), Cours::class, 'json');
        
        $content = $request->toArray();
        $idSalle = $content['idSalle'] ?? -1;
        $cours->setSalle($salleRepository->find($idSalle));

        $idClasse = $content['idClasse'] ?? -1;
        $classe = $classeRepository->find($idClasse);
        $cours->setClasse($classe);

        $idIntervenant = $content['idIntervenant'] ?? -1;
        $intervenant = $utilisateursRepository->find($idIntervenant);

        //Vérifie les valeurs
        $errors = $validator->validate($cours);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($cours);

        $participeIntervenant = new Participe();
        $participeIntervenant->setCours($cours);
        $participeIntervenant->setUtilisateur($intervenant);
        $participeIntervenant->setPresence(false);
        $em->persist($participeIntervenant);

        foreach ($classe->getUtilisateurs() as $user) {
            $participe = new Participe();
            $participe->setCours($cours);
            $participe->setUtilisateur($user);
            $participe->setPresence(false);
            $em->persist($participe);
        }

        $em->flush();

        $jsonCours = $serializer->serialize($cours, 'json', ['groups' => 'getCours']);
        
        $location = $urlGenerator->generate('detailCours', ['id' => $cours->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCours, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * @OA\Put(
    *     path="/api/cours/update/{id}",
    *     summary="Met à jour un cours",
    *     tags={"Cours"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du cours",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="idSalle", type="integer", example=1),
    *                 @OA\Property(property="idClasse", type="integer", example=1),
    *                 @OA\Property(property="nom", type="string", example="Nom du cours"),
    *                 @OA\Property(property="date", type="string", format="date", example="2023-07-01"),
    *                 @OA\Property(property="heure", type="string", format="time", example="09:00:00"),
    *                 @OA\Property(property="distanciel", type="boolean", example=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/cours/update/{id}', name:"updateCours", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une école')]
    public function updateCours(Request $request, SerializerInterface $serializer, Cours $currentCours, SalleRepository $salleRepository, ClasseRepository $classeRepository, EntityManagerInterface $em): JsonResponse 
    {
        $updatedCours = $serializer->deserialize($request->getContent(), 
                Cours::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentCours]);
        
        $content = $request->toArray();
        $idSalle = $content['idSalle'] ?? -1;
        $updatedCours->setSalle($salleRepository->find($idSalle));
        
        $idClasse = $content['idClasse'] ?? -1;
        $updatedCours->setClasse($classeRepository->find($idClasse));

        $em->persist($updatedCours);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Get(
    *     path="/api/cours/next/{idUser}",
    *     summary="Récupère le prochain cours d'un utilisateur",
    *     tags={"Cours"},
    *     @OA\Parameter(
    *         name="idUser",
    *         in="path",
    *         description="ID de l'utilisateur",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Prochain cours",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="cours", type="string"),
    *             @OA\Property(property="salle", type="string"),
    *             @OA\Property(property="date", type="string", format="date"),
    *             @OA\Property(property="heure", type="string", format="time"),
    *             @OA\Property(property="presence", type="boolean")
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Utilisateur non trouvé"
    *     )
    * )
    */
    #[Route('/api/cours/next/{idUser}', name: 'api_next_cours', methods: ['GET'])]
    public function getNextCours($idUser, EntityManagerInterface $entityManager, ParticipeRepository $participeRepository, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): Response
    {
        // Récupérer l'utilisateur par son ID
        $utilisateur = $utilisateursRepository->find($idUser);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer la date et l'heure actuelles
        $timezone = new \DateTimeZone('Europe/Paris');

        $now = new \DateTime('now', $timezone);
        $heure = $now->format('H:i:s');

        $participeCours = $utilisateur->getParticipes();

        // Récupérer le prochain cours pour l'utilisateur
        $prochainCours  = array();
        
        foreach ($participeCours as $p) {
            $cours = $p->getCours();
            $coursDate = $cours->getDate();
            $coursHeure = $cours->getHeure();
        
            if ($coursDate->format('Y-m-d') === $now->format('Y-m-d')) {
                $coursHeureLimite = clone $coursHeure;
                $coursHeureLimite->modify('+1 hour 30 minutes');
                if ($coursHeureLimite->format('H:i:s') > $heure) {
                    $prochainCours[] = $cours;
                } elseif ($coursHeure->format('H:i:s') > $heure) {
                    $prochainCours[] = $cours;
                }
            } elseif ($coursDate >= $now) {
                $prochainCours[] = $cours;
            }
        }

        usort($prochainCours, function ($cours1, $cours2) {
            if ($cours1->getDate() === $cours2->getDate()) {
                return $cours1->getHeure() <=> $cours2->getHeure();
            }
            return $cours1->getDate() <=> $cours2->getDate();
        });

        if (!$prochainCours ) {
            return $this->json(['message' => 'Aucun cours trouvé pour l\'utilisateur donné'], Response::HTTP_NOT_FOUND);
        }
        
        // Récupérer les informations nécessaires du cours
        $idCours = $prochainCours[0]->getId();
        $nomCours = $prochainCours[0]->getNom();
        $nomSalle = $prochainCours[0]->getSalle()->getSalle();
        $dateCours = $prochainCours[0]->getDate()->format('Y-m-d');
        $heureCours = $prochainCours[0]->getHeure()->format('H:i:s');
        $presence = null;

        // Récupérer la présence de l'utilisateur au cours s'il existe
        $participe = $participeRepository->findOneBy(['cours' => $prochainCours, 'utilisateur' => $utilisateur]);
        if ($participe) {
            $presence = $participe->isPresence();
        }

        return $this->json([
            'id' => $idCours,
            'cours' => $nomCours,
            'salle' => $nomSalle,
            'date' => $dateCours,
            'heure' => $heureCours,
            'presence' => $presence,
        ], Response::HTTP_OK);
    }

    /**
    * @OA\Get(
    *     path="/api/cours/getPresence/{idCours}",
    *     summary="Récupère la liste des participants présents pour un cours",
    *     tags={"Cours"},
    *     @OA\Parameter(
    *         name="idCours",
    *         in="path",
    *         description="ID du cours",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Utilisateurs présents",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(
    *                     property="utilisateur",
    *                     type="object",
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string"),
    *                     @OA\Property(property="prenom", type="string")
    *                 ),
    *                 @OA\Property(property="presence", type="boolean"),
    *                 @OA\Property(property="heure_badgeage", type="string", format="date-time", nullable=true)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Cours non trouvé"
    *     )
    * )
    */
    #[Route('/api/cours/getPresence/{idCours}', name: 'getPresence', methods: ['GET'])]
    public function getPresence($idCours, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): Response
    {
        // Récupérer l'utilisateur par son ID
        $cours = $coursRepository->find($idCours);

        if (!$cours) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $participeCours = $cours->getParticipes();
        $participes = array();

        foreach ($participeCours as $participe) {
            if($participe->isPresence()){
                $participes[] = $participe;
            }
        }
        
        $jsonCours = $serializer->serialize($participes, 'json', ['groups' => 'getPresence']);
        return new JsonResponse($jsonCours, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Get(
    *     path="/api/cours/getListEleve/{idCours}",
    *     summary="Récupère la liste des élèves pour un cours",
    *     tags={"Cours"},
    *     @OA\Parameter(
    *         name="idCours",
    *         in="path",
    *         description="ID du cours",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Liste des élèves",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="presence", type="boolean", nullable=true)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Cours non trouvé"
    *     )
    * )
    */
    #[Route('/api/cours/getListEleve/{idCours}', name: 'getListEleve', methods: ['GET'])]
    public function getListEleve($idCours, EntityManagerInterface $entityManager, ParticipeRepository $participeRepository, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): Response
    {
        // Récupérer l'utilisateur par son ID
        $cours = $coursRepository->find($idCours);

        if (!$cours) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $participeCours = $cours->getClasse()->getUtilisateurs();

        $utilisateurs = array();
    
        foreach($participeCours as $user){
            $participe = $participeRepository->findOneBy(['cours' => $cours, 'utilisateur' => $user]);
            $utilisateurs[] = [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'presence' => $participe->isPresence(),
            ];
        }
        
        $jsonCours = $serializer->serialize($utilisateurs, 'json', ['groups' => 'getParticipe']);
        return new JsonResponse($jsonCours, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Post(
    *     path="/api/cours/setPresence",
    *     summary="Enregistre les présences des élèves pour un cours",
    *     tags={"Cours"},
    *     @OA\RequestBody(
    *         description="Données de la requête",
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="coursId",
    *                     type="integer",
    *                     description="ID du cours"
    *                 ),
    *                 @OA\Property(
    *                     property="eleves",
    *                     type="array",
    *                     @OA\Items(
    *                         type="object",
    *                         @OA\Property(
    *                             property="nom",
    *                             type="string",
    *                             description="Nom de l'élève"
    *                         ),
    *                         @OA\Property(
    *                             property="prenom",
    *                             type="string",
    *                             description="Prénom de l'élève"
    *                         ),
    *                         @OA\Property(
    *                             property="presence",
    *                             type="boolean",
    *                             description="Présence de l'élève"
    *                         )
    *                     )
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Appel enregistré",
    *         @OA\MediaType(
    *             mediaType="application/json"
    *         )
    *     )
    * )
    */
    #[Route('/api/cours/setPresence', name:"validerAppel", methods: ['POST'])]
    public function validateAppel(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ParticipeRepository $participeRepository, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository): JsonResponse 
    {        
        $content = $request->toArray();
        $idCours = $content['coursId'] ?? -1;
        $cours = $coursRepository->find($idCours);

        $eleves = $content['eleves'] ?? -1;
        
        foreach ($eleves as $user) {
            $eleve = $utilisateursRepository->findOneBy(['nom' => $user["nom"], 'prenom' => $user["prenom"]]);
            $participe = $participeRepository->findOneBy(['cours' => $cours, 'utilisateur' => $eleve]);
            $participe->setPresence($user["presence"]);
            $em->persist($participe);
        }
        
        $em->flush();
        return new JsonResponse("Appel enregistré", Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Post(
    *     path="/api/cours/import",
    *     summary="Importe des cours à partir d'un fichier CSV",
    *     tags={"Cours"},
    *     @OA\RequestBody(
    *         description="Fichier CSV",
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 @OA\Property(
    *                     property="csv_file",
    *                     type="string",
    *                     format="binary",
    *                     description="Fichier CSV contenant les données des cours"
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Importation des cours terminée",
    *         @OA\MediaType(
    *             mediaType="application/json"
    *         )
    *     ),
    *     @OA\Response(
    *         response="400",
    *         description="Aucun fichier CSV fourni ou erreur lors de l'importation"
    *     )
    * )
    */
    #[Route('/api/cours/import', name:"importCours", methods: ['POST'])]
    public function importCours(Request $request, EntityManagerInterface $em, ClasseRepository $classeRepository, SalleRepository $salleRepository, UtilisateursRepository $utilisateursRepository, EcoleRepository $ecoleRepository): JsonResponse 
    {        
        $csvFile = $request->files->get('csv_file'); // Récupère le fichier CSV depuis la requête

        if (!$csvFile) {
            return $this->json(['message' => "Aucun fichier CSV fourni"],  Response::HTTP_BAD_REQUEST);
        }

        $reader = Reader::createFromPath($csvFile->getPathname());
        $reader->setHeaderOffset(0); // La première ligne contient les en-têtes

        $records = $reader->getRecords(); // Récupère les enregistrements du fichier CSV
 
        foreach ($records as $record) {
            $cours = new Cours();
            $cours->setNom($record['nom']);
            $cours->setDate(\DateTime::createFromFormat('d/m/Y', $record['date']));
            $cours->setHeure(\DateTime::createFromFormat('H:i:s', $record['heure']));
            $cours->setDistanciel($record['distanciel']);

            $salle = $salleRepository->findOneBy(['salle' => $record['salle']]);
            if($salle == null){
                return $this->json(['message' => "Salle inconnue : " . $record['salle']],  Response::HTTP_BAD_REQUEST);
            }
            $cours->setSalle($salle);

            $ecole = $ecoleRepository->findOneBy(['nom' => $record['ecole']]);
            if($ecole == null){
                return $this->json(['message' => "Ecole inconnue : " . $record['ecole']],  Response::HTTP_BAD_REQUEST);
            }

            $classe = $classeRepository->findOneBy(['nom' => $record['classe'], 'ecole' => $ecole]);
            if($classe == null){
                return $this->json(['message' => "Classe inconnue : " . $record['classe']],  Response::HTTP_BAD_REQUEST);
            }

            $intervenant = explode(" ", $record['intervenant']);
            $p = new Participe();
            $p->setCours($cours);

            $_intervenant = $utilisateursRepository->findOneBy(['nom' => $intervenant[0], 'prenom' => $intervenant[1]]);

            if($_intervenant == null){
                return $this->json(['message' => "Intervenant inconnu : " . $intervenant[0] . " " . $intervenant[1]],  Response::HTTP_BAD_REQUEST);
            }

            $p->setUtilisateur($_intervenant);
            $p->isPresence(false);
            $em->persist($p);

            $cours->setClasse($classe);
            $em->persist($cours);

            foreach ($classe->getUtilisateurs() as $user) {
                $participe = new Participe();
                $participe->setCours($cours);
                $participe->setUtilisateur($user);
                $participe->isPresence(false);
                $em->persist($participe);
            }
        }
        $em->flush();

        // Répondez avec une réponse appropriée
        return new JsonResponse("Importation des utilisateurs terminée.", Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
