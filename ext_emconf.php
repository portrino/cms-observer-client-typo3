<?php

// Still needed for v12/v13 classic mode; deprecated in v14, which reads composer.json (keep versions in sync)
$EM_CONF[$_EXTKEY] = [
    'title' => 'CMS Observer Client',
    'description' => 'Reports TYPO3 core, PHP/DB version and installed extensions to a CMS Observer instance.',
    'category' => 'services',
    'author' => 'portrino GmbH',
    'author_email' => 'dev@portrino.de',
    'author_company' => 'portrino',
    'state' => 'stable',
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.5.99',
            'typo3' => '12.4.0-14.3.99',
        ],
    ],
];
