<?php

namespace App\VideoBasedMarketing\Membership\Domain\Entity;

use App\VideoBasedMarketing\Membership\Domain\Enum\Capability;
use App\VideoBasedMarketing\Membership\Domain\Enum\MembershipPlanName;
use ValueError;


readonly class MembershipPlan
{
    private MembershipPlanName $name;

    private bool $mustBeBought;

    private float $pricePerMonth;

    private float $pricePerYear;

    private array $capabilities;

    private int $amountOfLingoSyncCreditsPerMonth;

    private int $maxRecordingTimeInSeconds;

    private int $maxVideoUploadFilesizeInBytes;


    /** @param array|Capability[] $capabilities */
    public function __construct(
        MembershipPlanName $name,
        bool               $mustBeBought,
        float              $pricePerMonth,
        float              $pricePerYear,
        array              $capabilities,
        int                $amountOfLingoSyncCreditsPerMonth,
        int                $maxRecordingTimeInSeconds,
        int                $maxVideoUploadFilesizeInBytes
    )
    {
        $this->name = $name;
        $this->mustBeBought = $mustBeBought;
        $this->pricePerMonth = $pricePerMonth;
        $this->pricePerYear = $pricePerYear;

        /** @var Capability $capability */
        foreach ($capabilities as $key => $capability) {
            if (get_class($capability) !== Capability::class) {
                throw new ValueError('$capabilities['. $key .'] has class ' . get_class($capability) . '.');
            }
        }

        $this->capabilities = $capabilities;

        $this->amountOfLingoSyncCreditsPerMonth = $amountOfLingoSyncCreditsPerMonth;
        $this->maxRecordingTimeInSeconds = $maxRecordingTimeInSeconds;
        $this->maxVideoUploadFilesizeInBytes = $maxVideoUploadFilesizeInBytes;
    }

    public function getName(): MembershipPlanName
    {
        return $this->name;
    }

    public function getNiceName(): string
    {
        return ucfirst($this->name->value);
    }

    public function mustBeBought(): bool
    {
        return $this->mustBeBought;
    }

    public function getPricePerMonth(): float
    {
        return $this->pricePerMonth;
    }

    public function getPricePerYear(): float
    {
        return $this->pricePerYear;
    }

    public function hasCapability(Capability $capability): bool
    {
        return in_array($capability, $this->capabilities);
    }

    /**
     * @return Capability[]
     */
    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    public function getAmountOfLingoSyncCreditsPerMonth(): int
    {
        return $this->amountOfLingoSyncCreditsPerMonth;
    }

    public function getMaxRecordingTimeInSeconds(): int
    {
        return $this->maxRecordingTimeInSeconds;
    }

    public function getMaxVideoUploadFilesizeInBytes(): int
    {
        return $this->maxVideoUploadFilesizeInBytes;
    }
}
