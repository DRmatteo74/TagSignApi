<?php

namespace App\Controller;

use App\Entity\Cours;
use App\Repository\ClasseRepository;
use App\Repository\CoursRepository;
use App\Repository\ParticipeRepository;
use App\Repository\SalleRepository;
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

    #[Route('/api/cours/next/{idUser}', name: 'api_next_cours', methods: ['GET'])]
    public function getNextCours($idUser, EntityManagerInterface $entityManager, ParticipeRepository $participeRepository, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): Response
    {
        // Récupérer l'utilisateur par son ID
        $utilisateur = $utilisateursRepository->find($idUser);

        if (!$utilisateur) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Récupérer la date et l'heure actuelles
        $timezone = new \DateTimeZone('Europe/Paris');

        $now = new \DateTime('now', $timezone);
        $heure = $now->format('H:i:s');

        $participeCours = $utilisateur->getParticipes();

        // Récupérer le prochain cours pour l'utilisateur
        $prochainCours  = array();
        
        foreach ($participeCours as $p) {
            $cours = $p->getCours();
            $coursDate = $cours->getDate();
            $coursHeure = $cours->getHeure();
        
            if ($coursDate->format('Y-m-d') === $now->format('Y-m-d')) {
                $coursHeureLimite = clone $coursHeure;
                $coursHeureLimite->modify('+1 hour 30 minutes');
                if ($coursHeureLimite->format('H:i:s') > $heure) {
                    $prochainCours[] = $cours;
                } elseif ($coursHeure->format('H:i:s') > $heure) {
                    $prochainCours[] = $cours;
                }
            } elseif ($coursDate >= $now) {
                $prochainCours[] = $cours;
            }
        }

        usort($prochainCours, function ($cours1, $cours2) {
            if ($cours1->getDate() === $cours2->getDate()) {
                return $cours1->getHeure() <=> $cours2->getHeure();
            }
            return $cours1->getDate() <=> $cours2->getDate();
        });

        if (!$prochainCours ) {
            return $this->json(['message' => 'Aucun cours trouvé pour l\'utilisateur donné'], Response::HTTP_NOT_FOUND);
        }
        
        // Récupérer les informations nécessaires du cours
        $idCours = $prochainCours[0]->getId();
        $nomCours = $prochainCours[0]->getNom();
        $nomSalle = $prochainCours[0]->getSalle()->getSalle();
        $dateCours = $prochainCours[0]->getDate()->format('Y-m-d');
        $heureCours = $prochainCours[0]->getHeure()->format('H:i:s');
        $presence = null;

        // Récupérer la présence de l'utilisateur au cours s'il existe
        $participe = $participeRepository->findOneBy(['cours' => $prochainCours, 'utilisateur' => $utilisateur]);
        if ($participe) {
            $presence = $participe->isPresence();
        }

        return $this->json([
            'id' => $idCours,
            'cours' => $nomCours,
            'salle' => $nomSalle,
            'date' => $dateCours,
            'heure' => $heureCours,
            'presence' => $presence,
        ], Response::HTTP_OK);
    }

    #[Route('/api/cours/getPresence/{idCours}', name: 'getPresence', methods: ['GET'])]
    public function getPresence($idCours, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): Response
    {
        // Récupérer l'utilisateur par son ID
        $cours = $coursRepository->find($idCours);

        if (!$cours) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $participeCours = $cours->getParticipes();
        $participes = array();

        foreach ($participeCours as $participe) {
            if($participe->isPresence()){
                $participes[] = $participe;
            }
        }
        
        $jsonCours = $serializer->serialize($participes, 'json', ['groups' => 'getPresence']);
        return new JsonResponse($jsonCours, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    #[Route('/api/cours/getListEleve/{idCours}', name: 'getListEleve', methods: ['GET'])]
    public function getListEleve($idCours, EntityManagerInterface $entityManager, ParticipeRepository $participeRepository, CoursRepository $coursRepository, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): Response
    {
        // Récupérer l'utilisateur par son ID
        $cours = $coursRepository->find($idCours);

        if (!$cours) {
            return $this->json(['message' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        $participeCours = $cours->getClasse()->getUtilisateurs();

        $utilisateurs = array();
    
        foreach($participeCours as $user){
            $participe = $participeRepository->findOneBy(['cours' => $cours, 'utilisateur' => $user]);
            $utilisateurs[] = [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'presence' => $participe->isPresence(),
            ];
        }
        
        $jsonCours = $serializer->serialize($utilisateurs, 'json', ['groups' => 'getParticipe']);
        return new JsonResponse($jsonCours, Response::HTTP_OK, ['accept' => 'json'], true);
    }
}
