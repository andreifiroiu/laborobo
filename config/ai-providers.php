<?php

return [
    'providers' => [
        'anthropic' => [
            'name' => 'Anthropic',
            'description' => 'Claude models (Opus, Sonnet, Haiku)',
            'icon' => 'anthropic',
            'config_path' => 'services.anthropic.api_key',
            'docs_url' => 'https://docs.anthropic.com/',
            'models' => [
                'claude-opus-4-6' => 'Claude Opus 4.6',
                'claude-sonnet-4-5-20250929' => 'Claude Sonnet 4.5',
                'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
            ],
        ],
        'openai' => [
            'name' => 'OpenAI',
            'description' => 'GPT-4, GPT-3.5 and other OpenAI models',
            'icon' => 'openai',
            'config_path' => 'services.openai.api_key',
            'docs_url' => 'https://platform.openai.com/docs/',
            'models' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'o3-mini' => 'o3-mini',
            ],
        ],
        'google' => [
            'name' => 'Google (Gemini)',
            'description' => 'Gemini Pro, Ultra and other Google AI models',
            'icon' => 'google',
            'config_path' => 'services.google.api_key',
            'docs_url' => 'https://ai.google.dev/docs/',
            'models' => [
                'gemini-2.0-flash' => 'Gemini 2.0 Flash',
                'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
            ],
        ],
    ],
];
