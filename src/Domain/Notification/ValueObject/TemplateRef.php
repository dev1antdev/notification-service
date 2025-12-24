<?php

declare(strict_types=1);

namespace App\Domain\Notification\ValueObject;

use App\Domain\Shared\Exception\InvariantViolation;

final readonly class TemplateRef
{
    private function __construct(
        private string $templateId,
        private ?string $version,
        private ?string $locale,
    ) {}

    public static function create(
        string $templateId,
        ?string $version = null,
        ?string $locale = null,
    ): self {
        $templateId = mb_trim($templateId);

        if ($templateId === '' || mb_strlen($templateId) > 128) {
            throw InvariantViolation::because('TemplateId is invalid.');
        }

        if ($version !== null) {
            $version = mb_trim($version);

            if ($version === '' || mb_strlen($version) > 64) {
                throw InvariantViolation::because('Template version is invalid.');
            }
        }

        if ($locale !== null) {
            $locale = mb_trim($locale);

            if ($locale === '' || !preg_match('/^[a-z]{2,3}([-_][A-Z]{2})?$/', $locale)) {
                throw InvariantViolation::because('Template locale is invalid.');
            }
        }

        return new self($templateId, $version, $locale);
    }

    public function templateId(): string
    {
        return $this->templateId;
    }

    public function version(): ?string
    {
        return $this->version;
    }

    public function locale(): ?string
    {
        return $this->locale;
    }
}
