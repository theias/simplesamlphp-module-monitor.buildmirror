<?php

namespace SimpleSAML\Module\monitor\Test;

use \SimpleSAML\Module\monitor\TestCase as TestCase;
use \SimpleSAML\Module\monitor\TestData as TestData;
use \SimpleSAML\Module\monitor\State as State;

/**
 * Tests for TestCase\Metadata\Expiration
 */
class TestMetadataExpirationTest extends \PHPUnit_Framework_TestCase
{
    public function testMetadataExpired()
    {
        $testData = new TestData([
            'entityId' => 'https://example.org',
            'entityMetadata' => ['expire' => time() - 1000],
        ]);
        $expirationTest = new TestCase\Metadata\Expiration($testData);
        $testResult = $expirationTest->getTestResult();

        $this->assertEquals(State::ERROR, $testResult->getState());
    }

    public function testMetadataValid()
    {
        $testData = new TestData([
            'entityId' => 'https://example.org',
            'entityMetadata' => ['expire' => time() + 1000],
        ]);
        $expirationTest = new TestCase\Metadata\Expiration($testData);
        $testResult = $expirationTest->getTestResult();

        $this->assertEquals(State::OK, $testResult->getState());
    }

    public function testMetadataNeverExpires()
    {
        $testData = new TestData([
            'entityId' => 'https://example.org',
            'entityMetadata' => [],
        ]);
        $expirationTest = new TestCase\Metadata\Expiration($testData);
        $testResult = $expirationTest->getTestResult();

        $this->assertEquals(State::OK, $testResult->getState());
    }
}
