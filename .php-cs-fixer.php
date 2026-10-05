<?php

$finder = PhpCsFixer\Finder::create()
                           ->in(__DIR__ . '/src')
                           ->in(__DIR__ . '/tests')
                           ->name('*.php')
                           ->ignoreDotFiles(true)
                           ->ignoreVCS(true);

return ChiefTools\PhpCsFixer\Config::make($finder);
