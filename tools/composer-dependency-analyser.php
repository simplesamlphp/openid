<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
  // Ignore suggested optional dependency
  ->ignoreErrorsOnExtension('ext-curl', [ErrorType::SHADOW_DEPENDENCY]);

