<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
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
* @OA\Tag(name="Cours")
*/
class CoursController extends AbstractController
{
    #[Route('/api/cours', name: 'cours', methods:['GET'])]
    public function getAllCours(CoursRepository $coursRepository, SerializerInterface $serializer): JsonResponse
    {
        $coursList = $coursRepository->findAll();
        $jsonCoursList = $serializer->serialize($coursList, 'json', ['groups' => 'getCours']);
        return new JsonResponse($jsonCoursList, Response::HTTP_OK, [], true);
    }
    
    #[Route('/api/cours/{id}', name: 'detailCours', methods: ['GET'])]
    public function getDetailCours(Cours $cours, SerializerInterface $serializer): JsonResponse 
    {
        $jsonCours = $serializer->serialize($cours, 'json', ['groups' => 'getCours']);
        return new JsonResponse($jsonCours, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/cours/delete/{id}', name: 'deleteCours', methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function deleteCours(Cours $cours, EntityManagerInterface $em): JsonResponse 
    {
        $em->remove($cours);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/cours/create', name:"createCours", methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer une école')]
    public function createCours(Request $request, SerializerInterface $serializer, EntityManagerInterface $em, 
    UrlGeneratorInterface $urlGenerator, ValidatorInterface $validator, SalleRepository $salleRepository, ClasseRepository $classeRepository): JsonResponse 
    {
        $cours = $serializer->deserialize($request->getContent(), Cours::class, 'json');
        
        $content = $request->toArray();
        $idSalle = $content['idSalle'] ?? -1;
        $cours->setSalle($salleRepository->find($idSalle));

        $idClasse = $content['idClasse'] ?? -1;
        $cours->setClasse($classeRepository->find($idClasse));


        //Vérifie les valeurs
        $errors = $validator->validate($cours);
        if($errors->count()>0){
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $em->persist($cours);
        $em->flush();

        $jsonCours = $serializer->serialize($cours, 'json', ['groups' => 'getCours']);
        
        $location = $urlGenerator->generate('detailCours', ['id' => $cours->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonCours, Response::HTTP_CREATED, ["Location" => $location], true);
    }

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
}
