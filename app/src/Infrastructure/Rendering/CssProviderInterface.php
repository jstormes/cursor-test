<?php

declare(strict_types=1);

namespace App\Infrastructure\Rendering;

interface CssProviderInterface
{
    public function getMainCSS(): string;

    public function getSimplePageCSS(): string;

    public function getErrorPageCSS(): string;

    public function getSuccessPageCSS(): string;
}
