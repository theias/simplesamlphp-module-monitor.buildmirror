<?php

namespace SimpleSAML\Module\monitor\TestCase\AuthSource\Ldap;

use \SimpleSAML\Module\monitor\State as State;
use \SimpleSAML\Module\monitor\TestData as TestData;
use \SimpleSAML\Module\monitor\TestResult as TestResult;

final class Configuration extends \SimpleSAML\Module\monitor\TestCaseFactory
{
    /*
     * @var \SimpleSAML_Auth_LDAP
     */
    private $connection;

    /*
     * @var string
     */
    private $hostname;

    /*
     * @var integer
     */
    private $port;

    /*
     * @var bool
     */
    private $enableTls;

    /*
     * @var integer
     */
    private $timeout;

    /*
     * @var bool
     */
    private $referrals;

    /*
     * @var bool
     */
    private $debug;


    /*
     * @param TestData $testData
     *
     * @return void
     */
    protected function initialize($testData)
    {
        $authSourceData = $testData->getInputItem('authSourceData');
        $this->hostname = $authSourceData->getString('hostname', '<< unset >>');
        $this->port = $authSourceData->getInteger('port', 636);
        $this->enableTls = $authSourceData->getBoolean('enable_tls', false);
        $this->timeout = $authSourceData->getInteger('timeout', 3);
        $this->referrals = $authSourceData->getBoolean('referrals', false);
        $this->debug = $authSourceData->getBoolean('debug', false);

        $this->setSubject($this->hostname);

        parent::initialize($testData);
    }


    /*
     * @return void
     */
    public function invokeTest()
    {
        if (preg_match('/^(ldap[s]?:\/\/(.*))$/', $this->hostname, $matches)) {
            $connectString = $this->hostname;
        } else {
            $connectString = $this->hostname .':'.$this->port;
        }

        $testResult = new TestResult('LDAP configuration', $connectString);

        try {
            $this->connection = new \SimpleSAML_Auth_LDAP(
                $this->hostname,
                $this->enableTls,
                $this->debug,
                $this->timeout,
                $this->port,
                $this->referrals
            );
            $state = State::OK;
        } catch (\Exception $error) {
            $state = State::FATAL;
        }

        if (isSet($error)) {
            $msg = str_replace('Library - LDAP __construct(): ', '', $error->getMessage());
        } else {
            $msg = 'Configuration syntax OK';
            $testResult->addOutput($this->connection, 'connection');
        }

        $testResult->setState($state);
        $testResult->setMessage($msg);
        $this->setTestResult($testResult);
    }
}