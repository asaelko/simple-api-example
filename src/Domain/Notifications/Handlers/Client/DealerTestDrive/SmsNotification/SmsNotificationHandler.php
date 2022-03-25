<?php


namespace App\Domain\Notifications\Handlers\Client\DealerTestDrive\SmsNotification;


use App\Domain\Notifications\Messages\Client\DealerTestDrive\SmsNotification\SmsNotificationMessage;
use App\Entity\TestDriveRequest;
use AppBundle\Service\Phone\PhoneService;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Dealer;
use CarlBundle\Helpers\TextFormatterHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class SmsNotificationHandler implements MessageHandlerInterface
{
    private EntityManagerInterface $entityManger;

    private PhoneService $phoneService;

    public function __construct(
        EntityManagerInterface $entityManager,
        PhoneService $phoneService
    )
    {
        $this->entityManger = $entityManager;
        $this->phoneService = $phoneService;
    }

    public function __invoke(SmsNotificationMessage $message): bool
    {
        /** @var Client $client */
        $client = $this->entityManger->getRepository(Client::class)->find($message->getClientId());

        if (!$client) {
            return false;
        }

        /** @var Dealer $dealer */
        $dealer = $this->entityManger->getRepository(Dealer::class)->find($message->getDealerId());

        if (!$dealer) {
            return false;
        }

        switch ($message->getState()) {
            case TestDriveRequest::STATUS_APPROVE:
                $text = sprintf(
                    "%s подтвердил ваш тест-драйв! Ждем вас %s в %s по адресу %s. Хорошей поездки!",
                    ucfirst($dealer->getName()),
                    $message->getTestDriveDate()->format('d-m-Y'),
                    $message->getTestDriveDate()->format('H:i'),
                    $dealer->getAddress()
                );
                break;
            case TestDriveRequest::STATUS_DECLINE:
                $text = sprintf(
                    "похоже у дилера что-то пошло не так. %s отменил ваш тест-драйв! Мы обязательно разберемся.",
                    $dealer->getName()
                );
                break;
            default:
                return false;
        }

        $text = $this->addNameToText($client->getFirstName(), $text);

        $this->phoneService->sendSms($client->getPhone(), $text, $client->getAppTag());

        return true;
    }

    /**
     * Добавляем имя в текст пуша, если это возможно
     *
     * @param string|null $firstName
     * @param string $text
     * @param bool $keepCase
     * @return string
     */
    protected function addNameToText(?string $firstName, string $text, bool $keepCase = false): string
    {
        if (!$keepCase) {
            $text = TextFormatterHelper::lcfirst($text);
        }

        $prefix = $firstName ? $firstName . ', ' : '';
        $text = $prefix . $text;

        return $keepCase ? $text : TextFormatterHelper::ucfirst($text);
    }
}