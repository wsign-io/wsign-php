<?php

declare(strict_types=1);

namespace Wsign\Resource;

use Wsign\Exception\UnexpectedResponseException;
use Wsign\HttpClient\HttpResponse;

/**
 * Converts decoded JSON into typed objects, chosen by each JSON object's `object` field.
 *
 * @internal
 */
final class ObjectFactory
{
    /** @var list<class-string<WsignObject>> */
    private const CLASSES = [
        Account::class,
        Balance::class,
        BulkSend::class,
        Certificate::class,
        Collection::class,
        Document::class,
        Download::class,
        Event::class,
        EventRedelivery::class,
        Evidence::class,
        Flow::class,
        IdentityVerification::class,
        Reminder::class,
        Session::class,
        SignatureRequest::class,
        Signer::class,
        SigningSession::class,
        Template::class,
        Usage::class,
        Verification::class,
        WebhookDelivery::class,
        WebhookEndpoint::class,
        WebhookTestResult::class,
    ];

    /** @var array<string, class-string<WsignObject>>|null */
    private static ?array $map = null;

    /**
     * @throws UnexpectedResponseException
     */
    public static function fromResponse(HttpResponse $response): WsignObject
    {
        $decoded = self::decode($response->body, $response->status);
        if (!$decoded instanceof \stdClass) {
            throw new UnexpectedResponseException('The Wsign API returned JSON that is not an object.', $response->status, $response->body);
        }
        $object = self::convert($decoded);
        \assert($object instanceof WsignObject);

        return $object->withResponse($response);
    }

    /**
     * @throws UnexpectedResponseException
     */
    public static function decode(string $json, int $status = 0): mixed
    {
        try {
            return json_decode($json, false, 512, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (\JsonException $e) {
            throw new UnexpectedResponseException('The Wsign API returned a response that is not valid JSON: ' . $e->getMessage(), $status, $json, $e);
        }
    }

    /**
     * Recursively converts decoded JSON (objects as stdClass) into Wsign objects and lists.
     */
    public static function convert(mixed $value): mixed
    {
        if ($value instanceof \stdClass) {
            $values = [];
            foreach (get_object_vars($value) as $k => $v) {
                $values[$k] = self::convert($v);
            }
            $type = $values['object'] ?? null;
            $class = \is_string($type) ? (self::map()[$type] ?? WsignObject::class) : WsignObject::class;

            return new $class($values);
        }
        if (\is_array($value)) {
            return array_map(self::convert(...), $value);
        }

        return $value;
    }

    /**
     * Converts plain PHP arrays (associative = object, list = array) into the stdClass form.
     */
    public static function arrayToStd(mixed $value): mixed
    {
        if (!\is_array($value)) {
            return $value;
        }
        if (array_is_list($value) && $value !== []) {
            return array_map(self::arrayToStd(...), $value);
        }
        $obj = new \stdClass();
        foreach ($value as $k => $v) {
            $obj->{(string) $k} = self::arrayToStd($v);
        }

        return $obj;
    }

    /**
     * @return array<string, class-string<WsignObject>>
     */
    private static function map(): array
    {
        if (self::$map === null) {
            self::$map = [];
            foreach (self::CLASSES as $class) {
                self::$map[$class::OBJECT_NAME] = $class;
            }
        }

        return self::$map;
    }
}
