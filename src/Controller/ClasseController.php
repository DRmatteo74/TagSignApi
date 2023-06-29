<?php

namespace App\Controller;

use App\Entity\Classe;
use App\Repository\ClasseRepository;
use App\Repository\EcoleRepository;
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
    #[Route('/api/classes', name: 'classe', methods:['GET'])]
    public function getAllClasses(ClasseRepository $classeRepository, SerializerInterface $serializer): JsonResponse
    {
        $classeList = $classeRepository->findAll();
        $jsonClasseList = $serializer->serialize($classeList, 'json', ['groups' => 'getClasses']);
        return new JsonResponse($jsonClasseList, Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/classes/{id}', name: 'detailClasse', methods: ['GET'])]
    public function getDetailClasse(Classe $classe, SerializerInterface $serializer): JsonResponse 
    {
        $jsonClasse = $serializer->serialize($classe, 'json', ['groups' => 'getClasses']);
        return new JsonResponse($jsonClasse, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/classes/delete/{id}', name: 'deleteClasse', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une classe')]
    public function deleteClasse(Classe $classe, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($classe);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/classes/create', name:"createClasse", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une classe')]
    public function createClasse(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, EcoleRepository $ecoleRepository, ValidatorInterface $validator): JsonResponse 
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

        $em->persist($classe);
        $em->flush();

        $jsonClasse = $serializer->serialize($classe, 'json', ['groups' => 'getClasses']);
        
        $location = $urlGenerator->generate('detailClasse', ['id' => $classe->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonClasse, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route('/api/classes/update/{id}', name:"updateClasse", methods:['PUT'])]
    #[IsGranted('ROLE_ADMIN', "ROLE_AP", message: 'Vous n\'avez pas les droits suffisants pour créer une classe')]
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
