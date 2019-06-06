<?php

include 'AssemblerTranslator.php';

function print_texts($programs)
{
    foreach ($programs as $program) {
        $text = implode("\n", $program);
        echo "\n$text\n";
    }
}

$translator = new AssemblerTranslator();
$programs = $translator->translate('a * ( -0.3234 + x * -123.4 )');
print_texts($programs);