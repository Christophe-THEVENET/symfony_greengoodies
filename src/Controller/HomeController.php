<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    // Tags produits pour la démo (par ID)
    private const PRODUCT_TAGS = [
        1 => 'Best-seller',
        2 => 'Nouveau',
        5 => 'Favori',
        9 => 'Artisanal',
    ];

    #[Route('/', name: 'app_home')]
    public function index(ProductRepository $productRepository): Response
    {
        $products = $productRepository->findBy([], ['id' => 'ASC']);

        return $this->render('home/index.html.twig', [
            'products' => $products,
            'productTags' => self::PRODUCT_TAGS,
        ]);
    }
}
