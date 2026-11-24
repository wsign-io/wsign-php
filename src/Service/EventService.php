<?php

declare(strict_types=1);

namespace Wsign\Service;

use Wsign\RequestOptions;
use Wsign\Resource\AutoPagingIterator;
use Wsign\Resource\Collection;
use Wsign\Resource\Event;
use Wsign\Resource\EventRedelivery;

final class EventService extends AbstractService
{
    /**
     * @param array<string, mixed>|RequestOptions|null $options
     */
    public function retrieve(string $id, array|RequestOptions|null $options = null): Event
    {
        return $this->requestObject(Event::class, 'GET', self::path('/events/%s', $id), [], $options);
    }

    /**
     * @param array{type?: string, limit?: int, starting_after?: string}|array<string, mixed> $params
     * @param array<string, mixed>|RequestOptions|null $options
     *
     * @return Collection<Event>
     */
    public function list(array $params = [], array|RequestOptions|null $options = null): Collection
    {
        return $this->requestCollection(Event::class, '/events', $params, $options);
    }

    /**
     * @param array<string, mixed>                     $params
     * @param array<string, mixed>|RequestOptions|null $options
     *
     * @return AutoPagingIterator<Event>
     */
    public function all(array $params = [], array|RequestOptions|null $options = null): AutoPagingIterator
    {
        return $this->autoPaging(Event::class, '/events', $params, $options);
    }

    /**
     * Delivers the event again, to one endpoint (`endpoint => 'we_…'`) or to every subscribed endpoint.
     *
     * @param array{endpoint?: string}|array<string, mixed> $params
     * @param array<string, mixed>|RequestOptions|null       $options
     */
    public function redeliver(string $id, array $params = [], array|RequestOptions|null $options = null): EventRedelivery
    {
        return $this->requestObject(EventRedelivery::class, 'POST', self::path('/events/%s/redeliver', $id), $params, $options);
    }
}
