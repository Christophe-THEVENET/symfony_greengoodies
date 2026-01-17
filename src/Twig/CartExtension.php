<?php

namespace App\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', [$this, 'getCartCount']),
        ];
    }

    public function getCartCount(): int
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            if (!$request?->hasSession()) {
                return 0;
            }

            $cartData = $request->getSession()->get('cart', []);
            $count = 0;
            foreach ($cartData as $quantity) {
                $count += $quantity;
            }
            return $count;
        } catch (\Exception) {
            return 0;
        }
    }
}
