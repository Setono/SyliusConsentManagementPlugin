<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->addPathToExclude(__DIR__ . '/tests')
    ->ignoreErrorsOnPackage('dbrekelmans/bdi', [ErrorType::UNUSED_DEPENDENCY]) // Used for downloading web drivers to execute requests that allow javascript
    ->ignoreErrorsOnPackage('setono/consent-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('setono/sylius-static-contexts-bundle', [ErrorType::UNUSED_DEPENDENCY])
    ->ignoreErrorsOnPackage('stof/doctrine-extensions-bundle', [ErrorType::UNUSED_DEPENDENCY])
    // The mb_* functions come from ext-mbstring when it's loaded, and from symfony/polyfill-mbstring otherwise
    ->ignoreErrorsOnExtension('ext-mbstring', [ErrorType::SHADOW_DEPENDENCY])
    ->ignoreErrorsOnPackage('symfony/polyfill-mbstring', [ErrorType::UNUSED_DEPENDENCY])
;
