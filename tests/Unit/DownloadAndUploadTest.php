<?php

declare(strict_types=1);

namespace Wsign\Tests\Unit;

use Wsign\Exception\ConflictException;
use Wsign\Exception\UnexpectedResponseException;
use Wsign\HttpClient\HttpResponse;

final class DownloadAndUploadTest extends TestCase
{
    private const PDF = "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<<>>\n%%EOF\n";

    public function testDownloadPdfReturnsVerifiedBytes(): void
    {
        $client = $this->client([new HttpResponse(200, ['Content-Type' => 'application/pdf', 'X-File-Sha256' => hash('sha256', self::PDF)], self::PDF)]);
        self::assertSame(self::PDF, $client->signatureRequests->downloadPdf('sr_1'));
        $request = $this->http->lastRequest();
        self::assertSame('https://wsign.io/api/v1/signature_requests/sr_1/download/pdf', $request->url);
        self::assertSame('application/pdf', $request->getHeader('Accept'));
    }

    public function testDownloadPdfDetectsAFingerprintMismatch(): void
    {
        $client = $this->client([new HttpResponse(200, ['X-File-Sha256' => str_repeat('0', 64)], self::PDF)]);
        $this->expectException(UnexpectedResponseException::class);
        $client->signatureRequests->downloadPdf('sr_1');
    }

    public function testDownloadPdfRejectsNonPdfContent(): void
    {
        $client = $this->client([new HttpResponse(200, [], '{"object":"download"}')]);
        $this->expectException(UnexpectedResponseException::class);
        $client->signatureRequests->downloadPdf('sr_1');
    }

    public function testPdfNotReadyIsAConflict(): void
    {
        $client = $this->client([self::error(409, 'invalid_request_error', 'pdf_not_ready')]);
        try {
            $client->signatureRequests->downloadPdf('sr_1');
            self::fail('Expected ConflictException');
        } catch (ConflictException $e) {
            self::assertSame('pdf_not_ready', $e->getErrorCode());
        }
    }

    public function testDownloadPdfToWritesTheFile(): void
    {
        $client = $this->client([new HttpResponse(200, ['X-File-Sha256' => hash('sha256', self::PDF)], self::PDF)]);
        $path = sys_get_temp_dir() . '/wsign-sdk-' . bin2hex(random_bytes(4)) . '.pdf';
        try {
            self::assertSame(\strlen(self::PDF), $client->signatureRequests->downloadPdfTo('sr_1', $path));
            self::assertSame(self::PDF, file_get_contents($path));
            self::assertSame([], glob($path . '.part-*') ?: []);
        } finally {
            @unlink($path);
        }
    }
}
