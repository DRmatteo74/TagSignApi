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
    #[Route('/api/planning/{id}', name: 'api_planning', methods: ['GET'])]
    public function getPlanning(int $id, UtilisateursRepository $utilisateursRepository, ParticipeRepository $participeRepository, ClasseRepository $classeRepository): JsonResponse
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
