<?php

namespace App\Controller;

use App\Repository\CoursRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Dompdf\Dompdf;
use Dompdf\Options;

use OpenApi\Annotations as OA;

/**
* @OA\Tag(name="Feuille d'appel")
*/

class FeuilleAppelPDFController extends AbstractController
{
    /**
    * @OA\Get(
    *     path="/api/appel/{coursId}",
    *     summary="Génère une feuille d'appel au format PDF",
    *     @OA\Parameter(
    *         name="coursId",
    *         in="path",
    *         description="ID du cours",
    *         required=true,
    *         @OA\Schema(
    *             type="integer",
    *             format="int64"
    *         )
    *     ),
    *     @OA\Response(
    *         response="200",
    *         description="PDF généré",
    *         @OA\MediaType(
    *             mediaType="application/pdf"
    *         )
    *     ),
    *     @OA\Response(
    *         response="404",
    *         description="Cours non trouvé"
    *     ),
    *     @OA\Response(
    *         response="500",
    *         description="Horaire non valide"
    *     )
    * )
    */
    #[Route('/api/appel/{coursId}', name: 'app_feuille_appel', methods:["GET"])]
    public function generatePdfAction(int $coursId, CoursRepository $coursRepository)
    {
        $timezone = new \DateTimeZone('Europe/Paris');

        $now = new \DateTime('now', $timezone);
        $heure = $now->format('H:i:s');


        $cours = $coursRepository->find($coursId);

        if (!$cours) {
            return new JsonResponse(['error' => 'Cours non trouvé.'], 404);
        }
        if ($cours->getDate()->format('Y-m-d') > $now->format('Y-m-d') || ($cours->getDate()->format('Y-m-d') == $now->format('Y-m-d') && $cours->getHeure()->modify('+1 hour 30 minutes')->format('H:i:s') >= $heure)) {
            return new JsonResponse(['error' => 'Horaire non valide.'], 500);
        }

        $ecole = $cours->getClasse()->getEcole()->getNom();
        $classe = $cours->getClasse()->getNom();
        $salle = $cours->getSalle()->getSalle();

        $eleves  = array();
        $intervanant = null;

        foreach ($cours->getParticipes() as $participe) {
            $user = $participe->getUtilisateur();
            if(in_array("ROLE_PROF", $user->getRoles())){
                $intervanant = $user->getNom() . " " . $user->getPrenom();
                continue;
            }else{
                $eleves[] = [
                    'nom' => $user->getNom(),
                    'prenom' => $user->getPrenom(),
                    'present' => $participe->isPresence(),
                ];
            }

        }

        // Créez le contenu HTML du PDF en utilisant les données récupérées
        $html = $this->renderView('feuille_appel_pdf/index.html.twig', [
            'ecole' => $ecole,
            'classe' => $classe,
            'cours' => $cours->getNom(),
            'intervenant' => $intervanant,
            'eleves' => $eleves,
            'salle' => $salle
        ]);

        // Configurez les options de Dompdf
        $options = new Options();
        $options->set('defaultFont', 'Arial');

        // Instanciez Dompdf avec les options configurées
        $dompdf = new Dompdf($options);

        // Chargez le contenu HTML dans Dompdf
        $dompdf->loadHtml($html);

        // Rendez le contenu HTML en PDF
        $dompdf->render();

        // Générez un nom de fichier pour le PDF
        $filename = str_replace(" ", "_", $cours->getNom()) . $cours->getDate()->format('Y-m-d') . "_" . $cours->getHeure()->format('H:i:s') . ".pdf"; 

        // Enregistrez le PDF généré dans un répertoire accessible
        $output = $dompdf->output();
        $pdfContent = $dompdf->output();
    
        // Créez une réponse Symfony avec le contenu du PDF
        $response = new Response($pdfContent);

        // Définissez les en-têtes pour spécifier que la réponse est un fichier PDF
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');

        return $response;
    }
}
