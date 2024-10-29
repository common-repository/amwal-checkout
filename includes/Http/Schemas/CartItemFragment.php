<?php

namespace Amwal\Http\Schemas;

final class CartItemFragment
{
    const SCHEMA = [
        'type'                 => 'object',
        'required'             => true,
        'additionalProperties' => true,
        'properties'           => [
            'product_id' => [
                'type'     => 'number',
                'required' => true
            ],
            'quantity'   => [
                'type'     => 'number',
                'required' => true,
                'min'      => 1
            ],
            'attributes' => [
                'type' => 'array',
                'required' => false,
                'items' => [
                    'type' => 'object',
                    'required' => true,
                    'additionalProperties' => true,
                    'properties' => [
                        'key' => [
                            'type' => 'string',
                            'required' => true
                        ],
                        'value' => [
                            'type' => 'string',
                            'required' => true
                        ]
                    ]
                ]
            ]
        ]
    ];
}
