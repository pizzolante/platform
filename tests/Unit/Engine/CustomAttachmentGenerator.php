<?php

declare(strict_types=1);

namespace Orchid\Tests\Unit\Engine;

use Orchid\Attachment\Engines\Generator;

class CustomAttachmentGenerator extends Generator
{
    public function path(): string
    {
        return 'custom';
    }
}
