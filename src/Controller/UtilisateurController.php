<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Entity\Participe;
use App\Entity\Utilisateurs;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\EcoleRepository;
use App\Repository\UtilisateursRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Csv\Reader;
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

use OpenApi\Annotations as OA;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
* @OA\Tag(name="Utilisateurs")
*/

class UtilisateurController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/checkToken",
    *     summary="Vérifie si le token est valide",
    *     tags={"Utilisateurs"},
    *     @OA\Response(
    *         response="200",
    *         description="Token valide",
    *     ),
    *     @OA\Response(
    *         response="401",
    *         description="Erreur token non trouvé",
    *     )
    * )
    */
    #[Route('/api/checkToken', name: 'checkToken', methods: ['GET'])]
    public function checkToken(): JsonResponse
    {
        return new JsonResponse(Response::HTTP_OK);
    }

    /**
    * @OA\Get(
    *     path="/api/users/eleves",
    *     summary="Récupère tous les élèves",
    *     tags={"Utilisateurs"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des élèves",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="login", type="string"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *                 @OA\Property(property="badge", type="string", nullable=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/users/eleves', name: 'eleves', methods:['GET'])]
    public function getAllEleves(UtilisateursRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $utilisateurs = $userRepository->findAll();
        $eleves = [];

        foreach ($utilisateurs as $utilisateur) {
            $roles = $utilisateur->getRoles();

            foreach ($roles as $role) {
                if ($role === 'ROLE_ELEVE') {
                    $eleves[] = $utilisateur;
                    break;
                }
            }
        }

        $jsonEleves = $serializer->serialize($eleves, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonEleves, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Get(
    *     path="/api/users/profs",
    *     summary="Récupère tous les intervenants (professeurs)",
    *     tags={"Utilisateurs"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des intervenants",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="login", type="string"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *                 @OA\Property(property="badge", type="string", nullable=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/users/profs', name: 'profs', methods:['GET'])]
    public function getAllIntervanants(UtilisateursRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $utilisateurs = $userRepository->findAll();
        $eleves = [];
        
        foreach ($utilisateurs as $utilisateur) {
            $roles = $utilisateur->getRoles();
        
            foreach ($roles as $role) {
                if ($role === 'ROLE_PROF') {
                    $eleves[] = $utilisateur;
                    break;
                }
            }
        }
    
        $jsonEleves = $serializer->serialize($eleves, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonEleves, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Get(
    *     path="/api/users",
    *     summary="Récupère tous les utilisateurs",
    *     tags={"Utilisateurs"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des utilisateurs",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="login", type="string"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *                 @OA\Property(property="badge", type="string", nullable=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/users', name: 'user', methods:['GET'])]
    public function getAllUsers(UtilisateursRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $userRepository->findAll();
        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }
    
    /**
    * @OA\Get(
    *     path="/api/users/{id}",
    *     summary="Récupère les détails d'un utilisateur",
    *     tags={"Utilisateurs"},
    *     @OA\Parameter(
    *         name="id",
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
    *         description="Détail de l'utilisateur",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="login", type="string"),
    *             @OA\Property(property="nom", type="string"),
    *             @OA\Property(property="prenom", type="string"),
    *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *             @OA\Property(property="badge", type="string", nullable=true)
    *         )
    *     )
    * )
    */
    #[Route('/api/users/{id}', name: 'detailUser', methods: ['GET'])]
    public function getDetailUser(Utilisateurs $user, SerializerInterface $serializer): JsonResponse 
    {
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Delete(
    *     path="/api/users/delete/{id}",
    *     summary="Supprime un utilisateur",
    *     tags={"Utilisateurs"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de l'utilisateur",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Utilisateur supprimé avec succès"
    *     )
    * )
    */
    #[Route('/api/users/delete/{id}', name: 'deleteUser', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteUser(Utilisateurs $user, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/users/create",
    *     summary="Crée un nouvel utilisateur",
    *     tags={"Utilisateurs"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="login", type="string", example="john.doe"),
    *                 @OA\Property(property="nom", type="string", example="Doe"),
    *                 @OA\Property(property="prenom", type="string", example="John"),
    *                 @OA\Property(property="password", type="string", example="password"),
    *                 @OA\Property(property="badge", type="string", example="A123456"),
    *                 @OA\Property(property="roles", type="string", example="ROLE_ELEVE"),
    *                 @OA\Property(property="classesId", type="array", @OA\Items(type="integer"), example={1, 2})
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="201",
    *         description="Créé avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="login", type="string"),
    *             @OA\Property(property="nom", type="string"),
    *             @OA\Property(property="prenom", type="string"),
    *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *             @OA\Property(property="badge", type="string", nullable=true)
    *         )
    *     ),
    *     @OA\Response(
    *         response="400",
    *         description="Requête invalide",
    *         @OA\MediaType(
    *             mediaType="application/json"
    *         )
    *     )
    * )
    */
    #[Route('/api/users/create', name:"createUser", methods: ['POST'])]
    public function createUser(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, ClasseRepository $classeRepository, UserPasswordHasherInterface $userPasswordHasherInterface): JsonResponse 
    {
        $data = json_decode($request->getContent(), true);
        
        // Création de l'utilisateur
        $utilisateur = new Utilisateurs();
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setLogin($data['login']);
        $utilisateur->setRoles([$data['roles']]);
        $utilisateur->setBadge($data['badge']);
        
        $password = $data['password'];
        $hashedPassword = $userPasswordHasherInterface->hashPassword($utilisateur, $password);
        $utilisateur->setPassword($hashedPassword);

        // Ajout de l'utilisateur aux classes
        $classes = $data['classesId'];
        foreach ($classes as $classId) {
            $classe = $classeRepository->find($classId);
            if (!$classe) {
                // Gérer l'erreur si la classe n'existe pas
                return new JsonResponse("La classe avec l'ID $classId n'existe pas.", Response::HTTP_BAD_REQUEST);
            }
            $classe->addUtilisateur($utilisateur);
        }
        
        // Création des lignes Participe pour chaque cours de chaque classe
        foreach ($classes as $classId) {
            $classe = $classeRepository->find($classId);
            $cours = $classe->getCours();
            foreach ($cours as $coursItem) {
                $participe = new Participe();
                $participe->setCours($coursItem);
                $participe->setUtilisateur($utilisateur);
                $em->persist($participe);
            }
        }
        
        $em->persist($utilisateur);
        $em->flush();
        
        return new JsonResponse('Utilisateur créé avec succès.', Response::HTTP_CREATED);
        
    }

    /**
    * @OA\Put(
    *     path="/api/users/update/{id}",
    *     summary="Met à jour un utilisateur",
    *     tags={"Utilisateurs"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de l'utilisateur",
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
    *                 @OA\Property(property="login", type="string", example="john.doe"),
    *                 @OA\Property(property="nom", type="string", example="Doe"),
    *                 @OA\Property(property="prenom", type="string", example="John"),
    *                 @OA\Property(property="motdepasse", type="string", example="password"),
    *                 @OA\Property(property="badge", type="string", example="A123456")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Utilisateur mis à jour avec succès"
    *     )
    * )
    */
    #[Route('/api/users/update/{id}', name:"updateUser", methods:['PUT'])]
    public function updateUser(Request $request, SerializerInterface $serializer, Utilisateurs $currentUser, EntityManagerInterface $em): JsonResponse 
    {
        $updatedUser = $serializer->deserialize($request->getContent(), 
                User::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);
        
        $em->persist($updatedUser);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/users/import",
    *     summary="Importe des utilisateurs depuis un fichier CSV",
    *     tags={"Utilisateurs"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="multipart/form-data",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="csv_file", type="string", format="binary")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Importation réussie",
    *         @OA\MediaType(
    *             mediaType="application/json"
    *         )
    *     ),
    *     @OA\Response(
    *         response="400",
    *         description="Requête invalide",
    *         @OA\MediaType(
    *             mediaType="application/json"
    *         )
    *     )
    * )
    */
    #[Route('/api/users/import', name:"importUsers", methods: ['POST'])]
    public function importUsers(Request $request, EntityManagerInterface $em, CoursRepository $coursRepository, ClasseRepository $classeRepository, UserPasswordHasherInterface $userPasswordHasherInterface): JsonResponse 
    {        
        $csvFile = $request->files->get('csv_file'); // Récupère le fichier CSV depuis la requête

        if (!$csvFile) {
            return $this->json(['message' => "Aucun fichier CSV fourni"],  Response::HTTP_BAD_REQUEST);
        }

        $reader = Reader::createFromPath($csvFile->getPathname());
        $reader->setHeaderOffset(0); // La première ligne contient les en-têtes

        $records = $reader->getRecords(); // Récupère les enregistrements du fichier CSV
 
        foreach ($records as $record) {
            $user = new Utilisateurs();
            $user->setLogin($record['login']);
            $user->setNom($record['nom']);
            $user->setPrenom($record['prenom']);
            $user->setBadge($record['badge']);

            // Hachage du mot de passe
            $password = $record['motdepasse'];
            $hashedPassword = $userPasswordHasherInterface->hashPassword($user, $password);
            $user->setPassword($hashedPassword);

            $em->persist($user);

            // Ajout de l'utilisateur aux classes
            if (isset($record['classesId'])) {
                $classesIds = explode(';', $record['classesId']);
                foreach ($classesIds as $classId) {
                    $classe = $classeRepository->find($classId);
                    if ($classe) {
                        $classe->addUtilisateur($user);
                    }
                }
            }

            // Création des lignes Participe pour chaque cours de chaque classe
            $classes = $user->getClasses();
            foreach ($classes as $classe) {
                $cours = $coursRepository->findByClasse($classe);
                foreach ($cours as $coursItem) {
                    $participe = new Participe();
                    $participe->setCours($coursItem);
                    $participe->setUtilisateur($user);
                    $em->persist($participe);
                }
            }
        }
        $em->flush();

        
        // Répondez avec une réponse appropriée
        return new JsonResponse("Importation des utilisateurs terminée.", Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Get(
    *     path="/api/users/classe/{id}",
    *     summary="Récupère les utilisateurs d'une classe",
    *     tags={"Utilisateurs"},
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
    *         description="Liste des élèves de la classe",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="login", type="string"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *                 @OA\Property(property="badge", type="string", nullable=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/users/classe/{id}', name: 'userClasse', methods:['GET'])]
    public function getUserFromClasse($id, UtilisateursRepository $userRepository, SerializerInterface $serializer, ClasseRepository $classeRepository): JsonResponse
    {
        $classe = $classeRepository->find($id);
        $user = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.classes', 'c')
            ->where('c.id = :classeId')
            ->setParameter('classeId', $classe->getId())
            ->getQuery()
            ->getResult();
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Get(
    *     path="/api/users/ecole/{id}",
    *     summary="Récupère les utilisateurs d'une école",
    *     tags={"Utilisateurs"},
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
    *         description="Liste des élèves de l'école",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="login", type="string"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(property="prenom", type="string"),
    *                 @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *                 @OA\Property(property="badge", type="string", nullable=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/users/ecole/{id}', name: 'userEcole', methods: ['GET'])]
    public function getUserFromEcole($id, UtilisateursRepository $userRepository, SerializerInterface $serializer, EcoleRepository $ecoleRepository): JsonResponse
    {
        $ecole = $ecoleRepository->find($id);
        $utilisateurs = $userRepository->createQueryBuilder('u')
            ->leftJoin('u.classes', 'c')
            ->where('c.ecole = :ecoleId')
            ->setParameter('ecoleId', $ecole->getId())
            ->getQuery()
            ->getResult();

        $jsonUtilisateurs = $serializer->serialize($utilisateurs, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonUtilisateurs, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
