<?php

declare(strict_types=1);

namespace SimpleSAML\Module\monitor\TestCase\AuthSource\Ldap;

use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Module\ldap\Auth\Ldap;
use SimpleSAML\Module\monitor\State;
use SimpleSAML\Module\monitor\TestData;
use SimpleSAML\Module\monitor\TestResult;

use function intval;
use function ldap_explode_dn;
use function str_replace;
use function stripos;
use function strpos;
use function substr;

final class Search extends \SimpleSAML\Module\monitor\TestCaseFactory
{
    /** @var \SimpleSAML\Module\ldap\Auth\Ldap */
    private Ldap $connection;

    /** @var string */
    private string $base;

    /** @var string */
    private string $username;

    /** @var string */
    private string $password;

    /** @var array */
    private array $attributes = [];


    /**
     * @param \SimpleSAML\Module\monitor\TestData $testData
     *
     * @return void
     */
    protected function initialize(TestData $testData): void
    {
        $authSourceData = $testData->getInputItem('authSourceData');

        // Just to be on the safe side, strip off any OU's and search to whole directory
        $base = $authSourceData->getArrayizeString('search.base', '<< unset >>');
        $base = is_array($base) ? $base[0] : $base;
        if (($i = intval(stripos($base, 'DC='))) > 0) {
            $base = substr($base, $i);
        }
        $this->base = $base;

        $username = $authSourceData->getString('search.username', '<< unset >>');
        $this->setSubject($username);
        if (strpos($username, 'DC=') > 0) {
            // We have been given a DN
            $username = ldap_explode_dn($username, 1);
            $this->username = $username[0];
            $this->attributes = ['cn'];
        } else {
            // We have been given a sAMAccountName
            $this->username = $username;
            $this->attributes = ['sAMAccountName'];
        }
        $this->password = $authSourceData->getString('search.password', '<< unset >>');
        $this->connection = $testData->getInputItem('connection');

        parent::initialize($testData);
    }


    /**
     * @return void
     */
    public function invokeTest(): void
    {
        try {
            $this->connection->searchfordn($this->base, $this->attributes, $this->username);
        } catch (Error\Error $error) {
            // Fallthru
        }

        $testResult = new TestResult('LDAP Search', $this->getSubject());

        if (isset($error)) {
            // When you feed str_replace a string, outcome will be string too, but Psalm doesn't see it that way
            $msg = str_replace('Library - LDAP searchfordn(): ', '', $error->getMessage());
            $testResult->setState(State::ERROR);
            $testResult->setMessage($msg);
        } else {
            $testResult->setState(State::OK);
            $testResult->setMessage('Search succesful');
        }

        $this->setTestResult($testResult);
    }
}
