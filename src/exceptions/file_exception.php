<?php
/**
 * File containing the ezcArchiveFileException class
 *
 * @package Archive
 * @version //autogen//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license BSD {@link http://ez.no/licenses/bsd}
 */
/**
 * This class provides an exception for errors occuring while accessing file
 * based archives.
 *
 * Create the exception and pass the error code in the constructor, the error
 * message will be automatically created.
 * <code>
 * throw new ezcArchiveFileException( ezcArchiveFileException::
 * FILE_NOT_READABLE, $filename );
 * </code>
 *
 * @package Archive
 * @version //autogen//
 */
class ezcArchiveFileException extends Exception
{
    /**
     * The  file could not be found on the filesystem.
     */
    const FILE_NOT_FOUND = 1;

    /**
     * The file could not be read from the filesystem.
     */
    const FILE_NOT_READABLE = 2;

    /**
     * The file could not be written to the filesystem.
     */
    const FILE_NOT_WRITABLE = 3;

    /**
     * The file not suitable.
     * Notice that the file may be available on the filesystem.
     */
    const FILE_NOT_SUITABLE = 4;



    /**
     * Constructs a file exception
     *
     * Creates the exceptions with one of the class constants as error code.
     * The error message will be generated automatically from the code.
     *
     * @param int $code The error code which is taken from one of the class
     *            constants.
     */
    public function __construct( $message, $code )
    {
        parent::__construct( $message, $code );
    }
}
?>