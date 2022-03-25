<?php

namespace App\Command\Uis;


use App\Domain\Infrastructure\IpTelephony\Uis\Service\UisService;
use CarlBundle\Entity\Driver;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UisRegistrationOldUserCommand extends Command
{
    protected static $defaultName = 'uis:registration';

    protected function configure()
    {
        $this
            ->setDescription('create uis acc')
            ->setHelp('create uis acc');
    }

    private ParameterBagInterface $parameterBag;

    private EntityManagerInterface $entityManager;

    private UisService $uisService;

    public function __construct(
        string $name = null,
        ParameterBagInterface $parameterBag,
        EntityManagerInterface $entityManager,
        UisService $uisService
    )
    {
        $this->uisService = $uisService;
        $this->entityManager = $entityManager;
        $this->parameterBag = $parameterBag;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $drivers = $this->entityManager->getRepository(Driver::class)->findBy(
                [
                    'uisId' => null,
                    'deletedAt' => null,
                ]
            );
            $progressBar = new ProgressBar($output, count($drivers));
            foreach ($drivers as $driver) {
                assert($driver instanceof Driver);
                $progressBar->advance();
                if ($driver->getPhone()) {
                    $this->uisService->createEmployer($driver);
                }
            }
            $progressBar->finish();
            return 0;
        } catch (\Exception $e) {
            $err = [
                $e->getMessage(),
                $e->getLine(),
                $e->getFile(),
            ];
            $output->writeln($err);
            return 1;
        }
    }
}