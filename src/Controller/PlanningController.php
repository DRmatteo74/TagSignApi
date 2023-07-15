<?php

namespace App\Controller;

use App\Entity\Participe;
use App\Repository\ClasseRepository;
use App\Repository\ParticipeRepository;
use App\Repository\UtilisateursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Planning")
*/
class PlanningController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/planning/{id}",
    *     summary="Récupère le planning d'un utilisateur",
    *     tags={"Planning"},
    *     @OA\Parameter(
    *         name="id",
    *         in="path",
    *         description="ID de l'utilisateur",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="Liste des cours pour le planning",
    *         @OA\JsonContent(
    *             @OA\Property(property="id", type="integer", example=1),
    *             @OA\Property(property="nom", type="string", example="Nom du cours"),
    *             @OA\Property(property="date", type="string", format="date", example="2023-07-01"),
    *             @OA\Property(property="heure", type="string", format="time", example="09:00:00"),
    *             @OA\Property(property="distanciel", type="boolean", example=true),
    *             @OA\Property(property="salle", type="string", example="Salle A"),
    *             @OA\Property(property="classe", type="string", example="Classe A")
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Utilisateur non trouvé",
    *         @OA\JsonContent(
    *             @OA\Property(property="error", type="string", example="Utilisateur non trouvé.")
    *         )
    *     )
    * )
    */
    #[Route('/api/planning/{id}', name: 'api_planning', methods: ['GET'])]
    public function getPlanning(int $id, UtilisateursRepository $utilisateursRepository): JsonResponse
    {
        $user = $utilisateursRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $cours = $user->getParticipes()->map(function (Participe $participe) {
            return $participe->getCours();
        });

        $coursData = [];
        foreach ($cours as $coursItem) {
            $coursData[] = [
                'id' => $coursItem->getId(),
                'nom' => $coursItem->getNom(),
                'date' => $coursItem->getDate(),
                'heure' => $coursItem->getHeure(),
                'distanciel' => $coursItem->isDistanciel(),
                'salle' => $coursItem->getSalle()->getSalle(),
                'classe' => $coursItem->getClasse()->getNom(),
            ];
        }

        return new JsonResponse($coursData);
    }

}
