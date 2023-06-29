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
    #[Route('/api/ecoles', name: 'ecole', methods:['GET'])]
    public function getAllEcoles(EcoleRepository $ecoleRepository, SerializerInterface $serializer): JsonResponse
    {
        $ecoleList = $ecoleRepository->findAll();
        $jsonEcoleList = $serializer->serialize($ecoleList, 'json', ['groups' => 'getEcole']);
        return new JsonResponse($jsonEcoleList, Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/ecoles/{id}', name: 'detailEcole', methods: ['GET'])]
    public function getDetailEcole(Ecole $ecole, SerializerInterface $serializer): JsonResponse 
    {
        $jsonEcole = $serializer->serialize($ecole, 'json', ['groups' => 'getEcole']);
        return new JsonResponse($jsonEcole, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/ecoles/delete/{id}', name: 'deleteEcole', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteEcole(Ecole $ecole, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($ecole);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/ecoles/create', name:"createEcole", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
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

    #[Route('/api/ecoles/update/{id}', name:"updateEcole", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier une école')]
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
