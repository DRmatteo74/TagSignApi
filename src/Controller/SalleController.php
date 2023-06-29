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
    #[Route('/api/salles', name: 'salles', methods:['GET'])]
    public function getAllSalles(SalleRepository $salleRepository, SerializerInterface $serializer): JsonResponse
    {
        $salleList = $salleRepository->findAll();
        $jsonsalleList = $serializer->serialize($salleList, 'json', ['groups' => 'getSalles']);
        return new JsonResponse($jsonsalleList, Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/salles/{id}', name: 'detailSalle', methods: ['GET'])]
    public function getDetailSalle(Salle $salle, SerializerInterface $serializer): JsonResponse 
    {
        $jsonSalle = $serializer->serialize($salle, 'json', ['groups' => 'getSalles']);
        return new JsonResponse($jsonSalle, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/salles/delete/{id}', name: 'deleteSalle', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une salle')]
    public function deleteSalle(Salle $salle, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($salle);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/salles/create', name:"createSalle", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une salle')]
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

    #[Route('/api/salles/update/{id}', name:"updateSalle", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', "ROLE_AP", message: 'Vous n\'avez pas les droits suffisants pour créer une salle')]
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
