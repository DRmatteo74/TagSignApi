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
    #[Route('/api/participes', name: 'participes', methods:['GET'])]
    public function getAllParticipes(ParticipeRepository $participeRepository, SerializerInterface $serializer): JsonResponse
    {
        $participeList = $participeRepository->findAll();
        $jsonParticipeList = $serializer->serialize($participeList, 'json', ['groups' => 'getParticipe']);
        return new JsonResponse($jsonParticipeList, Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/participes/{id}', name: 'detailParticipe', methods: ['GET'])]
    public function getDetailParticipe(Participe $participe, SerializerInterface $serializer): JsonResponse 
    {
        $jsonParticipe = $serializer->serialize($participe, 'json', ['groups' => 'getParticipe']);
        return new JsonResponse($jsonParticipe, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/participes/delete/{id}', name: 'deleteParticipe', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteParticipe(Participe $participe, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($participe);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

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
