<?php

namespace App\Service;

use App\Entity\Order;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Encapsule le client Stripe. La clé secrète est injectée depuis l'environnement
 * (STRIPE_SECRET_KEY) ; en démo on utilise les clés de test.
 */
class StripeService
{
    private ?StripeClient $client = null;

    public function __construct(private string $stripeSecretKey) {}

    /**
     * Crée le client à la demande. La clé vide est tolérée tant qu'aucun appel
     * API n'est fait (le conteneur compile même sans clé configurée).
     */
    private function client(): StripeClient
    {
        if ($this->client === null) {
            if ($this->stripeSecretKey === '') {
                throw new \RuntimeException('Clé secrète Stripe non configurée (STRIPE_SECRET_KEY).');
            }
            $this->client = new StripeClient($this->stripeSecretKey);
        }

        return $this->client;
    }

    /**
     * Crée un PaymentIntent pour une commande. Le montant (en centimes) est
     * toujours calculé côté serveur par l'appelant, jamais reçu du client.
     */
    public function createPaymentIntent(Order $order, int $amountCents): PaymentIntent
    {
        return $this->client()->paymentIntents->create([
            'amount'   => $amountCents,
            'currency' => 'eur',
            // Liste explicite des moyens voulus : carte + PayPal + Klarna.
            // (exclut Link / l'option "enregistrer", non listée ici)
            'payment_method_types' => ['card', 'paypal', 'klarna'],
            'metadata' => [
                'order_id' => (string) $order->getId(),
                'user_id'  => (string) $order->getUser()?->getId(),
            ],
        ]);
    }

    public function retrievePaymentIntent(string $id): PaymentIntent
    {
        return $this->client()->paymentIntents->retrieve($id);
    }
}
