<?php

namespace App\Services;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policies\Policy;

class CspService extends Policy
{
    public function configure()
    {
        $this
            ->addDirective(Directive::BASE, Keyword::SELF)
            ->addDirective(Directive::CONNECT, Keyword::SELF)
            ->addDirective(Directive::DEFAULT, Keyword::SELF)
            ->addDirective(Directive::FORM_ACTION, Keyword::SELF)
            ->addDirective(Directive::IMG, Keyword::SELF)
            ->addDirective(Directive::MEDIA, Keyword::SELF)
            ->addDirective(Directive::OBJECT, Keyword::NONE)
            ->addDirective(Directive::SCRIPT, [Keyword::SELF, 'unsafe-eval', 'unsafe-inline'])
            ->addDirective(Directive::STYLE, [Keyword::SELF, 'fonts.googleapis.com', 'unsafe-eval', 'unsafe-inline'])
            ->addDirective(Directive::DEFAULT, [Keyword::SELF, 'fonts.gstatic.com']);
    }
}
