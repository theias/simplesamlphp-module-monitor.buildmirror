<?php

namespace SimpleSAML\Module\monitor\TestSuite;

use SimpleSAML\Module\monitor\TestConfiguration;
use SimpleSAML\Module\monitor\TestCase;
use SimpleSAML\Module\monitor\TestData;
use SimpleSAML\Module\monitor\TestResult;

use function array_key_exists;
use function is_array;
use function preg_match;

final class Metadata extends \SimpleSAML\Module\monitor\TestSuiteFactory
{
    /** @var array */
    private $metadata = [];

    /** @var integer|null */
    private $certExpirationWarning = null;


    /**
     * @param \SimpleSAML\Module\monitor\TestConfiguration $configuration
     */
    public function __construct(TestConfiguration $configuration)
    {
        $moduleConfig = $configuration->getModuleConfig();
        $metadataConfig = $configuration->getMetadataConfig();
        $this->fixEntityIds($metadataConfig);

        $checkMetadata = $moduleConfig->getOptionalValue('checkMetadata', true);
        if ($checkMetadata === true) {
            $metadata = $metadataConfig;
        } else {
            $metadata = [];
            if (is_array($checkMetadata)) {
                foreach ($checkMetadata as $set => $entityIds) {
                    if (array_key_exists($set, $metadataConfig)) {
                        foreach ($entityIds as $entityId) {
                            if (array_key_exists($entityId, $metadataConfig[$set])) {
                                $metadata[$set][$entityId] = $metadataConfig[$set][$entityId];
                            }
                        }
                    }
                }
            }
        }

        $this->certExpirationWarning = $moduleConfig->getOptionalValue('certExpirationWarning', 28);

        $this->fixEntityIds($metadata);
        $this->metadata = $metadata;
        $this->setCategory('Metadata');

        parent::__construct($configuration);
    }


    /**
     * @return void
     */
    public function invokeTest(): void
    {
        $configuration = $this->getConfiguration();
        $output = [];

        foreach ($this->metadata as $set => $metadataSet) {
            foreach ($metadataSet as $entityId => $entityMetadata) {
                $input = [
                    'entityId' => $entityId,
                    'entityMetadata' => $entityMetadata
                ];
                $testData = new TestData($input);

                $metadataTest = new Metadata\Entity($configuration, $testData);
                $output[$entityId] = $metadataTest->getArrayizeTestResults();

                $this->addTestResults($metadataTest->getTestResults());
            }
        }

        $state = $this->calculateState();
        $testResult = new TestResult('Metadata entities');
        $testResult->setState($state);
        $testResult->setOutput($output);
        $this->setTestResult($testResult);
    }


    /**
     * @param array $metadata
     *
     * @return void
     */
    private function fixEntityIds(array &$metadata): void
    {
        foreach ($metadata as $set => $metadataSet) {
            foreach ($metadataSet as $entityId => $entityMetadata) {
                if (preg_match('/__DYNAMIC(:[0-9]+)?__/', $entityId)) {
                    // Remove old entry and create a new one based on new entityId
                    unset($metadata[$set][$entityId]);
                    $newEntityId = $entityMetadata['entityid'];
                    $metadata[$set][$newEntityId] = $entityMetadata;
                }
            }
        }
    }
}
