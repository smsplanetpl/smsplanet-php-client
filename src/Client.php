<?php

namespace SMSPLANET\PHP;

use Carbon\Carbon;
use DOMDocument;
use SMSPLANET\PHP\Client\Exceptions\InvalidParameterException;
use SMSPLANET\PHP\Client\Exceptions\MissingParameterException;
use SMSPLANET\PHP\Client\Exceptions\RequestException;

use function GuzzleHttp\Psr7\mimetype_from_filename;

class Client
{
    protected const API_URL = 'https://api.smsplanet.pl';

    protected const REQUIRED_PARAMS = ['from', 'msg', 'to'];

    /** @var \GuzzleHttp\Client */
    protected $client;

    /** @var array Credentials data */
    protected $credentials;

    /** @var array Message recipients */
    protected $recipients = [];

    /**
     * Client constructor.
     *
     * @param array $credentials
     */
    public function __construct(array $credentials)
    {
        $this->credentials = $credentials;

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => self::API_URL,
            'timeout' => 30
        ]);
    }

    /**
     * Send simple SMS
     *
     * @param array $args
     * @return int|null
     * @throws InvalidParameterException
     * @throws MissingParameterException
     * @throws RequestException
     */
    public function sendSimpleSMS(array $args)
    {
        $this->checkRequestParams($args, self::REQUIRED_PARAMS, self::REQUIRED_PARAMS);

        $query = http_build_query([
            'key' => $this->credentials['key'],
            'password' => $this->credentials['password'],
            'from' => $args['from'],
            'msg' => $args['msg'],
            'to' => $args['to'],
        ]);

        $response = $this->client->get('send', [
            'query' => $query
        ]);

        return $this->getSendMessageResponse($response);
    }

    /**
     * Send SMS
     *
     * @param array $args
     * @return int|null
     * @throws InvalidParameterException
     * @throws MissingParameterException
     * @throws RequestException
     */
    public function sendSMS(array $args)
    {
        $optional_params = ['from', 'msg', 'to', 'date', 'clear_polish', 'test'];
        $this->checkRequestParams($args, self::REQUIRED_PARAMS, $optional_params);
        $this->getRecipientsFromRequest($args);

        $dataToParse = [
            'key' => $this->credentials['key'],
            'password' => $this->credentials['password'],
            'sms' => $args,
        ];

        $body = $this->arrayToXml($dataToParse, '<data/>');

        $response = $this->client->post('', [
            'form_params' => [
                'xmldata' => $this->setToElementsToXml($body)
            ]
        ]);

        return $this->getSendMessageResponse($response);
    }

    /**
     * Send MMS
     *
     * @param array $args
     * @return int|null
     * @throws InvalidParameterException
     * @throws MissingParameterException
     * @throws RequestException
     */
    public function sendMMS(array $args)
    {
        $optional_params = ['from', 'msg', 'title', 'to', 'date', 'attachments', 'clear_polish', 'test'];
        $this->checkRequestParams($args, self::REQUIRED_PARAMS, $optional_params);
        $this->getRecipientsFromRequest($args);

        $dom = new DOMDocument('1.0', 'utf-8');

        $dom->appendChild($data = $dom->createElement('data'));

        $data->appendChild($dom->createElement('key', $this->credentials['key']));
        $data->appendChild($dom->createElement('password', $this->credentials['password']));
        $data->appendChild($mms = $dom->createElement('mms'));

        $mms->appendChild($dom->createElement('to', ''));
        $mms->appendChild($dom->createElement('from', $args['from']));

        if (isset($args['title'])) {
            $mms->appendChild($dom->createElement('title', $args['title']));
        }

        $mms->appendChild($dom->createElement('msg', $args['msg']));

        if (isset($args['clear_polish'])) {
            $mms->appendChild($dom->createElement('clear_polish', 1));
        }

        if (isset($args['date'])) {
            $mms->appendChild($dom->createElement('date', $args['date']));
        }

        $mms->appendChild($attachments = $dom->createElement('attachments'));

        if (isset($args['attachments'])) {
            if (!is_array($args['attachments'])) {
                $args['attachments'] = [$args['attachments']];
            }

            foreach ($args['attachments'] as $index => $attachment) {
                $attachments->appendChild($att = $dom->createElement('att'));

                $att->appendChild($nr = $dom->createAttribute('nr'));
                $nr->value = $index + 1;

                $att->appendChild($link = $dom->createAttribute('link'));
                $link->value = $attachment;

                $att->appendChild($mimetype = $dom->createAttribute('mimetype'));
                $mimetype->value = mimetype_from_filename($attachment);
            }
        }

        if (isset($args['test'])) {
            $mms->appendChild($dom->createElement('test', $args['test']));
        }

        $body = $dom->saveXML();

        $response = $this->client->post('', [
            'form_params' => [
                'xmldata' => $this->setToElementsToXml($body)
            ]
        ]);

        return $this->getSendMessageResponse($response);
    }

    /**
     * Get send message response
     *
     * @param $response
     * @return int|null
     * @throws RequestException
     */
    protected function getSendMessageResponse($response)
    {
        $content = $response->getBody()->getContents();
        $xml = $this->parseXmlResponse($content);

        if ($xml->messageId > 0) {
            return intval($xml->messageId);
        }

        return null;
    }

    /**
     * Get sender fields
     *
     * @param string $product
     * @return array
     */
    public function getSenderFields($product = 'SMS')
    {
        $response = $this->client->post('senderFields', [
            'form_params' => [
                'key' => $this->credentials['key'],
                'password' => $this->credentials['password'],
                'product' => $product
            ],
        ]);

        $content = $response->getBody()->getContents();
        return \GuzzleHttp\json_decode($content);
    }

    /**
     * Get balance
     *
     * @param string $product
     * @return int
     */
    public function getBalance($product = 'SMS')
    {
        $response = $this->client->post('getBalance', [
            'form_params' => [
                'key' => $this->credentials['key'],
                'password' => $this->credentials['password'],
                'product' => $product
            ]
        ]);

        $content = $response->getBody()->getContents();
        return intval($content);
    }

    /**
     * Cancel message
     *
     * @param $message_id
     * @return \SimpleXMLElement
     * @throws RequestException
     */
    public function cancelMessage($message_id)
    {
        $response = $this->client->post('cancelMessage', [
            'form_params' => [
                'key' => $this->credentials['key'],
                'password' => $this->credentials['password'],
                'messageId' => $message_id
            ]
        ]);

        $content = $response->getBody()->getContents();
        return $this->parseXmlResponse($content);
    }

    /**
     * Get message status
     *
     * @param $message_id
     * @return array
     * @throws RequestException
     */
    public function getMessageStatus($message_id): array
    {
        $response = $this->client->post('getMessageStatus', [
            'form_params' => [
                'key' => $this->credentials['key'],
                'password' => $this->credentials['password'],
                'messageId' => $message_id
            ]
        ]);

        $content = $response->getBody()->getContents();
        $xml = $this->parseXmlResponse($content);
        $resultMessage = $xml->resultMessage;

        $result = [
            'from' => [],
            'to' => []
        ];

        [$from, $to] = preg_split("!\n\n\n!", $resultMessage);

        foreach (explode("\n", $from) as $line) {
            if ($line) {
                [$key, $value] = preg_split('!:\s+!', $line);
                if ($key == 'Data wysyłki') {
                    $value = Carbon::parse($value)->toDateTimeString();
                }

                $result['from'] += [$key => $value];
            }
        }

        $csv = [];
        $i = 0;
        foreach (explode("\n", $to) as $line) {
            if ($line) {
                $data = str_getcsv($line, ';', '"', '');

                if ($i > 0) {
                    $data[2] = Carbon::parse($data[2])->toDateTimeString();
                }

                $csv[] = $data;
            }
            $i++;
        }

        array_walk($csv, function (&$a) use ($csv) {
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv);

        $result['to'] = $csv;

        return $result;
    }

    /**
     * Parse API response as XML
     *
     * @param $content
     * @return \SimpleXMLElement
     * @throws RequestException
     */
    protected function parseXMLResponse($content): \SimpleXMLElement
    {
        $xml = simplexml_load_string($content);

        if ($xml->result == 'ERROR') {
            throw new RequestException($xml->errorMsg, intval($xml->errorCode));
        }

        return $xml;
    }

    /**
     * Check request params
     *
     * @param array $args
     * @param array $required_params
     * @param array $optional_params
     * @throws InvalidParameterException
     * @throws MissingParameterException
     */
    protected function checkRequestParams(array &$args, array $required_params, array $optional_params): void
    {
        // Check required params

        if (count($result = array_diff($required_params, array_keys($args)))) {
            throw new MissingParameterException(join(', ', $result));
        }

        // Search invalid params

        if (count($result = array_diff(array_keys($args), $optional_params))) {
            throw new InvalidParameterException(join(', ', $result));
        }

        // Parse optional params

        if (isset($args['date'])) {
            $args['date'] = Carbon::parse($args['date'])->format('U');
        }
    }

    /**
     * Parse array as XML
     *
     * @param $array
     * @param null $rootElement
     * @param null $xml
     * @return mixed
     */
    protected function arrayToXml($array, $rootElement = null, $xml = null)
    {
        $_xml = $xml;

        if ($_xml === null) {
            $_xml = new \SimpleXMLElement($rootElement !== null ? $rootElement : '<data/>');
        }

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $this->arrayToXml($v, $k, $_xml->addChild($k));
            } else {
                $_xml->addChild($k, $v);
            }
        }

        return $_xml->asXML();
    }

    /**
     * Read [to] from *ARGS
     *
     * @param $args
     */
    protected function getRecipientsFromRequest(&$args): void
    {
        $this->recipients = [];
        if (is_array($args['to'])) {
            foreach ($args['to'] as $to) {
                $this->recipients[] = $to;
            }
        } else {
            $this->recipients[] = $args['to'];
        }

        // Clear
        $args['to'] = '';
    }

    /**
     * Replace [to] in request
     *
     * @param $body_xml
     * @return string
     */
    protected function setToElementsToXml($body_xml): string
    {
        $toXml = '';
        foreach ($this->recipients as $to) {
            $toXml .= "<to>$to</to>";
        }

        return str_replace('<to/>', $toXml, $body_xml);
    }
}
