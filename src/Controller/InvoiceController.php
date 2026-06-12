<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/mon-compte/commande')]
#[IsGranted('ROLE_USER')]
class InvoiceController extends AbstractController
{
    /**
     * Génère et renvoie la facture PDF détaillée d'une commande.
     */
    #[Route('/{id}/facture', name: 'app_order_invoice', methods: ['GET'])]
    public function invoice(Order $order): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Sécurité : la commande doit appartenir à l'utilisateur et être validée
        if ($order->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
        if (!$order->getIsValid()) {
            throw $this->createNotFoundException();
        }

        // Sous-total (somme des lignes) et ajustement (livraison/remise non détaillés)
        $subtotal = 0.0;
        foreach ($order->getOrderItems() as $item) {
            $subtotal += (float) $item->getTotalPrice();
        }
        $adjustment = round((float) $order->getTotalAmount() - $subtotal, 2);

        $html = $this->renderView('account/invoice.html.twig', [
            'order'      => $order,
            'subtotal'   => $subtotal,
            'adjustment' => $adjustment,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans'); // gère € et accents
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="facture-%s.pdf"', $order->getOrderNumber()),
        ]);
    }
}
