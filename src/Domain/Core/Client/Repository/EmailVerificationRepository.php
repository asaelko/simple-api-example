<?php

namespace App\Domain\Core\Client\Repository;

use App\Entity\Client\EmailVerification;
use CarlBundle\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Репозиторий для работы с заявками на подтверждение адреса почты
 */
class EmailVerificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerification::class);
    }

    /**
     * Отдает последний запрос пользователя на подтверждение почты
     *
     * @param Client $client
     * @return EmailVerification|null
     */
    public function getLastVerificationRequest(Client $client): ?EmailVerification
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        try {
            return $qb->select('email_verification')
                ->from(EmailVerification::class, 'email_verification')
                ->where('email_verification.client = :client')
                ->setParameter('client', $client)
                ->orderBy('email_verification.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Отдает последний неподтвержденный запрос на подтверждение почты по токену
     *
     * @param string $token
     * @return EmailVerification|null
     */
    public function getPendingRequestByToken(string $token): ?EmailVerification
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        try {
            return $qb->select('email_verification')
                ->from(EmailVerification::class, 'email_verification')
                ->where('email_verification.verificationToken = :token')
                ->andWhere('email_verification.validatedAt is null')
                ->setParameter('token', $token)
                ->orderBy('email_verification.createdAt', 'DESC')
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
