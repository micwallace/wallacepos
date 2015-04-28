<?php
/**
 * AutoLoader is part of Wallace Point of Sale system (WPOS) API
 *
 * AutoLoader, that is all.
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)

 * @link       https://wallacepos.com
 * @author     Michael B Wallace <micwallace@gmx.com>, Adam Jacquier-Parr <aljparr0@gmail.com>
 * @since      File available since 11/24/13 12:17 PM
 */
namespace wpos; //I am using a namespace here because the function name "modelLoader" could be used again and that would break the script and be hard to debug, better safe than sorry

/*** nullify any existing autoloads ***/
spl_autoload_register(null, false);

/*** specify extensions that may be loaded ***/
spl_autoload_extensions('.php, .class.php');

/**
 * Scan the specified file for the requested PHP class
 * @param $path
 * @param $class
 * @return bool
 */
function autoLoadDirectory($path, $class)
{
    if (is_dir($path)) {
        if (file_exists($path . DIRECTORY_SEPARATOR . $class . '.php')) {
            include $path . DIRECTORY_SEPARATOR . $class . '.php';

            return true;
        }
        $dirList = scandir($path);
        foreach ($dirList as $dirListing) {
            if ($dirListing !== '.' && $dirListing !== '..' && is_dir($path . DIRECTORY_SEPARATOR . $dirListing)) {
                if (autoLoadDirectory($path . DIRECTORY_SEPARATOR . $dirListing, $class)===true){
                    return true;
                }
            }
        }
    }
    return false;
}

/*** class Loader ***/
function autoLoader($class)
{
    $wposPath = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['APP_ROOT'] . 'library' . DIRECTORY_SEPARATOR . 'wpos';
    return autoLoadDirectory($wposPath, $class);
}

/*** register the loader functions ***/
spl_autoload_register('\wpos\autoLoader');