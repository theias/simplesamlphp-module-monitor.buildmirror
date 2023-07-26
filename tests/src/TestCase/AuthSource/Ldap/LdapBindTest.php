<?php

declare(strict_types=1);

namespace SimpleSAML\Module\monitor\Test;

use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\ldap\Connector\Ldap;
use SimpleSAML\Module\monitor\TestCase;
use SimpleSAML\Module\monitor\TestData;
use SimpleSAML\Module\monitor\State;

/**
 * Tests for TestCase\Ldap\Bind
 */
class TestLdapBindTest extends \PHPUnit\Framework\TestCase
{
    public function testBindSuccesful(): void
    {
        $authSourceData = [
            'search.username' => 'testuser',
            'search.password' => 'password',
        ];
        $connectionMock = $this->getMockBuilder(Ldap::class)->onlyMethods(
            ['bind']
        )->disableOriginalConstructor()->getMock();
        $connectionMock->expects($this->once())->method('bind')->will($this->returnValue(true));
        $confTest = new TestCase\AuthSource\Ldap\Bind(
            new TestData([
                'authSourceData' => Configuration::loadFromArray($authSourceData),
                'connection' => $connectionMock,
            ])
        );
        $testResult = $confTest->getTestResult();

        $this->assertEquals(State::OK, $testResult->getState());
    }

    public function testBindFailed(): void
    {
        $authSourceData = [
            'search.username' => 'testuser',
            'search.password' => 'password',
        ];
        $connectionMock = $this->getMockBuilder(Ldap::class)->onlyMethods(
            ['bind']
        )->disableOriginalConstructor()->getMock();
        $connectionMock->expects($this->once())->method('bind')->will(
            $this->throwException(new Error\Error('Invalid credentials.'))
        );

        $testResult = new TestCase\AuthSource\Ldap\Bind(
            new TestData([
                'authSourceData' => Configuration::loadFromArray($authSourceData),
                'connection' => $connectionMock,
            ])
        );

        $this->expectException(Error\Error::class);
        $this->expectExceptionMessage('Invalid credentials.');
        $testResult->getTestResult();
    }
}
