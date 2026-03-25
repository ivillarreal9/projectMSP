<?php

return [

    'default' => env('AI_PROVIDER', 'anthropic'),
    'default_for_images'       => 'anthropic',
    'default_for_audio'        => 'anthropic',
    'default_for_transcription'=> 'anthropic',
    'default_for_embeddings'   => 'anthropic',
    'default_for_reranking'    => 'anthropic',

    'caching' => [
        'embeddings' => [
            'cache' => false,
            'store' => env('CACHE_STORE', 'database'),
        ],
    ],

    'providers' => [
        'anthropic' => [
            'driver' => 'anthropic',
            'key'    => env('ANTHROPIC_API_KEY'),
        ],

        'openai' => [
            'driver' => 'openai',
            'key'    => env('OPENAI_API_KEY'),
        ],
    ],

];