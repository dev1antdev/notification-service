<?php

declare(strict_types=1);

namespace App\Infrastructure\Template;

use App\Application\Ports\Template\RenderedTemplate;
use App\Application\Ports\Template\TemplateRenderer;
use App\Domain\Notification\ValueObject\TemplateRef;
use App\Domain\Notification\ValueObject\Variables;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

final readonly class TwigTemplateRenderer implements TemplateRenderer
{
    public function __construct(
        private TemplateSource $source,
        private Environment $twig,
    ) {}

    /**
     * @throws LoaderError
     * @throws SyntaxError
     */
    public function render(TemplateRef $ref, Variables $variables): RenderedTemplate
    {
        $material = $this->source->get($ref);
        $vars = $variables->toArray();

        return new RenderedTemplate(
            subject: $this->renderString($material->subject, $vars),
            text: $this->renderString($material->text, $vars),
            html: $this->renderString($material->html, $vars),
            pushTitle: $this->renderString($material->pushTitle, $vars),
            pushBody: $this->renderString($material->pushBody, $vars),
            pushData: $this->renderPushData($material->pushData, $vars),
        );
    }

    /**
     * @throws SyntaxError
     * @throws LoaderError
     */
    private function renderString(?string $template, array $vars): ?string
    {
        if ($template === null || $template === '') {
            return $template;
        }

        return $this->twig->createTemplate($template)->render($vars);
    }

    private function renderPushData(array $data, array $vars): array
    {
        /**
         * @throws SyntaxError
         * @throws LoaderError
         */
        $walk = function ($value) use (&$walk, $vars) {
            if (is_string($value)) {
                return $this->renderString($value, $vars);
            }

            if (is_array($value)) {

                return array_map(static fn($v) => $walk($v), $value);
            }

            return $value;
        };

        return $walk($data);
    }
}
