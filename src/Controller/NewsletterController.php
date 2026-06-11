<?php

namespace App\Controller;

use App\Entity\NewsletterSubscription;
use App\Repository\NewsletterSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class NewsletterController extends AbstractController
{
    #[Route('/newsletter/inscription', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(
        Request $request,
        EntityManagerInterface $em,
        NewsletterSubscriptionRepository $repository,
        ValidatorInterface $validator,
        TranslatorInterface $translator,
        RateLimiterFactory $newsletterLimiter,
    ): JsonResponse {
        // Anti-spam : limite par IP
        if (!$newsletterLimiter->create($request->getClientIp() ?? 'anon')->consume()->isAccepted()) {
            return $this->json([
                'success' => false,
                'message' => $translator->trans('newsletter.rate_limited'),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $data = json_decode($request->getContent(), true);
        $email = is_array($data) ? trim((string) ($data['email'] ?? '')) : '';

        // Validation de l'email
        $violations = $validator->validate($email, [
            new Assert\NotBlank(message: 'newsletter.invalid'),
            new Assert\Email(message: 'newsletter.invalid'),
        ]);
        if (count($violations) > 0) {
            return $this->json([
                'success' => false,
                'message' => $translator->trans('newsletter.invalid'),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Vérifie si l'email existe déjà
        if ($repository->existsByEmail($email)) {
            return $this->json([
                'success' => false,
                'status' => 'already',
                'message' => $translator->trans('newsletter.already'),
            ]);
        }

        $subscription = (new NewsletterSubscription())->setEmail($email);
        $em->persist($subscription);
        $em->flush();

        return $this->json([
            'success' => true,
            'status' => 'subscribed',
            'message' => $translator->trans('newsletter.subscribed'),
        ]);
    }
}
