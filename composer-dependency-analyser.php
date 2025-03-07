<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('dbrekelmans/bdi', [ErrorType::UNUSED_DEPENDENCY]) // Used for downloading web drivers to execute requests that allow javascript
    ->ignoreErrorsOnPackage('league/commonmark', [ErrorType::UNUSED_DEPENDENCY]) // Used for rendering the widget body text
    ->ignoreErrorsOnPackage('setono/consent-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('stof/doctrine-extensions-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('twig/extra-bundle', [ErrorType::UNUSED_DEPENDENCY]) // Used for rendering the widget body text
    ->ignoreErrorsOnPackage('twig/markdown-extra', [ErrorType::UNUSED_DEPENDENCY]) // Used for rendering the widget body text
;
