<?php

declare(strict_types=1);

namespace Wsign\Service;

use Wsign\RequestOptions;
use Wsign\Resource\Document;

final class DocumentService extends AbstractService
{
    /**
     * @param array<string, mixed>|RequestOptions|null $options
     */
    public function retrieve(string $id, array|RequestOptions|null $options = null): Document
    {
        return $this->requestObject(Document::class, 'GET', self::path('/documents/%s', $id), [], $options);
    }
}
