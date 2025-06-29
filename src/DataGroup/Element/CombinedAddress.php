<?php declare(strict_types=1);

namespace Sprain\SwissQrBill\DataGroup\Element;

use Sprain\SwissQrBill\DataGroup\AddressInterface;
use Sprain\SwissQrBill\DataGroup\Element\Abstracts\Address;
use Sprain\SwissQrBill\DataGroup\QrCodeableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableInterface;
use Sprain\SwissQrBill\Validator\SelfValidatableTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * @deprecated The CombinedAdress is no longer supported by v2.3 of the QR bill specifications, valid from 21 November 2025.
 * This class will be removed in a future major version of this library. Use StructuredAddress instead.
 * For UltimateDebtor, you can also choose to set no data at all.
 */
class CombinedAddress extends Address implements AddressInterface, SelfValidatableInterface, QrCodeableInterface
{
    use SelfValidatableTrait;

    public const ADDRESS_TYPE = 'K';

    private function __construct(
        /**
         * Name or company
         */
        private string $name,

        /**
         * Address line 1
         *
         * Street and building number or P.O. Box
         */
        private ?string $addressLine1,

        /**
         * Address line 2
         *
         * Postal code and town
         */
        private string $addressLine2,

        /**
         * Country (ISO 3166-1 alpha-2)
         */
        private string $country
    ) {
        $this->name = self::normalizeString($name);
        $this->addressLine1 = self::normalizeString($addressLine1);
        $this->addressLine2 = self::normalizeString($addressLine2);
        $this->country = strtoupper((string) self::normalizeString($country));
    }

    public static function create(
        string $name,
        ?string $addressLine1,
        string $addressLine2,
        string $country
    ): self {
        return new self(
            $name,
            $addressLine1,
            $addressLine2,
            $country
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddressLine1(): ?string
    {
        return $this->addressLine1;
    }

    public function getAddressLine2(): string
    {
        return $this->addressLine2;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function getFullAddress(bool $forReceipt = false): string
    {
        $lines[1] = $this->getName();

        if ($this->getAddressLine1()) {
            $lines[2] = $this->getAddressLine1();
        }

        if ('CH' === $this->getCountry()) {
            $lines[3] = $this->getAddressLine2();
        } else {
            $lines[3] = sprintf("%s-%s", $this->getCountry(), $this->getAddressLine2());
        }

        if ($forReceipt) {
            $lines = self::clearMultilines($lines);
        }

        return implode("\n", $lines);
    }

    public function getQrCodeData(): array
    {
        return [
            $this->getAddressLine2() ? self::ADDRESS_TYPE : '',
            $this->getName(),
            $this->getAddressLine1(),
            $this->getAddressLine2(),
            '',
            '',
            $this->getCountry()
        ];
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraints('name', [
            new Assert\NotBlank(),
            new Assert\Length(
                max: 70
            )
        ]);

        $metadata->addPropertyConstraints('addressLine1', [
            new Assert\Length(
                max: 70
            )
        ]);

        $metadata->addPropertyConstraints('addressLine2', [
            new Assert\NotBlank(),
            new Assert\Length(
                max: 70
            )
        ]);

        $metadata->addPropertyConstraints('country', [
            new Assert\NotBlank(),
            new Assert\Country()
        ]);
    }
}
