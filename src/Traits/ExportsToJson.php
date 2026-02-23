<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel\Traits;

use Illuminate\Http\JsonResponse;
use Nandan108\DtoToolkit\Core\BaseDto;

/**
 * @psalm-require-extends BaseDto
 *
 * @api
 */
trait ExportsToJson
{
    /**
     * Export the DTO to a JSON string.
     *
     * @param ?array<string> $groups
     *
     * @throws \InvalidArgumentException
     */
    public function exportToJson(
        int $options = 0,
        ?string $wrapKey = null,
        ?array $groups = null,
    ): string {
        $payload = $this->buildOutboundPayload($wrapKey, $groups);

        $json = json_encode($payload, $options);
        if (!is_string($json)) {
            throw new \InvalidArgumentException('Failed to export DTO as JSON: '.json_last_error_msg());
        }

        return $json;
    }

    /**
     * Export the DTO to a JSON response.
     *
     * @param ?array<string> $groups
     */
    public function exportToJsonResponse(
        int $status = 200,
        array $headers = [],
        int $options = 0,
        ?string $wrapKey = null,
        ?array $groups = null,
    ): JsonResponse {
        /** @psalm-suppress UndefinedMagicMethod */
        return new JsonResponse(
            data: $this->buildOutboundPayload($wrapKey, $groups),
            status: $status,
            headers: $headers,
            options: $options,
        );
    }

    private function buildOutboundPayload(?string $wrapKey, ?array $groups): array
    {
        $source = $this;
        if (null !== $groups && method_exists($this, 'withGroups')) {
            $source = clone $this;
            $source->withGroups(outbound: $groups, outboundCast: $groups);
        }

        $payload = $source->toOutboundArray();

        if (null !== $wrapKey) {
            return [$wrapKey => $payload];
        }

        return $payload;
    }
}
