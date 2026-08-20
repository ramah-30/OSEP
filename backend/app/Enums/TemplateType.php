<?php

namespace App\Enums;

/**
 * The style family an invitation template belongs to.
 */
enum TemplateType: string
{
    case Wedding = 'wedding';
    case Birthday = 'birthday';
    case Conference = 'conference';
    case Corporate = 'corporate';
    case Graduation = 'graduation';
    case Custom = 'custom';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
