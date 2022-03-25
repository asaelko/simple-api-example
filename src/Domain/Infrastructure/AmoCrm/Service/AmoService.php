<?php

namespace App\Domain\Infrastructure\AmoCrm\Service;

use AmoCRM\Client\AmoCRMApiClient;
use AmoCRM\Collections\CatalogElementsCollection;
use AmoCRM\Collections\CustomFieldsValuesCollection;
use AmoCRM\Collections\LinksCollection;
use AmoCRM\Exceptions\AmoCRMApiException;
use AmoCRM\Exceptions\AmoCRMoAuthApiException;
use AmoCRM\Filters\CatalogElementsFilter;
use AmoCRM\Filters\ContactsFilter;
use AmoCRM\Models\CatalogElementModel;
use AmoCRM\Models\CatalogModel;
use AmoCRM\Models\ContactModel;
use AmoCRM\Models\CustomFieldsValues\CheckboxCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\MultitextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\SelectCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\TextCustomFieldValuesModel;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\CheckboxCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\MultitextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\SelectCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueCollections\TextCustomFieldValueCollection;
use AmoCRM\Models\CustomFieldsValues\ValueModels\CheckboxCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\MultitextCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\SelectCustomFieldValueModel;
use AmoCRM\Models\CustomFieldsValues\ValueModels\TextCustomFieldValueModel;
use AmoCRM\Models\LeadModel;
use AmoCRM\Models\TaskModel;
use CarlBundle\Entity\Car;
use CarlBundle\Entity\Client;
use CarlBundle\Entity\Drive;
use CarlBundle\Entity\Prebooking\PrebookingSession;
use CarlBundle\Service\DictionaryService;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AmoService
{
    private AmoCRMApiClient $amo;
    private ParameterBagInterface $params;
    private LoggerInterface $amoLogger;
    private DictionaryService $dictionaryService;
    private ?CatalogModel $catalog = null;

    /**
     * AmoService constructor.
     * @param TokenService $tokenService
     * @param ParameterBagInterface $params
     * @param LoggerInterface $amoLogger
     * @param DictionaryService $dictionaryService
     */
    public function __construct(
        TokenService $tokenService,
        ParameterBagInterface $params,
        LoggerInterface $amoLogger,
        DictionaryService $dictionaryService
    )
    {
        $this->amoLogger = $amoLogger;
        $this->amo = $tokenService->getApiClient();
        $this->params = $params;
        $this->dictionaryService = $dictionaryService;
    }

    /**
     * Связывает между собой сделку и другую сущность
     *
     * @param $model
     * @param LeadModel $lead
     */
    public function link($model, LeadModel $lead)
    {
        $leadLink = new LinksCollection();
        $leadLink->add($model);

        $this->amo->leads()->link($lead, $leadLink);
    }

    /**
     * Создает поля типа флаг
     * @param $id
     * @param $value
     * @return CheckboxCustomFieldValuesModel
     */
    public function createBooleanCustomField($id, $value): CheckboxCustomFieldValuesModel
    {
        $radioField = (new CheckboxCustomFieldValuesModel())->setFieldId($id);

        return $radioField->setValues(
            (new CheckboxCustomFieldValueCollection())
                ->add(
                    (new CheckboxCustomFieldValueModel())
                        ->setValue($value)
                )
        );
    }

    /**
     * Создает поля типа список
     * @param $id
     * @param $value
     * @return SelectCustomFieldValuesModel
     */
    public function createListCustomField($id, $value): SelectCustomFieldValuesModel
    {
        $listField = (new SelectCustomFieldValuesModel())->setFieldId($id);

        return $listField->setValues(
            (new SelectCustomFieldValueCollection())
                ->add(
                    (new SelectCustomFieldValueModel())
                        ->setValue($value)
                )
        );
    }

    /**
     * Позволяет указать значение для поля мультисписок
     *
     * @param $field
     * @param $value
     */
    public function setMultiFieldValue($field, $value)
    {
        $field->setValues(
            (new MultitextCustomFieldValueCollection())
                ->add(
                    (new MultitextCustomFieldValueModel())
                        ->setEnum('WORK')
                        ->setValue($value)
                )
        );
    }

    /**
     * Устанавливает значение текстовому полу в сделке
     *
     * @param $id
     * @param $value
     * @return TextCustomFieldValuesModel
     */
    public function createTextCustomField($id, $value): TextCustomFieldValuesModel
    {
        $textField = (new TextCustomFieldValuesModel())->setFieldId($id);

        return $textField->setValues(
            (new TextCustomFieldValueCollection())
                ->add(
                    (new TextCustomFieldValueModel())
                        ->setValue($value)
                )
        );
    }

    /**
     * Создает задачу
     *
     * @param $leadId
     * @param $text
     */
    public function createTask($leadId, $text): void
    {
        $task = new TaskModel();
        $task->setEntityId($leadId);
        $task->setTaskTypeId(TaskModel::TASK_TYPE_ID_CALL);
        $task->setText($text);
        $task->setCompleteTill((new DateTime('+7 day'))->getTimestamp());
        $task->setEntityType('leads');
        $task->setResponsibleUserId($this->params->get('amo.manager.id'));

        try {
            $this->amo->tasks()->addOne($task);
        } catch (AmoCRMoAuthApiException | AmoCRMApiException $e) {
            return;
        }
    }

    /**
     * Создает контакт в амо
     *
     * @param string $name
     * @param string $phone
     * @param string $email
     * @return ContactModel
     * @throws AmoCRMApiException
     * @throws AmoCRMoAuthApiException
     */
    public function createContact(
        string $name,
        string $phone,
        string $email
    ): ContactModel {
        $oldContact = $this->findContact($phone);
        if ($oldContact) {
            return $oldContact;
        }
        $contact = new ContactModel();
        $contact->setName($name);

        $contactFields = new CustomFieldsValuesCollection();

        $phoneField = (new MultitextCustomFieldValuesModel())->setFieldCode('PHONE');
        $this->setMultiFieldValue($phoneField, $phone);
        $contactFields->add($phoneField);

        $emailField = (new MultitextCustomFieldValuesModel())->setFieldCode('EMAIL');
        $this->setMultiFieldValue($emailField, $email);
        $contactFields->add($emailField);

        $contact->setCustomFieldsValues($contactFields);

        $this->amo->contacts()->addOne($contact);

        return $contact;
    }

    /**
     * Ищет контакт в амо
     *
     * @param $phone
     * @return ContactModel|null
     */
    private function findContact($phone): ?ContactModel
    {
        $filter = (new ContactsFilter())->setQuery($phone);
        try {
            return $this->amo->contacts()->get($filter)->first();
        } catch (AmoCRMoAuthApiException | AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return null;
        }
    }

    /**
     * Создает сделку
     *
     * @param PrebookingSession $session
     * @param ContactModel $contactModel
     */
    public function createLead(PrebookingSession $session, ContactModel $contactModel): void
    {
        $lead = new LeadModel();
        $lead
            ->setName('ББ ' .
                $session->getSchedule()->getCar()->getCarModelBrandName() .
                ' / ' . $session->getClient()->getFirstName() . " с " .
                (($session->getWidget()) ? $session->getWidget()->getDescription() : '-')
            )
            ->setStatusId($this->params->get('amo.status.id.booking'))
            ->setPipelineId($this->params->get('amo.pipeline.id.booking'));

        $customFields = new CustomFieldsValuesCollection();

        $customFields->add(
            $this->createTextCustomField($this->params->get('amo.field.address'), $session->getLocationName() ?? '-')
        );
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.auto.start'),
                    ($session->getSchedule() && $session->getSchedule()->getCar()) ? $session->getSchedule()->getCar()->getCarModelBrandName() : '-'
                )
        );
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.source.name'),
                    ($session->getWidget()) ? $session->getWidget()->getDescription() : '-'
                )
        );
        if ($session->getClient()->getCreatedAt()) {
            $customFields->add(
                $this
                    ->createTextCustomField(
                        $this->params->get('amo.field.date.registration'),
                        $session->getClient()->getCreatedAt()
                    )
            );
        }
        if ($session->getExpiresAt()) {
            $customFields->add(
                $this
                    ->createTextCustomField(
                        $this->params->get('amo.field.date.leave'),
                        $session->getExpiresAt()->getTimestamp()
                    )
            );
        }


        $lead->setCustomFieldsValues($customFields);

        try {
            $this->amo->leads()->addOne($lead);
            $this->createTask($lead->getId(), "Позвонить клиенту");
        } catch (AmoCRMoAuthApiException | AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return;
        }
        $this->link($contactModel, $lead);
    }

    /**
     * Создает сделку на основе поездки
     *
     * @param Drive $drive
     * @param ContactModel $contactModel
     * @param int $date
     */
    public function createLeadByCanceledDrive(Drive $drive, ContactModel $contactModel, int $date): void
    {
        $lead = new LeadModel();
        $lead
            ->setName('Отмена ТД ' . $drive->getSchedule()->getCar()->getCarModelBrandName() . ' ' . $drive->getClient()->getFirstName())
            ->setStatusId($this->params->get('amo.status.id.canceled'))
            ->setPipelineId($this->params->get('amo.pipeline.id.canceled'));

        $customFields = new CustomFieldsValuesCollection();

        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.auto.start'),
                    ($drive->getSchedule() && $drive->getSchedule()->getCar()) ? $drive->getSchedule()->getCar()->getCarModelBrandName() : '-'
                )
        );
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.source.name'),
                    ($drive->getWidget()) ? $drive->getWidget()->getDescription() : '-'
                )
        );
        if ($drive->getClient()->getCreatedAt()) {
            $customFields->add(
                $this
                    ->createTextCustomField(
                        $this->params->get('amo.field.date.registration'),
                        $drive->getClient()->getCreatedAt()
                    )
            );
        }
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.date.leave'),
                    $date
                )
        );



        $lead->setCustomFieldsValues($customFields);

        try {
            $this->amo->leads()->addOne($lead);
            $this->createTask($lead->getId(), "Позвонить клиенту");
        } catch (AmoCRMoAuthApiException | AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return;
        }
        $this->link($contactModel, $lead);
    }

    /**
     * Создает сделку
     *
     * @param Car $car
     * @param Client $client
     * @param int $time
     * @param ContactModel $contactModel
     */
    public function createLeadByCar(Car $car, Client $client, int $time, ContactModel $contactModel): void
    {
        $lead = new LeadModel();
        $lead
            ->setName('ББ ' . $car->getCarModelBrandName() . ' / ' . $client->getFullName() . " с CARL App")
            ->setStatusId($this->params->get('amo.status.id.booking'))
            ->setPipelineId($this->params->get('amo.pipeline.id.booking'));

        $customFields = new CustomFieldsValuesCollection();

        $customFields->add(
            $this->createTextCustomField($this->params->get('amo.field.address'), '-')
        );
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.auto.start'),
                    $car->getCarModelBrandName()
                )
        );
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.source.name'),
                    'CARL App'
                )
        );

        if ($client->getCreatedAt()) {
            $customFields->add(
                $this
                    ->createTextCustomField(
                        $this->params->get('amo.field.date.registration'),
                        $client->getCreatedAt()
                    )
            );
        }
        $customFields->add(
            $this
                ->createTextCustomField(
                    $this->params->get('amo.field.date.leave'),
                    $time
                )
        );

        $lead->setCustomFieldsValues($customFields);

        try {
            $this->amo->leads()->addOne($lead);
            $this->createTask($lead->getId(), "Позвонить клиенту");
        } catch (AmoCRMoAuthApiException | AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return;
        }
        $this->link($contactModel, $lead);
    }

    /**
     * @param Drive $drive
     * @param ContactModel $contactModel
     * @return LeadModel|null
     */
    public function createLeadByTestDrive(Drive $drive, ContactModel $contactModel): ?LeadModel
    {
        $probability = $this->dictionaryService->getByType('drive.probability');
        $liked = $this->dictionaryService->getByType('drive.liked');

        $lead = new LeadModel();
        $lead
            ->setName('ТД ' . $drive->getClient()->getFirstName() . ' на ' . $drive->getSchedule()->getCar()->getCarModelBrandName())
            ->setStatusId($this->params->get('amo.status.id.drive'))
            ->setPipelineId($this->params->get('amo.pipeline.id.drive'));

        $customFields = new CustomFieldsValuesCollection();

        $customFields->add(
            $this->createTextCustomField(
                $this->params->get('amo.field.auto'),
                ($drive->getSchedule() && $drive->getSchedule()->getCar()) ? $drive->getSchedule()->getCar()->getCarModelBrandName() : '-')
        );
        if ($drive->getSchedule()->getStop()) {
            $customFields->add(
                $this
                    ->createTextCustomField(
                        $this->params->get('amo.field.date.drive'),
                        $drive->getSchedule()->getStop()->getTimestamp()
                    )
            );
        }
        $customFields->add(
            $this->createTextCustomField(
                $this->params->get('amo.field.buy.from'),
                $drive->getClient()->getPurchasePeriodFrom() ?? 0
            )
        );
        $customFields->add(
            $this->createTextCustomField(
                $this->params->get('amo.field.buy.to'),
                $drive->getClient()->getPurchasePeriodTo() ?? 0
            )
        );
        $customFields->add(
            $this->createBooleanCustomField(
                $this->params->get('amo.field.credit'),
                $drive->getClient()->getNeedCredit()
            )
        );
        $customFields->add(
            $this->createBooleanCustomField(
                $this->params->get('amo.field.leasing'),
                $drive->getClient()->getNeedLeasing()
            )
        );
        if ($drive->getProbability()) {
            $customFields->add(
                $this->createListCustomField(
                    $this->params->get('amo.field.buy.probability'),
                    $probability[$drive->getProbability()]
                )
            );
        }
        if ($drive->getFeedback() && $drive->getFeedback()->isLiked()) {
            $customFields->add(
                $this->createListCustomField(
                    $this->params->get('amo.field.reaction'),
                    $liked[$drive->getFeedback()->isLiked()]
                )
            );
        }


        $lead->setCustomFieldsValues($customFields);

        try {
            $this->amo->leads()->addOne($lead);
            $this->createTask($lead->getId(), "Позвонить клиенту");
        } catch (AmoCRMoAuthApiException | AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return null;
        }
        $this->link($contactModel, $lead);
        return $lead;
    }

    public function getCatalogData(Car $car, CatalogModel $catalog): ?CatalogElementModel
    {
        $catalogElementsService = $this->amo->catalogElements($catalog->getId());
        $catalogElementsFilter = new CatalogElementsFilter();
        $catalogElementsFilter->setQuery($car->getCarModelBrandName());
        try {
            $catalogElementsCollection = $catalogElementsService->get($catalogElementsFilter);
            if ($catalogElementsCollection) {
                return $catalogElementsCollection->first();
            }
        } catch (AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return null;
        }
        return null;
    }

    public function getCatalog(): ?CatalogModel
    {
        if (!$this->catalog) {
            try {
                $catalogsCollection = $this->amo->catalogs()->get();
            } catch (AmoCRMApiException | AmoCRMoAuthApiException $e) {
                $this->amoLogger->error($e->getMessage());
                return null;
            }
            $this->catalog = $catalogsCollection->first();

        }
        return $this->catalog;
    }

    public function loadCatalogData(Car $car): ?CatalogElementModel
    {
        $catalog = $this->getCatalog();
        if ($this->getCatalogData($car, $catalog)) {
            return null;
        }

        $catalogElementsCollection = new CatalogElementsCollection();
        $catalogElement = new CatalogElementModel();
        $catalogElement->setName($car->getCarModelBrandName());
        $catalogElementsCollection->add($catalogElement);
        $catalogElementsService = $this->amo->catalogElements($catalog->getId());
        try {
            $catalogElementsService->add($catalogElementsCollection);
            return $catalogElement;
        } catch (AmoCRMApiException $e) {
            $this->amoLogger->error($e->getMessage());
            return null;
        }
    }

    public function linkProductWithLead(Car $car, LeadModel $leadModel)
    {
        $catalog = $this->getCatalog();
        $catalogElement = $this->getCatalogData($car, $catalog);
        if (!$catalogElement) {
            $catalogElement = $this->loadCatalogData($car);
            if (!$catalogElement) {
                return;
            }
        }
        $catalogElement->setQuantity(1);
        $this->link($catalogElement, $leadModel);
    }
}