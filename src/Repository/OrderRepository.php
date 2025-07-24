<?php
// src/Repository/OrderRepository.php

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    // get unvalidated order for a user
    public function findUnvalidatedOrderByUser(User $user): ?Order
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.is_valid = :isValid')
            ->setParameter('user', $user)
            ->setParameter('isValid', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    // make sure to get the next order number
    public function getNextOrderNumber(): string
    {
        // Utilise l'année + séquence auto-incrémentée
        $currentYear = date('Y');

        // Trouve le dernier numéro de commande de l'année
        $lastOrder = $this->createQueryBuilder('o')
            ->where('o.is_valid = :isValid')
            ->andWhere('o.order_number LIKE :yearPattern')
            ->setParameter('isValid', true)
            ->setParameter('yearPattern', "CMD-{$currentYear}-%")
            ->orderBy('o.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($lastOrder && $lastOrder->getOrderNumber()) {
            // Extrait le numéro de séquence : CMD-2024-000123 → 123
            $parts = explode('-', $lastOrder->getOrderNumber());
            $lastSequence = (int) end($parts);
            $nextSequence = $lastSequence + 1;
        } else {
            $nextSequence = 1;
        }

        return sprintf('CMD-%s-%06d', $currentYear, $nextSequence);
    }

    // get valid orders for a user
    public function findValidOrdersByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.is_valid = :isValid')
            ->setParameter('user', $user)
            ->setParameter('isValid', true)
            ->orderBy('o.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
