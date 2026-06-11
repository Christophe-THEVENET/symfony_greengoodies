<?php
// src/Controller/AccountController.php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\User;
use App\Form\AddressType;
use App\Form\ChangePasswordType;
use App\Form\ProfileType;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/mon-compte')]
#[IsGranted('ROLE_USER')]
class AccountController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private TokenStorageInterface $tokenStorage,
        private CsrfTokenManagerInterface $csrfTokenManager,
        private TranslatorInterface $translator,
        private UserPasswordHasherInterface $passwordHasher,
        private RequestStack $requestStack,
    ) {}

    #[Route('/', name: 'app_account')]
    public function index(Request $request, OrderRepository $orderRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // --- Formulaire profil (nom / email) ---
        $profileForm = $this->createForm(ProfileType::class, $user);
        $profileForm->handleRequest($request);
        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            $this->em->flush();
            return $this->toastRedirect('toast.profile_updated', 'profil');
        }

        // --- Formulaire mot de passe ---
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $passwordForm->get('plainPassword')->getData()));
            $this->em->flush();
            return $this->toastRedirect('toast.password_updated', 'profil');
        }

        // --- Formulaire ajout d'adresse ---
        $address = new Address();
        $addressForm = $this->createForm(AddressType::class, $address);
        $addressForm->handleRequest($request);
        if ($addressForm->isSubmitted() && $addressForm->isValid()) {
            $address->setUser($user);
            $this->applyDefault($user, $address);
            $this->em->persist($address);
            $this->em->flush();
            return $this->toastRedirect('toast.address_added', 'adresses');
        }

        return $this->renderAccount($user, $orderRepository, $profileForm, $passwordForm, $addressForm, $request);
    }

    #[Route('/adresse/{id}/modifier', name: 'app_address_edit', methods: ['GET', 'POST'])]
    public function editAddress(Request $request, Address $address, OrderRepository $orderRepository): Response
    {
        $this->denyUnlessOwner($address);

        $addressForm = $this->createForm(AddressType::class, $address);
        $addressForm->handleRequest($request);
        if ($addressForm->isSubmitted() && $addressForm->isValid()) {
            $this->applyDefault($this->getUser(), $address);
            $this->em->flush();
            return $this->toastRedirect('toast.address_updated', 'adresses');
        }

        /** @var User $user */
        $user = $this->getUser();
        $profileForm = $this->createForm(ProfileType::class, $user);
        $passwordForm = $this->createForm(ChangePasswordType::class);

        return $this->renderAccount($user, $orderRepository, $profileForm, $passwordForm, $addressForm, $request, $address);
    }

    #[Route('/adresse/{id}/supprimer', name: 'app_address_delete', methods: ['POST'])]
    public function deleteAddress(Request $request, Address $address): Response
    {
        $this->denyUnlessOwner($address);

        if (!$this->isCsrfTokenValid('delete_address_' . $address->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $this->em->remove($address);
        $this->em->flush();

        return $this->toastRedirect('toast.address_deleted', 'adresses');
    }

    #[Route('/adresse/{id}/defaut', name: 'app_address_default', methods: ['POST'])]
    public function setDefaultAddress(Request $request, Address $address): Response
    {
        $this->denyUnlessOwner($address);

        if (!$this->isCsrfTokenValid('default_address_' . $address->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $address->setIsDefault(true);
        $this->applyDefault($this->getUser(), $address);
        $this->em->flush();

        return $this->toastRedirect('toast.address_default_set', 'adresses');
    }

    #[Route('/supprimer', name: 'app_account_delete', methods: ['POST'])]
    public function delete(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('delete_account', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $user = $this->getUser();

        // Déconnexion avant suppression, puis invalidation de la session
        $this->tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        $this->em->remove($user);
        $this->em->flush();

        $request->getSession()->set('toast', $this->translator->trans('toast.account_deleted'));

        return $this->redirectToRoute('app_home');
    }

    #[Route('/acces-api', name: 'api_account_toggle_api', methods: ['POST'])]
    public function toggleApiAccess(Request $request): Response
    {
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('access_api', (string) $request->request->get('_token')))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['message' => $this->translator->trans('toast.invalid_data')], 403);
            }
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();

        $enabled = !$user->isApiAccessEnabled();
        $user->setApiAccessEnabled($enabled);
        $this->em->flush();

        $message = $enabled
            ? $this->translator->trans('toast.api_enabled')
            : $this->translator->trans('toast.api_disabled');

        if ($request->isXmlHttpRequest()) {
            return $this->json(['enabled' => $enabled, 'message' => $message]);
        }

        return $this->toastRedirect($enabled ? 'toast.api_enabled' : 'toast.api_disabled', 'profil');
    }

    // ************** Helpers **************

    private function renderAccount(
        User $user,
        OrderRepository $orderRepository,
        FormInterface $profileForm,
        FormInterface $passwordForm,
        FormInterface $addressForm,
        Request $request,
        ?Address $editingAddress = null,
    ): Response {
        $orders = $orderRepository->findBy(['user' => $user, 'isValid' => true], ['createdAt' => 'DESC']);

        // Onglet à afficher : forcé sur "adresses" en édition, sinon valeur
        // déposée en session par une redirection de formulaire (lue une fois),
        // par défaut "overview". L'URL reste propre (/mon-compte/).
        $session = $this->requestStack->getSession();
        $activeTab = $editingAddress ? 'adresses' : ($session->get('account_tab') ?? 'overview');
        $session->remove('account_tab');

        return $this->render('security/account.html.twig', [
            'user' => $user,
            'orders' => $orders,
            'profileForm' => $profileForm,
            'passwordForm' => $passwordForm,
            'addressForm' => $addressForm,
            'editingAddress' => $editingAddress,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Si l'adresse est marquée par défaut, retire le défaut des autres adresses
     * de l'utilisateur (une seule adresse par défaut).
     */
    private function applyDefault(User $user, Address $address): void
    {
        if (!$address->isDefault()) {
            return;
        }
        foreach ($user->getAddresses() as $other) {
            if ($other !== $address) {
                $other->setIsDefault(false);
            }
        }
    }

    private function denyUnlessOwner(Address $address): void
    {
        if ($address->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    }

    private function toastRedirect(string $messageKey, string $tab): Response
    {
        $session = $this->requestStack->getSession();
        $session->set('toast', $this->translator->trans($messageKey));
        // Onglet à réafficher après la redirection (URL propre, sans ?tab=)
        $session->set('account_tab', $tab);

        return $this->redirectToRoute('app_account');
    }
}
