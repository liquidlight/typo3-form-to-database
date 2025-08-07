<?php

declare(strict_types=1);

/**
 * This file is part of the "form_to_database" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Lavitto\FormToDatabase\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

/**
 * Class FormResult
 */
class FormResult extends AbstractEntity
{
    /**
     * The formPersistenceIdentifier
     *
     * @see FormDefinition::persistenceIdentifier
     */
    protected string $formPersistenceIdentifier = '';

    /**
     * Unique form identifier
     *
     * @see config/sites/{identifier}/config.yaml
     */
    protected string $formIdentifier = '';

    /**
     * The Site Identifier
     *
     * @see config/sites/{identifier}/config.yaml
     */
    protected string $siteIdentifier = '';

    /**
     * Uid of the form plugin content element
     */
    protected int $formPluginUid = 0;

    /**
     * The form result as json
     */
    protected string $result = '';

    /**
     * Create date
     */
    protected \DateTime $crdate;

    /**
     * Timestamp
     */
    protected \DateTime $tstamp;

    /**
     * Gets the formPersistenceIdentifier
     */
    public function getFormPersistenceIdentifier(): string
    {
        return $this->formPersistenceIdentifier;
    }

    /**
     * Sets the formPersistenceIdentifier
     *
     * @param string $formPersistenceIdentifier
     */
    public function setFormPersistenceIdentifier(string $formPersistenceIdentifier): void
    {
        $this->formPersistenceIdentifier = $formPersistenceIdentifier;
    }

    /**
     * Gets the siteIdentifier
     *
     * @return string
     */
    public function getFormIdentifier(): string
    {
        return $this->formIdentifier;
    }

    /**
     * @param string $formIdentifier
     */
    public function setFormIdentifier(string $formIdentifier): void
    {
        $this->formIdentifier = $formIdentifier;
    }

    /**
     * @return string
     */
    public function getSiteIdentifier(): string
    {
        return $this->siteIdentifier;
    }

    /**
     * Sets the siteIdentifier
     *
     * @param string $siteIdentifier
     */
    public function setSiteIdentifier(string $siteIdentifier): void
    {
        $this->siteIdentifier = $siteIdentifier;
    }

    /**
     * Gets the formPluginUid
     *
     * @return int
     */
    public function getFormPluginUid(): int
    {
        return $this->formPluginUid;
    }

    /**
     * Sets the formPluginUid
     *
     * @param int $formPluginUid
     */
    public function setFormPluginUid(int $formPluginUid): void
    {
        $this->formPluginUid = $formPluginUid;
    }

    /**
     * Gets the result
     *
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * Gets the result as an array
     *
     * @return array<array-key, mixed>
     * @throws \JsonException
     */
    public function getResultAsArray(): array
    {
        return $this->result !== '' ? json_decode($this->result, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * Sets the result
     *
     * @param string $result
     */
    public function setResult(string $result): void
    {
        $this->result = $result;
    }

    /**
     * Sets the result from an array
     *
     * @param array<array-key, mixed> $resultArray
     * @throws \JsonException
     */
    public function setResultFromArray(array $resultArray): void
    {
        $this->setResult(!empty($resultArray) ? json_encode($resultArray, JSON_THROW_ON_ERROR) : '');
    }

    /**
     * Gets the crdate
     *
     * @return \DateTime
     */
    public function getCrdate(): \DateTime
    {
        return $this->crdate;
    }

    /**
     * Sets the crdate
     *
     * @param \DateTime $crdate
     */
    public function setCrdate(\DateTime $crdate): void
    {
        $this->crdate = $crdate;
    }

    /**
     * Gets the tstamp
     *
     * @return \DateTime
     */
    public function getTstamp(): \DateTime
    {
        return $this->tstamp;
    }

    /**
     * Sets the tstamp
     *
     * @param \DateTime $tstamp
     */
    public function setTstamp(\DateTime $tstamp): void
    {
        $this->tstamp = $tstamp;
    }
}
