<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Webhook;

/**
 * A single MoySklad webhook event.
 */
final class WebhookEvent
{
    /**
     * @param string               $action        CREATE | UPDATE | DELETE.
     * @param string|null          $accountId     The account that produced the event — the tenant key.
     * @param string|null          $entityType    e.g. "product", "customerorder".
     * @param string|null          $entityHref    Full API href of the changed entity.
     * @param array<int, string>   $updatedFields Names of changed fields (UPDATE only).
     * @param array<string, mixed> $raw           The raw event item.
     */
    public function __construct(
        public readonly string $action,
        public readonly ?string $accountId,
        public readonly ?string $entityType,
        public readonly ?string $entityHref,
        public readonly array $updatedFields,
        public readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $event
     */
    public static function fromArray(array $event): self
    {
        $meta = is_array($event['meta'] ?? null) ? $event['meta'] : [];

        return new self(
            action: (string) ($event['action'] ?? ''),
            accountId: isset($event['accountId']) ? (string) $event['accountId'] : null,
            entityType: isset($meta['type']) ? (string) $meta['type'] : null,
            entityHref: isset($meta['href']) ? (string) $meta['href'] : null,
            updatedFields: is_array($event['updatedFields'] ?? null) ? $event['updatedFields'] : [],
            raw: $event,
        );
    }

    /**
     * The id of the changed entity, parsed from its href.
     */
    public function entityId(): ?string
    {
        if ($this->entityHref === null) {
            return null;
        }

        $segment = strtok((string) substr($this->entityHref, (int) strrpos($this->entityHref, '/') + 1), '?');

        return $segment === false ? null : $segment;
    }

    public function isCreate(): bool
    {
        return $this->action === 'CREATE';
    }

    public function isUpdate(): bool
    {
        return $this->action === 'UPDATE';
    }

    public function isDelete(): bool
    {
        return $this->action === 'DELETE';
    }
}
