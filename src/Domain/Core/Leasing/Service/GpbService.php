<?php


namespace App\Domain\Core\Leasing\Service;


use App\Domain\Core\Leasing\CalculateLeasingInterface;
use App\Domain\Core\Leasing\Response\LeasingResponse;
use CarlBundle\Exception\RestException;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GpbService implements CalculateLeasingInterface
{
    private array $parameters;
    private Client $client;
    private const PROVIDER_ID = 5;

    public function __construct(
        ParameterBagInterface $parameterBag
    )
    {
        $this->parameters = $parameterBag->get('gpb');
        $this->client = new Client(['base_uri' => $this->parameters['base_url']]);
    }

    public function getToken()
    {


        $result = $this->client->post(
            'token',
            [
                'form_params' => [
                    'username' => $this->parameters['login'],
                    'password' => $this->parameters['password'],
                    'grant_type' => 'password'
                ]
            ]
        );
        $body = $result->getBody();
        $data = json_decode($body, 1);
        return $data['access_token'];
    }

    /**
     * @param float $cost
     * @param int $firstPayPercent
     * @param int $term
     * @return mixed
     * @throws RestException
     */
    public function calculate(float $cost, int $firstPayPercent, int $term): LeasingResponse
    {
        $token = $this->getToken();

        $result = $this->client->post(
            'api/external/calc',
            [
                'headers' => [
                        'Authorization' => "Bearer {$token}"
                ],
                'json' => [
                    'GetId' => false,
                    'CostPl' => $cost,
                    'FirstPayPercent' => $firstPayPercent,
                    'Term' => $term,
                ]
            ]
        );
        $body = $result->getBody();
        $result = json_decode($body, 1);
        if (!array_key_exists('Result', $result)) {
            throw new RestException('Ошибка получения расчета для лизинга');
        }
        return new LeasingResponse(
                $result['Result']['MonthPay'],
                $result['Result']['SchedulePaymentTotal'],
                $result['Result']['NdsOffset'],
                $result['Result']['DecreaseCostsOfIncomeTax'],
                self::PROVIDER_ID
        );
    }
}