<?php

declare(strict_types=1);

namespace Wsign\Service;

use Wsign\ApiRequestor;
use Wsign\Exception\InvalidArgumentException;
use Wsign\HttpClient\HttpResponse;
use Wsign\RequestOptions;
use Wsign\Resource\AutoPagingIterator;
use Wsign\Resource\Collection;
use Wsign\Resource\ObjectFactory;
use Wsign\Resource\WsignObject;

/**
 * Shared plumbing for the resource services.
 */
abstract class AbstractService
{
    public function __construct(protected readonly ApiRequestor $requestor)
    {
    }

    /**
     * @template T of WsignObject
     *
     * @param class-string<T>                      $class
     * @param array<string, mixed>                 $params
     * @param array<string, mixed>|RequestOptions|null $options
     *
     * @return T
     */
    protected function requestObject(string $class, string $method, string $path, array $params = [], array|RequestOptions|null $options = null): WsignObject
    {
        $response = $this->requestor->request($method, $path, $params, RequestOptions::parse($options));

        return self::hydrate($class, $response);
    }

    /**
     * @template T of WsignObject
     *
     * @param class-string<T>                      $itemClass
     * @param array<string, mixed>                 $params
     * @param array<string, mixed>|RequestOptions|null $options
     *
     * @return Collection<T>
     */
    protected function requestCollection(string $itemClass, string $path, array $params = [], array|RequestOptions|null $options = null): Collection
    {
        $response = $this->requestor->request('GET', $path, $params, RequestOptions::parse($options));
        $object = ObjectFactory::fromResponse($response);

        $values = [];
        foreach ($object->keys() as $key) {
            $values[$key] = $object[$key];
        }
        $items = [];
        foreach (\is_array($values['data'] ?? null) ? $values['data'] : [] as $item) {
            $items[] = $item instanceof WsignObject && !$item instanceof $itemClass ? $itemClass::fromObject($item) : $item;
        }
        $values['data'] = $items;
        $values['object'] ??= 'list';

        /** @var Collection<T> $collection */
        $collection = new Collection($values, $response);

        return $collection;
    }

    /**
     * @template T of WsignObject
     *
     * @param class-string<T>                      $itemClass
     * @param array<string, mixed>                 $params
     * @param array<string, mixed>|RequestOptions|null $options
     *
     * @return AutoPagingIterator<T>
     */
    protected function autoPaging(string $itemClass, string $path, array $params = [], array|RequestOptions|null $options = null): AutoPagingIterator
    {
        $opts = RequestOptions::parse($options);

        return new AutoPagingIterator(fn (array $p): Collection => $this->requestCollection($itemClass, $path, $p, $opts), $params);
    }

    /**
     * @template T of WsignObject
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    protected static function hydrate(string $class, HttpResponse $response): WsignObject
    {
        $object = ObjectFactory::fromResponse($response);

        return $object instanceof $class ? $object : $class::fromObject($object);
    }

    /**
     * Builds a path, URL-encoding each id.
     */
    protected static function path(string $format, string ...$ids): string
    {
        foreach ($ids as $id) {
            if (trim($id) === '') {
                throw new InvalidArgumentException('The object id must be a non-empty string.');
            }
        }

        return vsprintf($format, array_map('rawurlencode', $ids));
    }
}
