<?php

namespace App\Controller;

use App\Entity\Salle;
use App\Repository\SalleRepository;
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

use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Salles")
*/

class SalleController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/salles",
    *     summary="Récupère toutes les salles",
    *     tags={"Salles"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des salles",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="salle", type="string"),
    *                 @OA\Property(property="lecteur", type="string")
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/salles', name: 'salles', methods:['GET'])]
    public function getAllSalles(SalleRepository $salleRepository, SerializerInterface $serializer): JsonResponse
    {
        $salleList = $salleRepository->findAll();
        $jsonsalleList = $serializer->serialize($salleList, 'json', ['groups' => 'getSalles']);
        return new JsonResponse($jsonsalleList, Response::HTTP_OK, [], true);
    }
    
    /**
    * @OA\Get(
    *     path="/api/salles/{id}",
    *     summary="Récupère les détails d'une salle",
    *     tags={"Salles"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de la salle",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Détail de la salle",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="salle", type="string"),
    *             @OA\Property(property="lecteur", type="string")
    *         )
    *     )
    * )
    */
    #[Route('/api/salles/{id}', name: 'detailSalle', methods: ['GET'])]
    public function getDetailSalle(Salle $salle, SerializerInterface $serializer): JsonResponse 
    {
        $jsonSalle = $serializer->serialize($salle, 'json', ['groups' => 'getSalles']);
        return new JsonResponse($jsonSalle, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Delete(
    *     path="/api/salles/delete/{id}",
    *     summary="Supprime une salle",
    *     tags={"Salles"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de la salle",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Salle supprimée avec succès"
    *     )
    * )
    */
    #[Route('/api/salles/delete/{id}', name: 'deleteSalle', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une salle')]
    public function deleteSalle(Salle $salle, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($salle);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/salles/create",
    *     summary="Crée une nouvelle salle",
    *     tags={"Salles"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="nom", type="string", example="Salle A")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="201",
    *         description="Créé avec succès",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="salle", type="string"),
    *             @OA\Property(property="lecteur", type="string")
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
    #[Route('/api/salles/create', name:"createSalle", methods: ['POST'])]
    public function createSalle(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse 
    {
        $salle = $serializer->deserialize($request->getContent(), Salle::class, 'json');
                
        //Vérifie les valeurs
        $errors = $validator->validate($salle);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($salle);
        $em->flush();

        $jsonSalle = $serializer->serialize($salle, 'json', ['groups' => 'getSalles']);
        
        $location = $urlGenerator->generate('detailSalle', ['id' => $salle->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonSalle, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * @OA\Put(
    *     path="/api/salles/update/{id}",
    *     summary="Met à jour une salle",
    *     tags={"Salles"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de la salle",
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
    *                 @OA\Property(property="nom", type="string", example="Salle B")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Salle mis à jour avec succès"
    *     )
    * )
    */
    #[Route('/api/salles/update/{id}', name:"updateSalle", methods:['PUT'])]
    public function updateSalle(Request $request, SerializerInterface $serializer, Salle $currentSalle, EntityManagerInterface $em): JsonResponse 
    {
        $updatedSalle = $serializer->deserialize($request->getContent(), 
            Salle::class, 
            'json', 
            [AbstractNormalizer::OBJECT_TO_POPULATE => $currentSalle]);
                
        $em->persist($updatedSalle);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
