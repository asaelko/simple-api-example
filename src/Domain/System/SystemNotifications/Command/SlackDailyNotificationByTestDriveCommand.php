<?php
namespace App\Domain\System\SystemNotifications\Command;

use CarlBundle\Entity\Drive;
use CarlBundle\Helpers\TranslateHelper;
use CarlBundle\Service\SlackNotificatorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SlackDailyNotificationByTestDriveCommand extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'slack:notify-by-td-daily';

    protected function configure()
    {
        $this
            ->setDescription('Make notification to slack with test drive information')
            ->setHelp('Make notification to slack with test drive information');
    }

    private EntityManagerInterface $entityManager;

    private SlackNotificatorService $notificatorService;

    private const MAIN_TEXT = "Сегодня %s в CARL доступны следующие автомобили и забронировано %d %s:";

    private const STRING_TEXT = "%s. %s - %s %s, в %s. Консультант - %s";

    private const YESTERDAY_TEXT = "Вчера %s состоялось:\n %d %s\n %d %s";

    private const WEEK_TEXT = "Всего на этой неделе: (с понедельника по воскресенье)\n %d %s\n %d %s";

    public function __construct(
        string $name = null,
        EntityManagerInterface $entityManager,
        SlackNotificatorService $notificatorService
    )
    {
        $this->notificatorService = $notificatorService;
        $this->entityManager = $entityManager;
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moscowTimezone = new \DateTimeZone('Europe/Moscow');
        $date = (new \DateTime())->setTimezone($moscowTimezone);
        $dateStart = clone $date;
        $dateStart->setTime(0,0,0);
        $dateEnd = clone $date;
        $dateEnd->setTime(23,59,59);
        $testDrives = $this->entityManager->getRepository(Drive::class)->getDrivesByDate($dateStart, $dateEnd);

        $text = sprintf(
            self::MAIN_TEXT,
            $date->format('d.m.Y'),
            count($testDrives),
            TranslateHelper::plural_ru_form(count($testDrives), ['тест-драйв', 'тест-драйва', 'тест-драйвов'])
        );

        $pureData = [];
        /** @var Drive $testDrive */
        foreach ($testDrives as $testDrive) {
            $pureData[$testDrive->getCar()->getCarModelBrandName()]['driver'] = $testDrive->getDriver() ? $testDrive->getDriver()->getFullName() : 'не назначено';
            $pureData[$testDrive->getCar()->getCarModelBrandName()]['time'][] = $testDrive->getStart()->setTimezone($moscowTimezone)->format('H:i');
        }

        $i = 1;
        $linesArray = [];
        foreach ($pureData as $key => $record) {
            $linesArray[] = sprintf(
                self::STRING_TEXT,
                $i,
                $key,
                count($record['time']),
                TranslateHelper::plural_ru_form(count($record['time']), ['тест-драйв', 'тест-драйва', 'тест-драйвов']),
                implode(', ', $record['time']),
                $record['driver']
            );
            $i++;
        }

        $weekStart = new \DateTime('monday this week');
        $weekEnd = new \DateTime('sunday this week');


        $weekTotal = $this
            ->entityManager
            ->getRepository(Drive::class)
            ->countDrivesByDateAndType(
                $weekStart,
                $weekEnd,
                Drive::$finishedStates
            );

        $weekClose = $this
            ->entityManager
            ->getRepository(Drive::class)
            ->countDrivesByDateAndType(
                $weekStart,
                $weekEnd,
                [Drive::STATE_CANCELLED]
            );

        $yesterdayStart = clone $dateStart;
        $yesterdayStart->modify('-1 day');
        $yesterdayEnd = clone $dateEnd;
        $yesterdayEnd->modify('-1 day');

        $yesterdayTotal = $this
            ->entityManager
            ->getRepository(Drive::class)
            ->countDrivesByDateAndType(
                $yesterdayStart,
                $yesterdayEnd,
                Drive::$finishedStates
            );

        $yesterdayClose = $this
            ->entityManager
            ->getRepository(Drive::class)
            ->countDrivesByDateAndType(
                $yesterdayStart,
                $yesterdayEnd,
                [Drive::STATE_CANCELLED]
            );

        $yesterdayText = sprintf(
            self::YESTERDAY_TEXT,
            $yesterdayStart->format('d.m.Y'),
            $yesterdayTotal,
            TranslateHelper::plural_ru_form($yesterdayTotal, ['тест-драйв', 'тест-драйва', 'тест-драйвов']),
            $yesterdayClose,
            TranslateHelper::plural_ru_form($yesterdayClose, ['отмена', 'отмены', 'отмен']),
        );

        $weekText = sprintf(
            self::WEEK_TEXT,
            $weekTotal,
            TranslateHelper::plural_ru_form($weekTotal, ['тест-драйв', 'тест-драйва', 'тест-драйвов']),
            $weekClose,
            TranslateHelper::plural_ru_form($weekClose, ['отмена', 'отмены', 'отмен']),
        );

        foreach ($linesArray as $line) {
            $text .= "\n" . $line;
        }
        $text .= "\n" . $yesterdayText . "\n" . $weekText;

        $this->notificatorService->sendCustomTextToSlackUserChannel($text);

        return 0;
    }
}