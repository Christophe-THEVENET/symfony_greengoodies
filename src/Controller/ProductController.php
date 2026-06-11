<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    #[Route('/produit/{slug}', name: 'app_product_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Product $product,
        ProductRepository $productRepository,
    ): Response {
        // Produits liés (4 autres produits aléatoires, hors celui affiché)
        $relatedProducts = $productRepository->createQueryBuilder('p')
            ->where('p.id != :currentId')
            ->setParameter('currentId', $product->getId())
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
