<?php

namespace App\Repository;

use App\Entity\MetaTable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MetaTable>
 *
 * @method MetaTable|null find($id, $lockMode = null, $lockVersion = null)
 * @method MetaTable|null findOneBy(array $criteria, array $orderBy = null)
 * @method MetaTable[]    findAll()
 * @method MetaTable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MetaTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MetaTable::class);
    }

    public function getColumnNames(string $filename): array // string $filename
    {
        return $this->createQueryBuilder('mt')
            ->addSelect('mt.columns')
            ->andWhere('mt.filename = :filename')
            ->setParameter('filename', $filename)
            ->getQuery()
            ->getResult()
        ; 
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(MetaTable $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(MetaTable $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function createOrDropDynamicTable($sql){

        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->executeQuery();

    }
    public function addDataToTable($sql){

        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare($sql);
        $stmt->executeQuery();

    }
    public function getUpdatedcsv($sql){

        $em = $this->getEntityManager();

        $stmt = $em->getConnection()->prepare($sql);
        $conn=$stmt->executeQuery()->fetchAllAssociative();
        return $conn;        
        
    }

    // /**
    //  * @return MetaTable[] Returns an array of MetaTable objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MetaTable
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
