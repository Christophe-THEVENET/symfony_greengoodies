<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class ApiController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ProductRepository $productRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    /* #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['username']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Username and password required'], 400);
        }

        $user = $this->userRepository->findOneBy(['email' => $data['username']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->isApiAccessEnabled()) {
            return new JsonResponse(['error' => 'API access not enabled'], 403);
        }

        $token = $this->jwtManager->create($user);

        return new JsonResponse(['token' => $token]);
    } */

    #[Route('/products', name: 'api_products', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function products(): JsonResponse
    {
        $products = $this->productRepository->findAll();

        $productsData = array_map(fn(Product $product) => $product->toArray(), $products);

        return new JsonResponse($productsData);
    }
}
