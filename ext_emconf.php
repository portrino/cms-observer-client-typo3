<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'CMS Observer Client',
    'description' => 'Reports TYPO3 core, PHP/DB version and installed extensions to a CMS Observer instance.',
    'category' => 'services',
    'author' => 'CMS Observer',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
        ],
    ],
];
