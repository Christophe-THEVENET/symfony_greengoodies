<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/produit', name: 'app_product_show')]
    public function show(): Response
    {
        return $this->render('product/show.html.twig', []);
    }
}
