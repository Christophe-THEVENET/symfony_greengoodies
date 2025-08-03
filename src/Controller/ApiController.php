<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class ApiController extends AbstractController
{
    #[Route('/products', name: 'api_products', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function products(
        Security $security,
        ProductRepository $productRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $security->getUser();
        if (!$user || !$user->isApiAccessEnabled()) {
            return new JsonResponse(['error' => 'API access not enabled'], 403);
        }

        $products = $productRepository->findAll();

        $jsonContent = $serializer->serialize($products, 'json', [
            'groups' => ['product:read']
        ]);

        return new JsonResponse($jsonContent, 200, [], true);
    }
}
