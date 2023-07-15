<?php

namespace App\Controller;

use App\Repository\ParticipeRepository;
use App\Repository\SalleRepository;
use App\Repository\UtilisateursRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Length;

use function PHPSTORM_META\type;
use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Badge")
*/
class BadgeController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/badge/{id}",
    *     summary="Obtient les détails de l'utilisateur par badge",
    *     tags={"Badge"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID du badge",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Détails de l'utilisateur par badge",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer"),
    *             @OA\Property(property="login", type="string"),
    *             @OA\Property(property="nom", type="string"),
    *             @OA\Property(property="prenom", type="string"),
    *             @OA\Property(property="roles", type="array", @OA\Items(type="string")),
    *             @OA\Property(property="badge", type="string")
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Utilisateur introuvable"
    *     )
    * )
    */
    #[Route('/api/badge/{id}', name: 'detailBadgeUser', methods: ['GET'])]
    public function getDetailBadge($id, UtilisateursRepository $utilisateursRepository, SerializerInterface $serializer): JsonResponse 
    {
        $user = $utilisateursRepository->findOneBy(['badge' => $id]); // Remplacez "findByBadge" par la méthode appropriée pour récupérer l'utilisateur par son badge

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }
        $jsonClasse = $serializer->serialize($user, 'json', ['groups' => 'getUser']);
        return new JsonResponse($jsonClasse, Response::HTTP_OK, ['accept' => 'json'], true);
    }

    /**
    * @OA\Get(
    *     path="/api/badge/cours/{idBadge}/{idSalle}",
    *     summary="Scan de badge",
    *     tags={"Badge"},
    *     @OA\Parameter(
    *         name="idBadge",
    *         in="path",
    *         description="ID du badge",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Parameter(
    *         name="idSalle",
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
    *         description="Présence enregistrée avec succès",
    *         @OA\MediaType(
    *             mediaType="application/json"
    *         )
    *     ),
    *     @OA\Response(
    *         response="400",
    *         description="L'utilisateur n'est pas inscrit au cours"
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Utilisateur ou salle introuvable"
    *     )
    * )
    */
    #[Route('/api/badge/cours/{idBadge}/{idSalle}', name: 'badgeScan', methods: ['GET'])]
    public function scanBadge($idBadge, $idSalle, UtilisateursRepository $userRepository, SalleRepository $salleRepository, ParticipeRepository $participeRepository, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        // Vérifier si l'utilisateur existe
        $user = $userRepository->findOneBy(['badge' => $idBadge]);

        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $timezone = new \DateTimeZone('Europe/Paris');

        $currentDateTime = new \DateTime('now', $timezone);
        $salle = $salleRepository->findOneBy(['lecteur' => $idSalle]);

        if (!$salle) {
            return new JsonResponse(['message' => 'Salle introuvable'], Response::HTTP_NOT_FOUND);
        }

        // Filtrer les cours de la date actuelle
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');
        $cours = $salle->getCours()->filter(function ($c) use ($currentDate) {
            return $c->getDate() && $c->getDate()->format('Y-m-d') === $currentDate;
        });

        if ($cours->isEmpty()) {
            return new JsonResponse(['message' => "Aucun cours prévu dans cette salle aujourd'hui"], Response::HTTP_NOT_FOUND);
        }

        $matchingCours = array();
        // Rechercher le cours correspondant à l'heure actuelle
        foreach($cours as $c){
            $coursDate = $c->getDate();
            $coursHeure = $c->getHeure();

            if ($coursDate->format('Y-m-d') === $currentDateTime->format('Y-m-d')) {
                $coursHeureLimite = clone $coursHeure;
                $coursHeureLimite->modify('+1 hour 30 minutes');
                if ($coursHeureLimite->format('H:i:s') >= $currentTime && $coursHeure->format('H:i:s') <= $currentTime) {
                    $matchingCours[] = $c;
                } 
            }
        }

        /*$matchingCours = $cours->filter(function ($c) use ($currentDateTime) {
            $coursStartTime = $c->getHeure()->modify('-15 minutes')->format('H:i:s');
            $coursEndTime = (clone $c->getHeure())->modify('+1 hour 45 minutes')->format('H:i:s');
            
            $currentTime = $currentDateTime->format('H:i:s');
        
            return $currentTime >= $coursStartTime && $currentTime <= $coursEndTime;
        });*/
        
        if (!$matchingCours) {
            return new JsonResponse(['message' => "Aucun cours prévu dans cette salle à cette heure"], Response::HTTP_NOT_FOUND);
        }

        // Vérifier si l'utilisateur est inscrit au cours
        $nonInscrit = true;
        foreach ($matchingCours as $c) {
            $participe = $participeRepository->findOneBy(['cours' => $c, 'utilisateur' => $user]);

            if (!$participe) {
              continue;  
            }
            $nonInscrit = false;
            // Mettre à jour la présence de l'utilisateur
            $participe->setPresence(true);
            $participe->setHeureBadgeage($currentDateTime);

            $em->persist($participe);
            $em->flush();
        }
        
        if($nonInscrit == true){
            return new JsonResponse(['message' => 'L\'utilisateur n\'est pas inscrit au cours', 'cours' => $matchingCours], Response::HTTP_BAD_REQUEST);
        }
        $jsonClasseList = $serializer->serialize($matchingCours, 'json', ['groups' => 'getCours']);
        return new JsonResponse(['message' => 'Présence enregistrée avec succès', 'cours'=>$jsonClasseList], Response::HTTP_OK);
    }
}


