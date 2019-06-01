<?php

include 'topDownAnalyzer.php';

$GRAMMAR = [
    'IDENTIFIER' => [
        ['x'],
        ['y'],
        ['z'],
    ],
    'MULTIPLE' => [
        ['\INTEGER'],
        ['\IDENTIFIER'],
        ['\FLOAT'],
        ['(', '\S', ')'],
    ],
    'P' => [
        ['\MULTIPLE'],
        ['\MULTIPLE', '^', '\INTEGER'],
    ],
    'T' => [
        ['\P'],
        ['\P', '*', '\T'],
    ],
    'S' => [
        ['\T', '+', '\S'],
        ['\T'],
    ],
];

$analyzer = new topDownAnalyzer($GRAMMAR);

$res = $analyzer->parse(explode(' ', '12 * x ^ 24 + 12.34 * x ^ 56 * x ^ 3 + 145.3242434'), 'S');
print_r($res);