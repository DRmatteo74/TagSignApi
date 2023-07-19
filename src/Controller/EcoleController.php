<?php

namespace App\Controller;

use App\Entity\Ecole;
use App\Repository\EcoleRepository;
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
* @OA\Tag(name="Ecoles")
*/

class EcoleController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/ecoles",
    *     summary="Récupère toutes les écoles",
    *     tags={"Ecoles"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des écoles",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(property="nom", type="string"),
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/ecoles', name: 'ecole', methods:['GET'])]
    public function getAllEcoles(EcoleRepository $ecoleRepository, SerializerInterface $serializer): JsonResponse
    {
        $ecoleList = $ecoleRepository->findAll();
        $jsonEcoleList = $serializer->serialize($ecoleList, 'json', ['groups' => 'getEcole']);
        return new JsonResponse($jsonEcoleList, Response::HTTP_OK, [], true);
    }
    
    /**
    * @OA\Get(
    *     path="/api/ecoles/{id}",
    *     summary="Récupère les détails d'une école",
    *     tags={"Ecoles"},
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
    *         description="Détail de l'école",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(property="nom", type="string"),
    *         )
    *     )
    * )
    */
    #[Route('/api/ecoles/{id}', name: 'detailEcole', methods: ['GET'])]
    public function getDetailEcole(Ecole $ecole, SerializerInterface $serializer): JsonResponse 
    {
        $jsonEcole = $serializer->serialize($ecole, 'json', ['groups' => 'getEcole']);
        return new JsonResponse($jsonEcole, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Delete(
    *     path="/api/ecoles/delete/{id}",
    *     summary="Supprime une école",
    *     tags={"Ecoles"},
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
    *         response="204",
    *         description="Ecole supprimé avec succès"
    *     )
    * )
    */
    #[Route('/api/ecoles/delete/{id}', name: 'deleteEcole', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteEcole(Ecole $ecole, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($ecole);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/ecoles/create",
    *     summary="Crée une nouvelle école",
    *     tags={"Ecoles"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="nom", type="string", example="Nom de l'école")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="201",
    *         description="Créé avec succès",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(property="nom", type="string"),
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
    #[Route('/api/ecoles/create', name:"createEcole", methods: ['POST'])]
    public function createEcole(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator): JsonResponse 
    {
        $ecole = $serializer->deserialize($request->getContent(), Ecole::class, 'json');
        
        //Vérifie les valeurs
        $errors = $validator->validate($ecole);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($ecole);
        $em->flush();

        $jsonEcole = $serializer->serialize($ecole, 'json', ['groups' => 'getEcole']);
        
        $location = $urlGenerator->generate('detailEcole', ['id' => $ecole->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonEcole, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * @OA\Put(
    *     path="/api/ecoles/update/{id}",
    *     summary="Met à jour une école",
    *     tags={"Ecoles"},
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
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="nom", type="string", example="Nouveau nom de l'école")
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Ecole mis à jour avec succès"
    *     )
    * )
    */
    #[Route('/api/ecoles/update/{id}', name:"updateEcole", methods:['PUT'])]
    public function updateEcole(Request $request, SerializerInterface $serializer, Ecole $currentEcole, EntityManagerInterface $em): JsonResponse 
    {
        $updatedEcole = $serializer->deserialize($request->getContent(), 
                Ecole::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentEcole]);
        
        $em->persist($updatedEcole);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
