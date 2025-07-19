<?php
// src/Controller/AccountController.php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        // Formulaire profil
        $profileForm = $this->createForm(AccountType::class, $user);
        $profileForm->handleRequest($request);

        // Formulaire mot de passe
        $passwordForm = $this->createForm(ChangePasswordType::class);
        $passwordForm->handleRequest($request);

        // Traitement formulaire profil
        if ($profileForm->isSubmitted() && $profileForm->isValid()) {
            // Vérifie si les champs firstname, lastname ou email ont changé
            $originalUser = $entityManager->getUnitOfWork()->getOriginalEntityData($user);

            $fieldsToCheck = ['firstname', 'lastname', 'email'];
            $hasChanged = false;
            foreach ($fieldsToCheck as $field) {
            if (array_key_exists($field, $originalUser) && $user->{'get' . ucfirst($field)}() !== $originalUser[$field]) {
                $hasChanged = true;
                break;
            }
            }

            if ($hasChanged) {
            $entityManager->persist($user);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profil mis à jour avec succès !');
            return $this->redirectToRoute('app_account');
        }

        // Traitement formulaire mot de passe
        if ($passwordForm->isSubmitted() && $passwordForm->isValid()) {
            /** @var string $newPlainPassword */
            $newPlainPassword = $passwordForm->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $newPlainPassword));

            $entityManager->flush();

            $this->addFlash('success', 'Mot de passe mis à jour avec succès !');
            return $this->redirectToRoute('app_account');
        }

        return $this->render('security/account.html.twig', [
            'user' => $user,
            'profileForm' => $profileForm->createView(),
            'passwordForm' => $passwordForm->createView(),
        ]);
    }
}
