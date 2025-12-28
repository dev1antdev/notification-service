<?php

declare(strict_types=1);

namespace App\UI\Http\Exception;

use JsonException;
use Symfony\Component\HttpFoundation\Response;
use function json_encode;

final class BadRequestHttpException extends \RuntimeException
{
    /**
     * @param ValidationError[] $errors
     * @throws JsonException
     */
    public static function fromErrors(array $errors): self
    {
        return new self(
            json_encode($errors, JSON_THROW_ON_ERROR),
            Response::HTTP_BAD_REQUEST,
        );
    }

    /**
     * @throws JsonException
     */
    public static function fromError(ValidationError $error): self
    {
        return self::fromErrors([$error]);
    }
}
