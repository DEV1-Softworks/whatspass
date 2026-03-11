<?php

declare(strict_types=1);

namespace Dev1\Whatspass;

enum MessageType: string
{
    case Template = 'template';
    case Text = 'text';
}
