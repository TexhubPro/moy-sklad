<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Resources;

use TexHub\MoySklad\Config;
use TexHub\MoySklad\Http\HttpClient;
use TexHub\MoySklad\Responses\ListResponse;
use TexHub\MoySklad\Responses\Response;

/**
 * Products (товары) — full CRUD plus image management.
 *
 * Inherits list/get/create/update/delete/metadata from {@see EntityClient}.
 * Product fields include name, code, article, description, salePrices,
 * buyPrice, attributes, productFolder, images, etc.
 *
 * @see https://dev.moysklad.ru/doc/api/remap/1.2/dictionaries/#suschnosti-towar
 */
final class ProductsClient extends EntityClient
{
    public function __construct(HttpClient $http, Config $config)
    {
        parent::__construct($http, $config, 'product');
    }

    /**
     * List a product's images.
     */
    public function images(string $productId): ListResponse
    {
        return ListResponse::from($this->http->get($this->path($productId) . '/images'));
    }

    /**
     * Attach an image from raw binary contents (base64-encoded internally).
     */
    public function addImage(string $productId, string $filename, string $contents): ListResponse
    {
        return ListResponse::from(['rows' => $this->http->post($this->path($productId) . '/images', [[
            'filename' => $filename,
            'content' => base64_encode($contents),
        ]])]);
    }

    /**
     * Attach an image from a local file path.
     */
    public function addImageFromFile(string $productId, string $path, ?string $filename = null): ListResponse
    {
        return $this->addImage($productId, $filename ?? basename($path), (string) file_get_contents($path));
    }

    /**
     * Delete a product image.
     */
    public function deleteImage(string $productId, string $imageId): void
    {
        $this->http->delete($this->path($productId) . '/images/' . rawurlencode($imageId));
    }

    /**
     * Download the raw bytes of an image by its download href
     * (from an image row's `meta.downloadHref`, `miniature.href` or `tiny.href`).
     */
    public function downloadImage(string $downloadHref): string
    {
        return $this->http->getRaw($downloadHref)->body;
    }

    /**
     * Convenience: download the first image of a product as raw bytes (or null).
     */
    public function firstImageContents(string $productId): ?string
    {
        $rows = $this->images($productId)->rows();
        $href = $rows[0]['meta']['downloadHref'] ?? null;

        return $href === null ? null : $this->downloadImage((string) $href);
    }

    /**
     * Get a product's sale prices (convenience).
     *
     * @return array<int, array<string, mixed>>
     */
    public function salePrices(string $productId): array
    {
        $prices = $this->get($productId)->get('salePrices', []);

        return is_array($prices) ? $prices : [];
    }
}
