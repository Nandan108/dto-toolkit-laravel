<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Laravel;

use Illuminate\Validation\ValidationException;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;

final class DtoValidationBridge
{
    /**
     * @return list<ProcessingException>
     */
    private static function normalizeErrorList(ProcessingErrorList | ProcessingException | array $errors): array
    {
        if (is_array($errors)) {
            foreach ($errors as $error) {
                if (!$error instanceof ProcessingException) {
                    throw new \InvalidArgumentException('Invalid error type provided to DtoValidationBridge::normalizeErrorList: array must contain only ProcessingException instances');
                }
            }
            /** @var list<ProcessingException> $normalized */
            $normalized = array_values($errors);

            return $normalized;
        }
        if ($errors instanceof ProcessingErrorList) {
            return $errors->all();
        }

        return [$errors];

    }

    /**
     * Convert DTOT processing errors to a Laravel ValidationException errors array.
     *
     * @return array<string, list<string>>
     */
    public static function toValidationMessages(ProcessingErrorList | ProcessingException | array $errors): array
    {
        $normalizedErrors = self::normalizeErrorList($errors);
        $messages = [];

        foreach ($normalizedErrors as $error) {
            $attribute = self::normalizePropertyPath($error->getPropertyPath());
            $messages[$attribute][] = $error->getMessage();
        }

        return $messages;
    }

    public static function throwLaravelValidationException(ProcessingErrorList | ProcessingException | array $errors): void
    {
        throw ValidationException::withMessages(
            self::toValidationMessages($errors),
        );
    }

    private static function normalizePropertyPath(?string $propertyPath): string
    {
        if (null === $propertyPath || '' === trim($propertyPath)) {
            return 'dto';
        }

        // Strip processing trace markers, e.g. {CastTo\Boolean} or {Mod\PerItem->CastTo\Trimmed}.
        $normalized = (string) preg_replace('/\{[^}]*\}/', '', $propertyPath);
        // Convert bracket notation to Laravel-friendly dotted notation.
        $normalized = (string) preg_replace('/\[([^\]]+)\]/', '.$1', $normalized);
        $normalized = trim($normalized, '.');

        return '' === $normalized ? 'dto' : $normalized;
    }
}
