<?php declare(strict_types=1);

namespace Twikey\Api;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class TwikeyTest extends TestCase
{
    private static $APIKEY;
    private static $CT;
    private static $httpClient;

    /**
     * @beforeClass
     */
    public static function setupBase(): void  {
        self::$APIKEY = getenv('APIKEY');
        self::$CT = getenv('CT');
        self::$httpClient = new Client([
            'http_errors'=>false,
            'debug'=>false
        ]);
    }

    public function testCreateDocument()
    {
        if( ! self::$APIKEY )
            throw new \InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$httpClient);
        $twikey->setTestmode(true);
        $twikey->setApiKey(self::$APIKEY);

        $data =  array(
            "ct" => self::$CT, // see Settings > Template
            "email" => "john@doe.com",
            "firstname" => "John",
            "lastname" => "Doe",
            "l" => "en",
            "address" => "Abbey road",
            "city" => "Liverpool",
            "zip" => "1526",
            "country" => "BE",
            "mobile" => "",
            "companyName" => "",
            "form" => "",
            "vatno" => "",
            "iban" => "",
            "bic" => "",
            "mandateNumber" => "",
            "contractNumber" => "",
        );

        $contract = $twikey->createNew($data);
        $this->assertIsString($contract->url);
        $this->assertIsString($contract->mndtId);
        $this->assertIsString($contract->key);

        // Remove the mandate again
        $twikey->cancelMandate($contract->mndtId);
    }

    public function testCreateTransaction()
    {
        if( ! self::$APIKEY )
            throw new \InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$httpClient);
        $twikey->setTestmode(true);
        $twikey->setApiKey(self::$APIKEY);

        $data =  array(
            "mndtId" => "CORERECURRENTNL16318",
            "message" => "Test Message",
            "ref" => "Merchant Reference",
            "amount" => 10.00, // 10 euro
            "place" => "Here"
        );

        $tx = $twikey->newTransaction($data);
        $this->assertIsNumeric($tx->Entries[0]->id);
        $this->assertIsNumeric($tx->Entries[0]->contractId);
        $this->assertNotEmpty($tx->Entries[0]->date);
    }

    public function testTransactionFeed()
    {
        if( ! self::$APIKEY )
            throw new \InvalidArgumentException('Invalid apikey');

        $twikey = new Twikey(self::$httpClient);
        $twikey->setTestmode(true);
        $twikey->setApiKey(self::$APIKEY);

        $txs = $twikey->getTransactionFeed();
        $this->assertIsArray($txs->Entries);
        while(count($txs->Entries) > 0){
            for ($i = 0; $i < count($txs->Entries); $i++)  {
                $tx = $txs->Entries[$i];
                $this->assertIsNumeric($tx->id);
                $this->assertIsNumeric($tx->contractId);
                $this->assertNotEmpty($tx->date);
            }
            $txs = $twikey->getTransactionFeed();
        }
    }

    public function testWebhook()
    {
        $twikey = new Twikey(self::$httpClient);
        $twikey->setTestmode(true);
        $twikey->setApiKey('1234');

        $this->assertTrue($twikey->validateWebhook("abc=123&name=abc","55261CBC12BF62000DE1371412EF78C874DBC46F513B078FB9FF8643B2FD4FC2"));

    }
}
