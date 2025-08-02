<?php

namespace App\EventSubscriber;

use App\Repository\OrderRepository;
use App\Service\CartService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginOrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OrderRepository $orderRepository,
        private RequestStack $requestStack,
        private CartService $cartService
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            'security.interactive_login' => 'onInteractiveLogin',
        ];
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user) {
            return;
        }

        $orderInvalid = $this->orderRepository->findUnvalidatedOrderByUser($user);
        $session = $this->requestStack->getSession();
        if ($orderInvalid && $session) {
            $session->remove('cart'); // On supprime le panier anonyme au cas ou l'utilisateur connecté avait un panier anonyme
            $session->set('cart_order_id', $orderInvalid->getId());

            // Reconstituer la session 'cart' avec les produits de l'Order
            $cartData = [];
            foreach ($orderInvalid->getOrderItems() as $orderInvalidItem) {
                $cartData[$orderInvalidItem->getProduct()->getId()] = $orderInvalidItem->getQuantity();
            }
            $session->set('cart', $cartData);
        }

        if (!$orderInvalid && $session) {
            $cartData = $session->get('cart', []);
            if (!empty($cartData)) {
               
                $this->cartService->persistCart(); 
                $order = $this->orderRepository->findUnvalidatedOrderByUser($user);
                if ($order) {
                    $session->set('cart_order_id', $order->getId());
                }
            }
        }
    }
}
