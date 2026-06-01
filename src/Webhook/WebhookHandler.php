<?php

declare(strict_types=1);

namespace TexHub\MoySklad\Webhook;

use TexHub\MoySklad\Exceptions\MoySkladException;

/**
 * Parses incoming MoySklad webhook callbacks.
 *
 * Payload shape: `{ auditContext: {...}, events: [ { meta, action, accountId,
 * updatedFields } ] }`. MoySklad does not sign webhooks, so for multi-tenant
 * routing use the `accountId` on each event to find the owning tenant.
 */
final class WebhookHandler
{
    /**
     * Parse a webhook body into a list of events.
     *
     * @param string|array<string, mixed> $body
     *
     * @return array<int, WebhookEvent>
     *
     * @throws MoySkladException On invalid JSON.
     */
    public function parse(string|array $body): array
    {
        if (is_string($body)) {
            $decoded = json_decode($body, true);
            if (! is_array($decoded)) {
                throw new MoySkladException('Webhook body is not valid JSON.');
            }
            $body = $decoded;
        }

        $events = [];
        foreach (($body['events'] ?? []) as $event) {
            if (is_array($event)) {
                $events[] = WebhookEvent::fromArray($event);
            }
        }

        return $events;
    }

    /**
     * The account id (tenant) of a payload, read from the first event.
     *
     * @param string|array<string, mixed> $body
     */
    public function accountId(string|array $body): ?string
    {
        $events = $this->parse($body);

        return $events[0]->accountId ?? null;
    }
}
