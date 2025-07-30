<?php
// src/Controller/AccountController.php

namespace App\Controller;

use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class AccountController extends AbstractController
{
    #[Route('/mon-compte', name: 'app_account')]
    #[IsGranted('ROLE_USER')]
    public function index(OrderRepository $orderRepository): Response
     {
        $user = $this->getUser();

        $orders = $orderRepository->findValidOrdersByUser($user);
     


        return $this->render('security/account.html.twig', [
            'user' => $user,
            'orders' => $orders
          
        ]);
    }
}
