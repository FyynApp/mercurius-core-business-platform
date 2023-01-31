<?php

namespace App\VideoBasedMarketing\Settings\Infrastructure\MessageHandler;

use App\VideoBasedMarketing\Settings\Domain\Entity\CustomDomainSetting;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainDnsSetupStatus;
use App\VideoBasedMarketing\Settings\Domain\Enum\CustomDomainHttpSetupStatus;
use App\VideoBasedMarketing\Settings\Infrastructure\Message\CheckCustomDomainNameSetupCommandMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Throwable;

#[AsMessageHandler]
readonly class CheckCustomDomainNameSetupCommandMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface    $messageBus,
        private RouterInterface        $router,
        private LoggerInterface        $logger
    )
    {
    }

    public function __invoke(
        CheckCustomDomainNameSetupCommandMessage $message
    ): void
    {
        /** @var null|CustomDomainSetting $customDomainSetting */
        $customDomainSetting = $this->entityManager->find(
            CustomDomainSetting::class,
            $message->getCustomDomainSettingId()
        );

        if (is_null($customDomainSetting)) {
            throw new UnrecoverableMessageHandlingException(
                "Could not find customDomainSetting with id '{$message->getCustomDomainSettingId()}'."
            );
        }

        if (is_null($customDomainSetting->getDomainName())) {
            throw new UnrecoverableMessageHandlingException(
                "Domain name of custom domain setting '{$customDomainSetting->getId()}' is null."
            );
        }

        $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckRunning);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();

        $cnameRecord = dns_get_record(
            $customDomainSetting->getDomainName(),
            DNS_CNAME
        );

        if (   is_array($cnameRecord)
            && array_key_exists(0, $cnameRecord)
            && array_key_exists('target', $cnameRecord[0])
            && $cnameRecord[0]['target'] === "customdomain.{$_ENV['APP_ENV']}.fyyn.io"
        ) {
            $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckPositive);
            $this->entityManager->persist($customDomainSetting);
            $this->entityManager->flush();

            $checkUrl = "https://{$customDomainSetting->getDomainName()}{$this->router->generate('videobasedmarketing.settings.presentation.custom_domain.verify')}";

            $checkResult = false;

            try {
                $checkResult = file_get_contents($checkUrl);
            } catch (Throwable $t) {
                $this->logger->info("Got throwable '{$t->getMessage()}'.");
            }

            if ($checkResult !== 'This custom domain is working.') {

                $customDomainSetting->setHttpSetupStatus(CustomDomainHttpSetupStatus::CheckNegative);
                $this->entityManager->persist($customDomainSetting);
                $this->entityManager->flush();

                $fs = new Filesystem();
                $fs->mkdir('/var/tmp/mercurius-core-business-platform/customdomain_setup_tasks');
                file_put_contents(
                    "/var/tmp/mercurius-core-business-platform/customdomain_setup_tasks/{$customDomainSetting->getId()}.task",
                    $customDomainSetting->getDomainName()
                );


                sleep(5);

                $this->messageBus->dispatch(
                    new CheckCustomDomainNameSetupCommandMessage($customDomainSetting)
                );

                return;

            } else {
                $customDomainSetting->setHttpSetupStatus(CustomDomainHttpSetupStatus::CheckPositive);
                $this->entityManager->persist($customDomainSetting);
                $this->entityManager->flush();
            }

            return;
        }

        $customDomainSetting->setDnsSetupStatus(CustomDomainDnsSetupStatus::CheckNegative);
        $this->entityManager->persist($customDomainSetting);
        $this->entityManager->flush();

        sleep(5);

        $this->messageBus->dispatch(
            new CheckCustomDomainNameSetupCommandMessage($customDomainSetting)
        );
    }
}
