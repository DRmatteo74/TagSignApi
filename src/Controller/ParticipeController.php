<?php

namespace App\Controller;

use App\Entity\Participe;
use App\Repository\CoursRepository;
use App\Repository\ParticipeRepository;
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

use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Participes")
*/

class ParticipeController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/participes",
    *     summary="Récupère tous les participes",
    *     tags={"Participes"},
    *     @OA\Response(
    *         response="200",
    *         description="Liste des participations",
    *         @OA\JsonContent(
    *             type="array",
    *             @OA\Items(
    *                 @OA\Property(property="id", type="integer"),
    *                 @OA\Property(
    *                     property="cours",
    *                     type="object",
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string"),
    *                     @OA\Property(property="date", type="string", format="date-time"),
    *                     @OA\Property(property="heure", type="string", format="date-time"),
    *                     @OA\Property(property="distanciel", type="boolean"),
    *                     @OA\Property(
    *                         property="salle",
    *                         type="object",
    *                         @OA\Property(property="id", type="integer"),
    *                         @OA\Property(property="salle", type="string"),
    *                         @OA\Property(property="lecteur", type="string")
    *                     ),
    *                     @OA\Property(
    *                         property="classe",
    *                         type="object",
    *                         @OA\Property(property="id", type="integer"),
    *                         @OA\Property(property="nom", type="string"),
    *                         @OA\Property(
    *                             property="ecole",
    *                             type="object",
    *                             @OA\Property(property="id", type="integer"),
    *                             @OA\Property(property="nom", type="string")
    *                         )
    *                     )
    *                 ),
    *                 @OA\Property(
    *                     property="utilisateur",
    *                     type="object",
    *                     @OA\Property(property="id", type="integer"),
    *                     @OA\Property(property="nom", type="string"),
    *                     @OA\Property(property="prenom", type="string"),
    *                     @OA\Property(property="badge", type="string", nullable=true)
    *                 ),
    *                 @OA\Property(property="presence", type="boolean"),
    *                 @OA\Property(property="heure_badgeage", type="string", format="date-time", nullable=true),
    *                 @OA\Property(property="justificatif", type="string", nullable=true),
    *                 @OA\Property(property="justificatifValide", type="boolean", nullable=true)
    *             )
    *         )
    *     )
    * )
    */
    #[Route('/api/participes', name: 'participes', methods:['GET'])]
    public function getAllParticipes(ParticipeRepository $participeRepository, SerializerInterface $serializer): JsonResponse
    {
        $participeList = $participeRepository->findAll();
        $jsonParticipeList = $serializer->serialize($participeList, 'json', ['groups' => 'getParticipe']);
        return new JsonResponse($jsonParticipeList, Response::HTTP_OK, [], true);
    }
    
    /**
    * @OA\Get(
    *     path="/api/participes/{id}",
    *     summary="Récupère les détails d'un participe",
    *     tags={"Participes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du participe",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Succès",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(
    *                  property="cours",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string"),
    *                  @OA\Property(property="date", type="string", format="date-time"),
    *                  @OA\Property(property="heure", type="string", format="date-time"),
    *                  @OA\Property(property="distanciel", type="boolean"),
    *                  @OA\Property(
    *                      property="salle",
    *                      type="object",
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="salle", type="string"),
    *                      @OA\Property(property="lecteur", type="string")
    *                  ),
    *                  @OA\Property(
    *                      property="classe",
    *                      type="object",
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="nom", type="string"),
    *                      @OA\Property(
    *                          property="ecole",
    *                          type="object",
    *                          @OA\Property(property="id", type="integer"),
    *                          @OA\Property(property="nom", type="string")
    *                      )
    *                  )
    *              ),
    *              @OA\Property(
    *                  property="utilisateur",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string"),
    *                  @OA\Property(property="prenom", type="string"),
    *                  @OA\Property(property="badge", type="string", nullable=true)
    *              ),
    *              @OA\Property(property="presence", type="boolean"),
    *              @OA\Property(property="heure_badgeage", type="string", format="date-time", nullable=true),
    *              @OA\Property(property="justificatif", type="string", nullable=true),
    *              @OA\Property(property="justificatifValide", type="boolean", nullable=true)
    *         )
    *     )
    * )
    */
    #[Route('/api/participes/{id}', name: 'detailParticipe', methods: ['GET'])]
    public function getDetailParticipe(Participe $participe, SerializerInterface $serializer): JsonResponse 
    {
        $jsonParticipe = $serializer->serialize($participe, 'json', ['groups' => 'getParticipe']);
        return new JsonResponse($jsonParticipe, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Delete(
    *     path="/api/participes/delete/{id}",
    *     summary="Supprime un participe",
    *     tags={"Participes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du participe",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Participe supprimé avec succès"
    *     )
    * )
    */
    #[Route('/api/participes/delete/{id}', name: 'deleteParticipe', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteParticipe(Participe $participe, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($participe);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    /**
    * @OA\Post(
    *     path="/api/participes/create",
    *     summary="Crée un nouveau participe",
    *     tags={"Participes"},
    *     @OA\RequestBody(
    *         required=true,
    *         @OA\MediaType(
    *             mediaType="application/json",
    *             @OA\Schema(
    *                 type="object",
    *                 @OA\Property(property="cours", type="integer", example=1),
    *                 @OA\Property(property="utilisateur", type="integer", example=1),
    *                 @OA\Property(property="presence", type="boolean", example=true),
    *                 @OA\Property(property="heure_badgeage", type="string", format="time", example="09:00:00"),
    *                 @OA\Property(property="justificatif", type="string", example="Justificatif"),
    *                 @OA\Property(property="justificatifValide", type="boolean", example=true)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="201",
    *         description="Créé avec succès",
    *         @OA\JsonContent(
    *              @OA\Property(property="id", type="integer"),
    *              @OA\Property(
    *                  property="cours",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string"),
    *                  @OA\Property(property="date", type="string", format="date-time"),
    *                  @OA\Property(property="heure", type="string", format="date-time"),
    *                  @OA\Property(property="distanciel", type="boolean"),
    *                  @OA\Property(
    *                      property="salle",
    *                      type="object",
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="salle", type="string"),
    *                      @OA\Property(property="lecteur", type="string")
    *                  ),
    *                  @OA\Property(
    *                      property="classe",
    *                      type="object",
    *                      @OA\Property(property="id", type="integer"),
    *                      @OA\Property(property="nom", type="string"),
    *                      @OA\Property(
    *                          property="ecole",
    *                          type="object",
    *                          @OA\Property(property="id", type="integer"),
    *                          @OA\Property(property="nom", type="string")
    *                      )
    *                  )
    *              ),
    *              @OA\Property(
    *                  property="utilisateur",
    *                  type="object",
    *                  @OA\Property(property="id", type="integer"),
    *                  @OA\Property(property="nom", type="string"),
    *                  @OA\Property(property="prenom", type="string"),
    *                  @OA\Property(property="badge", type="string", nullable=true)
    *              ),
    *              @OA\Property(property="presence", type="boolean"),
    *              @OA\Property(property="heure_badgeage", type="string", format="date-time", nullable=true),
    *              @OA\Property(property="justificatif", type="string", nullable=true),
    *              @OA\Property(property="justificatifValide", type="boolean", nullable=true)
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
    #[Route('/api/participes/create', name:"createParticipe", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function createParticipe(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository): JsonResponse 
    {
        $participe = $serializer->deserialize($request->getContent(), Participe::class, 'json');
        
        $content = $request->toArray();
        $idCours = $content['idCours'] ?? -1;
        $participe->setSalle($coursRepository->find($idCours));

        $idUser = $content['idUser'] ?? -1;
        $participe->setClasse($utilisateursRepository->find($idUser));


        //Vérifie les valeurs
        $errors = $validator->validate($participe);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($participe);
        $em->flush();

        $jsonParticipe = $serializer->serialize($participe, 'json', ['groups' => 'getParticipe']);
        
        $location = $urlGenerator->generate('detailParticipe', ['id' => $participe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonParticipe, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    /**
    * @OA\Put(
    *     path="/api/participes/update/{id}",
    *     summary="Met à jour un participe",
    *     tags={"Participes"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du participe",
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
    *                 @OA\Property(property="cours", type="integer", example=2),
    *                 @OA\Property(property="utilisateur", type="integer", example=2),
    *                 @OA\Property(property="presence", type="boolean", example=false),
    *                 @OA\Property(property="heure_badgeage", type="string", format="time", example="10:00:00"),
    *                 @OA\Property(property="justificatif", type="string", example="Nouveau justificatif"),
    *                 @OA\Property(property="justificatifValide", type="boolean", example=false)
    *             )
    *         )
    *     ),
    *     @OA\Response(
    *         response="204",
    *         description="Aucun contenu"
    *     )
    * )
    */
    #[Route('/api/participes/update/{id}', name:"updateParticipe", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une école')]
    public function updateParticipe(Request $request, SerializerInterface $serializer, Participe $currentParticipe, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, EntityManagerInterface $em): JsonResponse 
    {
        $updatedParticipe = $serializer->deserialize($request->getContent(), 
                Participe::class, 
                'json', 
                [AbstractNormalizer::OBJECT_TO_POPULATE => $currentParticipe]);
        
        $content = $request->toArray();
        $idCours = $content['idCours'] ?? -1;
        $updatedParticipe->setSalle($coursRepository->find($idCours));
        
        $idUser = $content['idUser'] ?? -1;
        $updatedParticipe->setClasse($utilisateursRepository->find($idUser));

        $em->persist($updatedParticipe);
        $em->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
