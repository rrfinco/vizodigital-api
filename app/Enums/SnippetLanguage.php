<?php

namespace App\Enums;

enum SnippetLanguage: string
{
    case Curl = 'curl';
    case Php = 'php';
    case Laravel = 'laravel';
    case Nodejs = 'nodejs';
    case Python = 'python';
    case Java = 'java';
    case Go = 'go';
    case Csharp = 'csharp';
    case Javascript = 'javascript';

    public function label(): string
    {
        return match ($this) {
            self::Curl => 'cURL',
            self::Php => 'PHP',
            self::Laravel => 'Laravel',
            self::Nodejs => 'Node.js',
            self::Python => 'Python',
            self::Java => 'Java',
            self::Go => 'Go',
            self::Csharp => 'C#',
            self::Javascript => 'JavaScript',
        };
    }
}
