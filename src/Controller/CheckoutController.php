<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Service\CartService;
use App\Service\StripeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/checkout')]
#[IsGranted('ROLE_USER')]
class CheckoutController extends AbstractController
{
    public function __construct(
        private CartService $cartService,
        private OrderRepository $orderRepository,
        private StripeService $stripe,
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
        private string $stripePublicKey,
    ) {}

    /**
     * Page de paiement : récap commande + adresse + Payment Element embarqué.
     */
    #[Route('', name: 'app_checkout', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $cart = $this->cartService->getCart();
        $session = $request->getSession();

        if ($cart->isEmpty()) {
            return $this->redirectToRoute('app_cart');
        }

        /** @var User $user */
        $user = $this->getUser();
        $address = $this->defaultAddress($user);

        if (!$address) {
            // Pas d'adresse : on renvoie vers l'onglet adresses du compte, en
            // mémorisant qu'il faudra revenir au paiement une fois l'adresse créée.
            $session->set('error', $this->translator->trans('checkout.address_required'));
            $session->set('account_tab', 'adresses');
            $session->set('checkout_redirect', true);
            return $this->redirectToRoute('app_account');
        }

        // Adresse présente : on nettoie un éventuel drapeau de retour resté en session
        $session->remove('checkout_redirect');

        // S'assure que la commande "panier" (isValid=false) est persistée
        $this->cartService->persistCart();

        return $this->render('checkout/index.html.twig', [
            'summary'         => $cart->getSummary(),
            'items'           => $cart->getItems(),
            'address'         => $address,
            'stripePublicKey' => $this->stripePublicKey,
        ]);
    }

    /**
     * Crée le PaymentIntent (montant calculé serveur) et renvoie le client_secret.
     */
    #[Route('/intent', name: 'api_checkout_intent', methods: ['POST'])]
    public function intent(Request $request): JsonResponse
    {
        if (!$this->isCsrfTokenValid('checkout_intent', (string) $request->headers->get('X-CSRF-Token'))) {
            return $this->json(['error' => $this->translator->trans('toast.invalid_data')], 403);
        }

        $cart = $this->cartService->getCart();
        if ($cart->isEmpty()) {
            return $this->json(['error' => $this->translator->trans('cart.empty')], 400);
        }

        /** @var User $user */
        $user = $this->getUser();
        if (!$this->defaultAddress($user)) {
            return $this->json(['error' => $this->translator->trans('checkout.address_required')], 400);
        }

        $this->cartService->persistCart();
        $order = $this->orderRepository->findUnvalidatedOrderByUser($user);
        if (!$order) {
            return $this->json(['error' => $this->translator->trans('cart.empty')], 400);
        }

        // Montant final (sous-total + livraison − éco-remise), TOUJOURS côté serveur
        $amountCents = (int) round($cart->getFinalTotal() * 100);

        try {
            $intent = $this->stripe->createPaymentIntent($order, $amountCents);
        } catch (\Throwable $e) {
            return $this->json(['error' => $this->translator->trans('checkout.payment_error')], 502);
        }

        $order->setStripePaymentIntentId($intent->id);
        $this->em->flush();

        return $this->json(['clientSecret' => $intent->client_secret]);
    }

    /**
     * Retour après paiement : on relit le PaymentIntent côté serveur (source de
     * vérité) et on finalise la commande si le paiement a réussi.
     */
    #[Route('/confirmation', name: 'app_checkout_confirmation', methods: ['GET'])]
    public function confirmation(Request $request): Response
    {
        $intentId = $request->query->get('payment_intent');
        if (!$intentId) {
            return $this->redirectToRoute('app_cart');
        }

        try {
            $intent = $this->stripe->retrievePaymentIntent($intentId);
        } catch (\Throwable $e) {
            return $this->render('checkout/confirmation.html.twig', ['status' => 'error', 'order' => null]);
        }

        /** @var User $user */
        $user = $this->getUser();

        // Vérification d'appartenance (le PI doit appartenir à l'utilisateur courant)
        if (($intent->metadata['user_id'] ?? null) !== (string) $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        $orderId = $intent->metadata['order_id'] ?? null;
        $order = $orderId ? $this->orderRepository->find((int) $orderId) : null;

        if (!$order || $order->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }

        // 'succeeded' = carte (immédiat) ; 'processing' = PayPal/Klarna (asynchrone).
        // En prod, un webhook confirmerait le passage 'processing' -> 'succeeded' ;
        // ici (démo, sans webhook) on considère la commande comme confirmée.
        if (in_array($intent->status, ['succeeded', 'processing'], true)) {
            // Idempotent : ne refinalise pas une commande déjà payée
            $this->cartService->markOrderPaid(
                $order,
                $intent->id,
                $intent->amount / 100,
                $this->defaultAddress($user),
            );

            return $this->render('checkout/confirmation.html.twig', [
                'status' => 'succeeded',
                'order'  => $order,
            ]);
        }

        return $this->render('checkout/confirmation.html.twig', [
            'status' => $intent->status,
            'order'  => null,
        ]);
    }

    /**
     * Adresse par défaut de l'utilisateur (sinon la première, sinon null).
     */
    private function defaultAddress(User $user): ?Address
    {
        foreach ($user->getAddresses() as $address) {
            if ($address->isDefault()) {
                return $address;
            }
        }

        return $user->getAddresses()->first() ?: null;
    }
}
