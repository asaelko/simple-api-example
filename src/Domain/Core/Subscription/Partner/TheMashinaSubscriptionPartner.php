<?php

namespace App\Domain\Core\Subscription\Partner;

use App\Entity\SubscriptionModel;
use App\Entity\SubscriptionPartner;
use App\Entity\SubscriptionRequest;
use AppBundle\Service\AppConfig;
use AppBundle\Service\Mail\Mail;
use AppBundle\Service\Mail\MailService;
use CarlBundle\Entity\Brand;
use CarlBundle\Entity\Model\Model;
use CarlBundle\ServiceRepository\Model\ModelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\Error;

class TheMashinaSubscriptionPartner implements SubscriptionPartnerInterface
{
    public const PARTNER_ID = 3;
    private const LOAD_LINK = 'https://prod.themashina.ru/api/carl';
    private const LEAD_EMAIL = 'support@themashina.ru';

    private Client $client;
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private ModelRepository $modelRepository;
    private Environment $templatingService;
    private MailService $mailService;
    private AppConfig $appConfig;

    public function __construct(
        Client $client,
        LoggerInterface $subscriptionLogger,
        EntityManagerInterface $entityManager,
        ModelRepository $modelRepository,
        AppConfig $appConfig,
        Environment $templatingService,
        MailService $mailService
    )
    {
        $this->client = $client;
        $this->logger = $subscriptionLogger;
        $this->entityManager = $entityManager;
        $this->modelRepository = $modelRepository;
        $this->templatingService = $templatingService;
        $this->mailService = $mailService;
        $this->appConfig = $appConfig;
    }

    /**
     * @inheritDoc
     */
    public function loadData(): void
    {
        $data = [];
        $loadTime = new \DateTime();
        try {
            $response = $this->client->get(self::LOAD_LINK);
            $content = preg_replace('/\s+(?=([^"]*"[^"]*")*[^"]*$)/', '', $response->getBody()->getContents());
            $content = str_replace('},]', '}]', $content);

            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $ex) {
            $this->logger->error($ex);
        }

        /** @var SubscriptionPartner $partner */
        $partner = $this->entityManager->getRepository(SubscriptionPartner::class)->find(self::PARTNER_ID);
        foreach ($data as $modelData) {
            $subModel = $this->entityManager->getRepository(SubscriptionModel::class)->findOneBy([
                'partnerCode' => $modelData['id']
            ]);

            if (!$subModel) {
                if ($modelData['brand'] === 'Mercedes') {
                    $modelData['brand'] = 'Mercedes-Benz';
                }
                if ($modelData['brand'] === 'Skoda') {
                    $modelData['brand'] = 'ŠKODA';
                }
                /** @var Brand $brand */
                $brand = $this->entityManager->getRepository(Brand::class)->findOneBy([
                    'name' => trim($modelData['brand'])
                ]);

                if (!$brand) {
                    $this->logger->error("For partner TheMashina brand {$modelData['brand']} not found");
                    continue;
                }

                /** @var Model $model */
                $model = $this->modelRepository->findByBrandAndName($brand, trim($modelData['model']));

                if (!$model) {
                    $this->logger->error("For partner TheMashina model {$modelData['brand']} {$modelData['model']} not found");
                    continue;
                }

                $subModel = new SubscriptionModel();
                $subModel->setPartnerCode($modelData['id'])
                    ->setModel($model)
                    ->setPartner($partner);

                $this->entityManager->persist($subModel);
            }

            $subModel->setPrice($modelData['cost'])
                ->setOptions($this->processOptions($modelData))
                ->setEquipmentUrl(empty($modelData['equipment_link']) ? null : $modelData['equipment_link'])
                ->setUpdatedAt($loadTime);
        }
        $this->entityManager->flush();
    }

    /**
     * @inheritDoc
     */
    public function sendLead(SubscriptionRequest $request): void
    {
        try {
            $template = "@Carl/emails.main/dealer/subscription_request_email.html.twig";

            $content = $this->templatingService->render($template, [
                'request' => $request,
            ]);
        } catch (Error $e) {
            $this->logger->critical($e);
            return;
        }

        $mail = new Mail();
        $mail
            ->setHtmlContent($content)
            ->setSubject('Запрос подписки');

        $sender = $this->appConfig->getWlConfig('main')['mail']['sender'];
        $mail->setSenderName($sender['name'])
            ->setSenderEmail($sender['mail'])
            ->addRecipient(['email' => self::LEAD_EMAIL])
            ->addRecipient(['email' => 'g@carl-drive.ru']);

        $this->mailService->sendEmail($mail);
    }

    /**
     * Обрабатываем опции подписки
     *
     * @param array $data
     *
     * @return array
     */
    private function processOptions(array $data): array
    {
        $result = [];

        if (isset($data['manufacture_year'])) {
            $result['Год выпуска'] = (string) $data['manufacture_year'];
        }

        if (isset($data['manufacture_code'])) {
            $result['Цвет'] = implode("\n", $data['manufacture_code']);
        }

        if (isset($data['transmission'])) {
            $result['Трансмиссия'] = $data['transmission'];
        }

        if (isset($data['wheel_drive'])) {
            $result['Привод'] = $data['wheel_drive'];
        }

        if (isset($data['engin_volume'])) {
            $result['Объем двигателя'] = $data['engin_volume'] . ' л.';
        }

        if (isset($data['horse_power'])) {
            $result['Мощность'] = $data['horse_power'] . ' л.с.';
        }

//        if (!empty($data['options'])) {
//            $result['Опции'] = ' - ' . implode("\n - ", $data['options']);
//        }

        return $result;
    }
}
