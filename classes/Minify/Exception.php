<?php

/**
 * Basic exception.
 *
 * Please report bugs on https://github.com/matthiasmullie/minify/issues
 *
 * @author Matthias Mullie <minify@mullie.eu>
 * @copyright Copyright (c) 2012, Matthias Mullie. All rights reserved
 * @license MIT License
 */

namespace MatthiasMullie\Minify;
/**
 * Basic Exception Class.
 *
 * @author Matthias Mullie <minify@mullie.eu>
 */
abstract class BasicException extends \Exception {}

/**
 * IO Exception Class.
 *
 * @author Matthias Mullie <minify@mullie.eu>
 */
class IOException extends BasicException {}

/**
 * File Import Exception Class.
 *
 * @author Matthias Mullie <minify@mullie.eu>
 */
class FileImportException extends BasicException {}