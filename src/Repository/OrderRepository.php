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
            ->andWhere('o.isValid = false')
            ->setParameter('user', $user)
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
            ->where('o.isValid = :isValid')
            ->andWhere('o.orderNumber LIKE :yearPattern')
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

        return sprintf('%s-%06d', $currentYear, $nextSequence);
    }

    // get the 5 latest valid orders for a user
    public function findLastFiveValidOrdersByUser(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->where('o.user = :user')
            ->andWhere('o.isValid = :isValid')
            ->setParameter('user', $user)
            ->setParameter('isValid', true)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}
