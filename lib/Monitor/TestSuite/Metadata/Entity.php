<?php

namespace SimpleSAML\Module\monitor\TestSuite\Metadata;

use \SimpleSAML\Module\monitor\State as State;
use \SimpleSAML\Module\monitor\TestConfiguration as TestConfiguration;
use \SimpleSAML\Module\monitor\TestCase as TestCase;
use \SimpleSAML\Module\monitor\TestData as TestData;
use \SimpleSAML\Module\monitor\TestResult as TestResult;

final class Entity extends \SimpleSAML\Module\monitor\TestSuiteFactory
{
    /**
     * @var array
     */
    private $entityMetadata;

    /**
     * @var string
     */
    private $entityId;

    /**
     * @param integer|null;
     */
    private $certExpirationWarning = null;

    /**
     * @param TestConfiguration $configuration
     * @param TestData $testData
     */
    public function __construct($configuration, $testData)
    {
        $moduleConfig = $configuration->getModuleConfig();
        $entityMetadata = $testData->getInputItem('entityMetadata');
        $entityId = $testData->getInputItem('entityId');

        assert(is_array($entityMetadata));
        assert(is_string($entityId));

        $this->certExpirationWarning = $moduleConfig->getValue('certExpirationWarning', 28);
        $this->entityMetadata = $entityMetadata;
        $this->entityId = $entityId;

        $this->setCategory('Metadata entity');
        parent::__construct($configuration);
    }

    /**
     * @return void
     */
    public function invokeTest()
    {
        $input = [
            'entityId' => $this->entityId,
            'entityMetadata' => $this->entityMetadata,
        ];
        $testData = new TestData($input);

        $expTest = new TestCase\Metadata\Expiration($testData);
        $expTestResult = $expTest->getTestResult();
        $expTestResult->setSubject($this->entityId);
        $this->addTestResult($expTestResult);

        if (array_key_exists('keys', $this->entityMetadata)) {
            $keys = $this->entityMetadata['keys'];
            foreach ($keys as $key) {
                $input = array(
                    'category' => $this->getType($key),
                    'certData' => "-----BEGIN CERTIFICATE-----\n" .chunk_split($key['X509Certificate'], 64)."-----END CERTIFICATE-----\n",
                    'certExpirationWarning' => $this->certExpirationWarning,
                );
                $testData = new TestData($input);

                $certTest = new TestCase\Cert\Data($testData);
                $certTestResult = $certTest->getTestResult();

                $this->addTestResult($certTestResult);
            }
        } else {
            // saml20-idp-hosted
            $files = [];
            if (array_key_exists('certificate', $this->entityMetadata)) {
                $files[] = $this->entityMetadata['certificate'];
            }
            if (array_key_exists('new_certificate', $this->entityMetadata)) {
                $files[] = $this->entityMetadata['new_certificate'];
            }

            foreach ($files as $file) {
                $input = [
                    'category' => $this->getType(['signing' => true]),
                    'certFile' => \SimpleSAML\Utils\Config::getCertPath($file),
                    'certExpirationWarning' => $this->certExpirationWarning,
                ];

                $testData = new TestData($input);

                $certTest = new TestCase\Cert\File($testData);
                $certTestResult = $certTest->getTestResult();

                $this->addTestResult($certTestResult);
            }
        }

        $state = $this->calculateState();

        $testResult = new TestResult('Metadata endpoint');
        $testResult->setState($state);
        $this->setTestResult($testResult);
    }

    /**
     * @param array $key
     *
     * @return string
     */
    public function getType($key)
    {
        if ($key['encryption'] === true && $key['signing'] === false) {
            $category = 'Encryption certificate';
        } elseif ($key['encryption'] === false && $key['signing'] === true) {
            $category = 'Signing certificate';
        } else {
            $category = 'Unknown type';
        }
        return $category;
    }
}
