<?php

namespace App\Domain\Core\Subscription\Command;

use App\Domain\Core\Subscription\Partner\SubscriptionPartnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Traversable;

class LoadPartnersSubscriptionDataCommand extends Command
{
    /** @var Traversable|SubscriptionPartnerInterface[] */
    private Traversable $partners;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('subscription:data:load');
    }

    public function __construct(
        string $name = null,
        Traversable $partners
    )
    {
        parent::__construct($name);
        $this->partners = $partners;
    }


    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach($this->partners as $partner) {
            $partner->loadData();
        }

        return 1;
    }
}

