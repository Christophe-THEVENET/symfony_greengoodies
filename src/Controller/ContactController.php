<?php

namespace App\Controller;

use App\Entity\ContactMessage;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        RateLimiterFactory $contactLimiter,
    ): Response {
        $contact = new ContactMessage();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Anti-spam 1 : honeypot (champ piège rempli => bot)
            // Anti-spam 2 : rate limiting par IP
            $isBot = (string) $form->get('website')->getData() !== '';

            if (!$contactLimiter->create($request->getClientIp() ?? 'anon')->consume()->isAccepted()) {
                throw new TooManyRequestsHttpException(null, $translator->trans('contact.rate_limited'));
            }

            if (!$isBot) {
                $contact->setIpAddress($request->getClientIp());
                $em->persist($contact);
                $em->flush();
            }

            // Confirmation identique (on ne révèle pas la détection du bot)
            $request->getSession()->set('toast', $translator->trans('contact.success'));

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'contactForm' => $form,
        ]);
    }
}
