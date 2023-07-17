<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Repository\EcoleRepository;
use App\Repository\UtilisateursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Classes")
*/

class ClasseController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/classes",
    *     summary="Récupère toutes les classes",
    *     tags={"Classes"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des classes",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="nom", type="string"),
    *                 @OA\Property(
    *                     property="ecole",
    *                     type="object",
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string")
    *                 ),
    *                 @OA\Property(
    *                     property="utilisateurs",
    *                     type="array",
    *                     @OA\Items(
    *                         @OA\Property(property="id", type="integer"),
    *                         @OA\Property(property="nom", type="string"),
    *                         @OA\Property(property="prenom", type="string")
    *                     )
    *                 )
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/classes', name: 'classe', methods:['GET'])]
    public function getAllClasses(ClasseRepository $classeRepository, SerializerInterface $serializer): JsonResponse
    {
        $classeList = $classeRepository->findAll();
        $jsonClasseList = $serializer->serialize($classeList, 'json', ['groups' => 'getClasses']);
        return new JsonResponse($jsonClasseList, Response::HTTP_OK, [], true);
    }
    
    /**
    * @OA\Get(
    *     path="/api/classes/{id}",
    *     summary="Récupère les détails d'une classe",
    *     tags={"Classes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de la classe",
    *         required=true,
    *         @OA\Schema(
    *             type="integer"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Détails de la classe",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(property="nom", type="string"),
    *              @OA\Property(
    *                  property="ecole",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string")
    *              ),
    *              @OA\Property(
    *                  property="utilisateurs",
    *                  type="array",
    *                  @OA\Items(
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="nom", type="string"),
    *                      @OA\Property(property="prenom", type="string")
    *                  )
    *              )
    *         )
    *     )
    * )
    */
    #[Route('/api/classes/{id}', name: 'detailClasse', methods: ['GET'])]
    public function getDetailClasse(Classe $classe, SerializerInterface $serializer): JsonResponse 
    {
        $jsonClasse = $serializer->serialize($classe, 'json', ['groups' => 'getClasses']);
        return new JsonResponse($jsonClasse, Response::HTTP_OK, ['accept' => 'json'], true);
    }


    /**
    * @OA\Delete(
    *     path="/api/classes/delete/{id}",
    *     summary="Supprime une classe",
    *     tags={"Classes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de la classe",
    *         required=true,
    *         @OA\Schema(
    *             type="integer"
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Classe supprimée avec succès"
    *     )
    * )
    */
    #[Route('/api/classes/delete/{id}', name: 'deleteClasse', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une classe')]
    public function deleteClasse(Classe $classe, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($classe);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/classes/create",
    *     summary="Crée une classe",
    *     tags={"Classes"},
    *     @OA\RequestBody(
    *         description="Données de la classe",
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(property="nom", type="string", example="Nom de la classe"),
    *                 @OA\Property(property="idEcole", type="integer", example=1),
    *                 @OA\Property(property="idUtilisateurs", type="array", @OA\Items(type="integer"), example={1, 2})
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="201",
    *         description="Classe créée",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="nom", type="string"),
    *             @OA\Property(
    *                 property="ecole",
    *                 type="object",
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="nom", type="string")
    *             ),
    *             @OA\Property(
    *                 property="utilisateurs",
    *                 type="array",
    *                 @OA\Items(
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string"),
    *                     @OA\Property(property="prenom", type="string")
    *                 )
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="400",
    *         description="Erreur de validation des données"
    *     )
    * )
    */
    #[Route('/api/classes/create', name:"createClasse", methods: ['POST'])]
    public function createClasse(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, EcoleRepository $ecoleRepository, ValidatorInterface $validator, UtilisateursRepository $utilisateursRepository): JsonResponse 
    {
        $classe = $serializer->deserialize($request->getContent(), Classe::class, 'json');
        
        $content = $request->toArray();
        $idEcole = $content['idEcole'] ?? -1;
        $classe->setEcole($ecoleRepository->find($idEcole));

        //Vérifie les valeurs
        $errors = $validator->validate($classe);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $idUsers = $content['idUtilisateurs'] ?? -1;
        foreach ($idUsers as $user) {
            $eleve = $utilisateursRepository->find($user);
            if (!$eleve) {
                // Gérer l'erreur si la classe n'existe pas
                return new JsonResponse("La classe avec l'ID $eleve n'existe pas.", Response::HTTP_BAD_REQUEST);
            }
            $eleve->addClass($classe);
        }

        $em->persist($classe);

        $em->flush();

        $jsonClasse = $serializer->serialize($classe, 'json', ['groups' => 'getClasses']);
        
        $location = $urlGenerator->generate('detailClasse', ['id' => $classe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonClasse, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * @OA\Put(
    *     path="/api/classes/update/{id}",
    *     summary="Met à jour une classe",
    *     tags={"Classes"},
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
    *     @OA\RequestBody(
    *         description="Données mises à jour de la classe",
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 @OA\Property(property="nom", type="string", example="Nouveau nom de la classe"),
    *                 @OA\Property(property="ecole", type="integer", example=1)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Classe mise à jour"
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Classe non trouvée"
    *     )
    * )
    */
    #[Route('/api/classes/update/{id}', name:"updateClasse", methods:['PUT'])]
    public function updateClasse(Request $request, SerializerInterface $serializer, Classe $currentClasse, EntityManagerInterface $em, EcoleRepository $ecoleRepository): JsonResponse 
    {
        $updatedClasse = $serializer->deserialize($request->getContent(), 
                Classe::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentClasse]);
        $content = $request->toArray();
        $idEcole = $content['idEcole'] ?? -1;
        $updatedClasse->setEcole($ecoleRepository->find($idEcole));
        
        $em->persist($updatedClasse);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
