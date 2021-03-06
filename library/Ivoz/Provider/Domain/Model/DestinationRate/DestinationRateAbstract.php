<?php

namespace Ivoz\Provider\Domain\Model\DestinationRate;

use Assert\Assertion;
use Ivoz\Core\Application\DataTransferObjectInterface;
use Ivoz\Core\Domain\Model\ChangelogTrait;
use Ivoz\Core\Domain\Model\EntityInterface;

/**
 * DestinationRateAbstract
 * @codeCoverageIgnore
 */
abstract class DestinationRateAbstract
{
    /**
     * column: rate
     * @var float
     */
    protected $cost;

    /**
     * @var float
     */
    protected $connectFee;

    /**
     * @var string
     */
    protected $rateIncrement;

    /**
     * @var string
     */
    protected $groupIntervalStart = '0s';

    /**
     * @var \Ivoz\Cgr\Domain\Model\TpRate\TpRateInterface | null
     */
    protected $tpRate;

    /**
     * @var \Ivoz\Cgr\Domain\Model\TpDestinationRate\TpDestinationRateInterface | null
     */
    protected $tpDestinationRate;

    /**
     * @var \Ivoz\Provider\Domain\Model\DestinationRateGroup\DestinationRateGroupInterface
     */
    protected $destinationRateGroup;

    /**
     * @var \Ivoz\Provider\Domain\Model\Destination\DestinationInterface
     */
    protected $destination;


    use ChangelogTrait;

    /**
     * Constructor
     */
    protected function __construct(
        $cost,
        $connectFee,
        $rateIncrement,
        $groupIntervalStart
    ) {
        $this->setCost($cost);
        $this->setConnectFee($connectFee);
        $this->setRateIncrement($rateIncrement);
        $this->setGroupIntervalStart($groupIntervalStart);
    }

    abstract public function getId();

    public function __toString()
    {
        return sprintf(
            "%s#%s",
            "DestinationRate",
            $this->getId()
        );
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function sanitizeValues()
    {
    }

    /**
     * @param null $id
     * @return DestinationRateDto
     */
    public static function createDto($id = null)
    {
        return new DestinationRateDto($id);
    }

    /**
     * @internal use EntityTools instead
     * @param DestinationRateInterface|null $entity
     * @param int $depth
     * @return DestinationRateDto|null
     */
    public static function entityToDto(EntityInterface $entity = null, $depth = 0)
    {
        if (!$entity) {
            return null;
        }

        Assertion::isInstanceOf($entity, DestinationRateInterface::class);

        if ($depth < 1) {
            return static::createDto($entity->getId());
        }

        if ($entity instanceof \Doctrine\ORM\Proxy\Proxy && !$entity->__isInitialized()) {
            return static::createDto($entity->getId());
        }

        /** @var DestinationRateDto $dto */
        $dto = $entity->toDto($depth-1);

        return $dto;
    }

    /**
     * Factory method
     * @internal use EntityTools instead
     * @param DestinationRateDto $dto
     * @return self
     */
    public static function fromDto(
        DataTransferObjectInterface $dto,
        \Ivoz\Core\Application\ForeignKeyTransformerInterface $fkTransformer
    ) {
        Assertion::isInstanceOf($dto, DestinationRateDto::class);

        $self = new static(
            $dto->getCost(),
            $dto->getConnectFee(),
            $dto->getRateIncrement(),
            $dto->getGroupIntervalStart()
        );

        $self
            ->setTpRate($fkTransformer->transform($dto->getTpRate()))
            ->setTpDestinationRate($fkTransformer->transform($dto->getTpDestinationRate()))
            ->setDestinationRateGroup($fkTransformer->transform($dto->getDestinationRateGroup()))
            ->setDestination($fkTransformer->transform($dto->getDestination()))
        ;

        $self->initChangelog();

        return $self;
    }

    /**
     * @internal use EntityTools instead
     * @param DestinationRateDto $dto
     * @return self
     */
    public function updateFromDto(
        DataTransferObjectInterface $dto,
        \Ivoz\Core\Application\ForeignKeyTransformerInterface $fkTransformer
    ) {
        Assertion::isInstanceOf($dto, DestinationRateDto::class);

        $this
            ->setCost($dto->getCost())
            ->setConnectFee($dto->getConnectFee())
            ->setRateIncrement($dto->getRateIncrement())
            ->setGroupIntervalStart($dto->getGroupIntervalStart())
            ->setTpRate($fkTransformer->transform($dto->getTpRate()))
            ->setTpDestinationRate($fkTransformer->transform($dto->getTpDestinationRate()))
            ->setDestinationRateGroup($fkTransformer->transform($dto->getDestinationRateGroup()))
            ->setDestination($fkTransformer->transform($dto->getDestination()));



        return $this;
    }

    /**
     * @internal use EntityTools instead
     * @param int $depth
     * @return DestinationRateDto
     */
    public function toDto($depth = 0)
    {
        return self::createDto()
            ->setCost(self::getCost())
            ->setConnectFee(self::getConnectFee())
            ->setRateIncrement(self::getRateIncrement())
            ->setGroupIntervalStart(self::getGroupIntervalStart())
            ->setTpRate(\Ivoz\Cgr\Domain\Model\TpRate\TpRate::entityToDto(self::getTpRate(), $depth))
            ->setTpDestinationRate(\Ivoz\Cgr\Domain\Model\TpDestinationRate\TpDestinationRate::entityToDto(self::getTpDestinationRate(), $depth))
            ->setDestinationRateGroup(\Ivoz\Provider\Domain\Model\DestinationRateGroup\DestinationRateGroup::entityToDto(self::getDestinationRateGroup(), $depth))
            ->setDestination(\Ivoz\Provider\Domain\Model\Destination\Destination::entityToDto(self::getDestination(), $depth));
    }

    /**
     * @return array
     */
    protected function __toArray()
    {
        return [
            'rate' => self::getCost(),
            'connectFee' => self::getConnectFee(),
            'rateIncrement' => self::getRateIncrement(),
            'groupIntervalStart' => self::getGroupIntervalStart(),
            'tpRateId' => self::getTpRate() ? self::getTpRate()->getId() : null,
            'tpDestinationRateId' => self::getTpDestinationRate() ? self::getTpDestinationRate()->getId() : null,
            'destinationRateGroupId' => self::getDestinationRateGroup()->getId(),
            'destinationId' => self::getDestination()->getId()
        ];
    }
    // @codeCoverageIgnoreStart

    /**
     * Set cost
     *
     * @param float $cost
     *
     * @return static
     */
    protected function setCost($cost)
    {
        Assertion::notNull($cost, 'cost value "%s" is null, but non null value was expected.');
        Assertion::numeric($cost);

        $this->cost = (float) $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * Set connectFee
     *
     * @param float $connectFee
     *
     * @return static
     */
    protected function setConnectFee($connectFee)
    {
        Assertion::notNull($connectFee, 'connectFee value "%s" is null, but non null value was expected.');
        Assertion::numeric($connectFee);

        $this->connectFee = (float) $connectFee;

        return $this;
    }

    /**
     * Get connectFee
     *
     * @return float
     */
    public function getConnectFee(): float
    {
        return $this->connectFee;
    }

    /**
     * Set rateIncrement
     *
     * @param string $rateIncrement
     *
     * @return static
     */
    protected function setRateIncrement($rateIncrement)
    {
        Assertion::notNull($rateIncrement, 'rateIncrement value "%s" is null, but non null value was expected.');
        Assertion::maxLength($rateIncrement, 16, 'rateIncrement value "%s" is too long, it should have no more than %d characters, but has %d characters.');

        $this->rateIncrement = $rateIncrement;

        return $this;
    }

    /**
     * Get rateIncrement
     *
     * @return string
     */
    public function getRateIncrement(): string
    {
        return $this->rateIncrement;
    }

    /**
     * Set groupIntervalStart
     *
     * @param string $groupIntervalStart
     *
     * @return static
     */
    protected function setGroupIntervalStart($groupIntervalStart)
    {
        Assertion::notNull($groupIntervalStart, 'groupIntervalStart value "%s" is null, but non null value was expected.');
        Assertion::maxLength($groupIntervalStart, 16, 'groupIntervalStart value "%s" is too long, it should have no more than %d characters, but has %d characters.');

        $this->groupIntervalStart = $groupIntervalStart;

        return $this;
    }

    /**
     * Get groupIntervalStart
     *
     * @return string
     */
    public function getGroupIntervalStart(): string
    {
        return $this->groupIntervalStart;
    }

    /**
     * Set tpRate
     *
     * @param \Ivoz\Cgr\Domain\Model\TpRate\TpRateInterface $tpRate | null
     *
     * @return static
     */
    protected function setTpRate(\Ivoz\Cgr\Domain\Model\TpRate\TpRateInterface $tpRate = null)
    {
        $this->tpRate = $tpRate;

        return $this;
    }

    /**
     * Get tpRate
     *
     * @return \Ivoz\Cgr\Domain\Model\TpRate\TpRateInterface | null
     */
    public function getTpRate()
    {
        return $this->tpRate;
    }

    /**
     * Set tpDestinationRate
     *
     * @param \Ivoz\Cgr\Domain\Model\TpDestinationRate\TpDestinationRateInterface $tpDestinationRate | null
     *
     * @return static
     */
    protected function setTpDestinationRate(\Ivoz\Cgr\Domain\Model\TpDestinationRate\TpDestinationRateInterface $tpDestinationRate = null)
    {
        $this->tpDestinationRate = $tpDestinationRate;

        return $this;
    }

    /**
     * Get tpDestinationRate
     *
     * @return \Ivoz\Cgr\Domain\Model\TpDestinationRate\TpDestinationRateInterface | null
     */
    public function getTpDestinationRate()
    {
        return $this->tpDestinationRate;
    }

    /**
     * Set destinationRateGroup
     *
     * @param \Ivoz\Provider\Domain\Model\DestinationRateGroup\DestinationRateGroupInterface $destinationRateGroup
     *
     * @return static
     */
    public function setDestinationRateGroup(\Ivoz\Provider\Domain\Model\DestinationRateGroup\DestinationRateGroupInterface $destinationRateGroup)
    {
        $this->destinationRateGroup = $destinationRateGroup;

        return $this;
    }

    /**
     * Get destinationRateGroup
     *
     * @return \Ivoz\Provider\Domain\Model\DestinationRateGroup\DestinationRateGroupInterface
     */
    public function getDestinationRateGroup()
    {
        return $this->destinationRateGroup;
    }

    /**
     * Set destination
     *
     * @param \Ivoz\Provider\Domain\Model\Destination\DestinationInterface $destination
     *
     * @return static
     */
    public function setDestination(\Ivoz\Provider\Domain\Model\Destination\DestinationInterface $destination)
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * Get destination
     *
     * @return \Ivoz\Provider\Domain\Model\Destination\DestinationInterface
     */
    public function getDestination()
    {
        return $this->destination;
    }

    // @codeCoverageIgnoreEnd
}
