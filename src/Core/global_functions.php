<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */

if (!function_exists('compressionFromFile')) {
    /**
     * Returns the compression from a file
     * @param string $filename Filename
     * @return string|false
     */
    function compressionFromFile($filename)
    {
        //Gets extension
        $extension = extensionFromFile($filename);

        $compressions = ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false];

        if (!array_key_exists($extension, $compressions)) {
            return null;
        }

        return $compressions[$extension];
    }
}

if (!function_exists('extensionFromCompression')) {
    /**
     * Returns the extension from a compression
     * @param string $compression Compression
     * @return string|bool
     */
    function extensionFromCompression($compression)
    {
        $compressions = ['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false];

        return array_search($compression, $compressions, true);
    }
}

if (!function_exists('extensionFromFile')) {
    /**
     * Returns the extension from a file
     * @param string $filename Filename
     * @return string|null
     */
    function extensionFromFile($filename)
    {
        if (!preg_match('/\.(sql(\.(gz|bz2))?)$/', $filename, $matches) || empty($matches[1])) {
            return null;
        }

        return $matches[1];
    }
}

if (!function_exists('isPositive')) {
    /**
     * Checks if a string is a positive number
     * @param string $string String
     * @return bool
     */
    function isPositive($string)
    {
        return is_numeric($string) && $string > 0 && $string == round($string);
    }
}

if (!function_exists('which')) {
    /**
     * Executes the `which` command and shows the full path of (shell) commands
     * @param string $command Command
     * @return string
     */
    function which($command)
    {
        return exec(sprintf('which %s', $command));
    }
}
