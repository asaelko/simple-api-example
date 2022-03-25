<?php

namespace App\Domain\Core\Client\Controller\Response;

use CarlBundle\Entity\City;
use CarlBundle\Entity\Client;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

/**
 * Объект клиента, получаемый в результате авторизации на клиентах
 */
class ClientAuthApiResponse
{
    /**
     * Идентификатор клиента
     *
     * @OA\Property(type="integer", example=182)
     */
    public int $id;

    /**
     * Телефон клиента
     *
     * @OA\Property(type="string", example="79008288282", nullable=true)
     */
    public ?string $phone;

    /**
     * Почтовый адрес клиента
     *
     * @OA\Property(type="string", nullable=true, example="eamil@test.com", nullable=true)
     */
    public ?string $email;

    /**
     * Признак подтверждения почтового адреса клиента
     *
     * @OA\Property(type="bool", example=true)
     */
    public bool $emailVerified;

    /**
     * Имя клиента
     *
     * @OA\Property(type="string", nullable=true, example="Иван")
     */
    public ?string $firstName;

    /**
     * Фамилия клиента
     *
     * @OA\Property(type="string", nullable=true, example="Иванов")
     */
    public ?string $secondName;

    /**
     * Имеет водительские права с необходимым стажем
     *
     * @OA\Property(type="boolean", example=false)
     */
    public bool $hasLicense;

    /**
     * Номера водительских прав клиента
     *
     * @OA\Property(type="string", nullable=true, example="66гг777660")
     */
    public ?string $license;

    /**
     * Дата выдачи водительских прав в формате UNIX Timestamp
     *
     * @OA\Property(type="integer", nullable=true, example=1390867200)
     */
    public ?int $licenseDate;

    /**
     * Дата окончания срока действия водительских прав
     *
     * @OA\Property(type="integer", nullable=true, example=1643328000)
     */
    public ?int $licenseEndDate;

    /**
     * Год начала водительского стажа
     *
     * @OA\Property(type="integer", nullable=true, example=2010)
     */
    public ?int $experienceStartYear;

    /**
     * Дата рождения клиента в формате UNIX Timestamp
     *
     * @OA\Property(type="integer", nullable=true, example=680918400)
     */
    public ?int $birthDate;

    /**
     * Флаг принятия оферты сервиса клиентом
     *
     * @OA\Property(type="bool", example=true)
     */
    public bool $accept;

    /**
     * Признак завершенности заполнения профиля клиентом
     *
     * @OA\Property(type="bool", example=false)
     */
    public bool $completed;

    /**
     * Флаг наличия вип-статуса клиента
     *
     * @OA\Property(type="bool", example=false)
     */
    public bool $vip;

    /**
     * Рекламный идентификатор используемого устройства
     *
     * @OA\Property(type="string", nullable=true, example="7278752F-A330-4FD8-835B-8074F166039D")
     */
    public ?string $advertisementId;

    /**
     * Число завершенных поездок клиента
     *
     * @OA\Property(type="integer", nullable=true, example=7)
     */
    public ?int $drivesCount;

    /**
     * Город клиента, если указан
     *
     * @OA\Property(type="object", ref=@Model(type=CityAuthResponse::class), nullable=true)
     */
    public ?CityAuthResponse $city = null;

    /**
     * Идентификатор пользователя в Яндекс.Турбоаппе
     *
     * @OA\Property(type="string", nullable=true)
     */
    public ?string $psuid;

    public function __construct(Client $client, ?City $city)
    {
        $this->id = $client->getId();
        $this->phone = $client->getPhone();
        $this->email = $client->getEmail();
        $this->emailVerified = $client->isEmailVerified();
        $this->firstName = $client->getFirstName();
        $this->secondName = $client->getSecondName();
        $this->hasLicense = $client->getHasLicense();
        $this->license = $client->getLicense();
        $this->licenseDate = $client->getLicenseDate() ? $client->getLicenseDate()->getTimestamp() : null;
        $this->licenseEndDate = $client->getLicenseEndDate() ? $client->getLicenseEndDate()->getTimestamp() : null;
        $this->experienceStartYear = $client->getExperienceStartYear();
        $this->birthDate = $client->getBirthDate() ? $client->getBirthDate()->getTimestamp() : null;
        $this->accept = $client->isAccept();
        $this->completed = $client->isCompleted();
        $this->vip = $client->isVip();
        $this->advertisementId = $client->getAdvertisementId();
        $this->drivesCount = $client->getDrivesCount();

        $this->psuid = $client->getYandexPsuid();

        if ($city) {
            $this->city = new CityAuthResponse($city);
        }
    }
}
