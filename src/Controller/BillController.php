<?php

namespace App\Controller;

use Dompdf\Dompdf;
use Dompdf\Options;
use App\Repository\OrderRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class BillController extends AbstractController
{
    #[Route('/editor/order/{id}/bill', name: 'app_bill')]
    public function index($id, OrderRepository $orderRepo): Response
    {
        // Récupère la commande depuis la BDD via son ID
        $order = $orderRepo->find($id);

        // Crée une instance d'Options pour configurer DomPDF
        $pdfOptions = new Options();

        // Définit la police par défaut utilisée dans le PDF
        $pdfOptions->set('defaultFont', 'Arial');

        // Instancie DomPDF en lui passant les options configurées
        $domPdf = new Dompdf($pdfOptions);

        // Génère le HTML depuis le template Twig en lui passant la commande
        // renderView() retourne le HTML sous forme de chaîne (pas de Response)
        $html = $this->renderView('bill/indexBill.html.twig', [
            'order' => $order,
        ]);

        // Charge le HTML généré dans DomPDF pour traitement
        $domPdf->loadHtml($html);

        // Convertit le HTML en PDF (calcul des pages, styles, mise en page)
        $domPdf->render();

        // Envoie le PDF au navigateur
        // 'Attachment' => false  : affiche le PDF dans le navigateur (inline)
        // 'Attachment' => true   : force le téléchargement direct du fichier
        $domPdf->stream('StyleBoutik-Facture-' . $order->getId() . '.pdf', [
            'Attachment' => false
        ]);

        // Retourne une réponse HTTP vide — DomPDF a déjà envoyé le PDF
        // directement dans le flux de sortie via stream()
        return new Response('', 200, [
            'Content-Type' => 'application/pdf'
        ]);
    }
}