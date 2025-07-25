<?php

namespace App\EventSubscriber;

use App\Repository\OrderRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginOrderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OrderRepository $orderRepository,
        private RequestStack $requestStack
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

        $order = $this->orderRepository->findUnvalidatedOrderByUser($user);
        $session = $this->requestStack->getSession();
        if ($order && $session) {
            $session->remove('cart'); // On supprime le panier anonyme au cas ou l'utilisateur connecté avait un panier anonyme
            $session->set('cart_order_id', $order->getId());

            // Reconstituer la session 'cart' avec les produits de l'Order
            $cartData = [];
            foreach ($order->getOrderItems() as $orderItem) {
                $cartData[$orderItem->getProduct()->getId()] = $orderItem->getQuantity();
            }
            $session->set('cart', $cartData);
        }
    }
}
