<?php
/**
 * File containing the abstract ezcArchive class.
 *
 * @package Archive
 * @version //autogentag//
 * @copyright Copyright (C) 2005, 2006 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/** 
 * The ezcArchive class provides the common interface for reading and writing
 * the archive formats Tar and Zip.
 *
 * ezcArchive provides the main API for reading and writing to an archive. The
 * archive itself can be compressed with GZip or BZip2 and will be handled
 * transparently. 
 * 
 * The {@link open()} method creates a new archive instance. For
 * existing archives, ezcArchive determines the correct archive format by the
 * mime-type and returns an instance of a subclass handling this format. 
 * New archives should force a format type via a parameter in the open()
 * method.
 *
 * The instance of an ezcArchive class is also an iterator, which
 * points to the first file in the archive by default. Moving this pointer can
 * be done via the iterator methods: {@link rewind()}, {@link next()}, 
 * {@link valid()}, {@link key()}, and {@link current()}. This iterator is
 * defined as an object iterator and allows, for example, the {@link
 * http://www.php.net/foreach foreach} statement to iterate through the files. 
 *
 * Extra methods that operate on the current iterator are: {@link
 * extractCurrent()} and {@link appendToCurrent()}. Which can be used,
 * respectively, to extract the files and to append a new file to the archive. 
 * 
 * The following example will open an existing tar.gz file and will append each
 * file to a zip archive:
 * <code>
 * $tar = ezcArchive::open( "/tmp/archive.tar.gz" );
 * $newZip = ezcArchive::open( "/tmp/new_archive.zip", ezcArchive::ZIP );
 * 
 * foreach ( $tar as $entry )
 * {
 *    // $entry contains the information of the current entry in the archive.
 *    $tar->extractCurrent( "/tmp/" );
 *    $newZip->appendToCurrent( $entry->getPath(), "/tmp/" ); 
 *    $newZip->next();
 * }
 * </code>
 * 
 * In order to extract an entire archive at once, use the {@link extract()}
 * method.
 *
 * @package Archive
 * @version //autogentag//
 */ 
abstract class ezcArchive implements Iterator
{
    /**
     * Normal tar archive.
     */
    const TAR        = 0;

    /**
     * Tar version 7 archive.
     */
    const TAR_V7     = 1;

    /**
     * USTAR tar archive.
     */
    const TAR_USTAR  = 2;

    /**
     * PAX tar archive.
     */
    const TAR_PAX    = 3;

    /**
     * GNU tar archive.
     */
    const TAR_GNU    = 4;

    /**
     * ZIP archive.
     */
    const ZIP        = 10;

    /**
     * Gnu ZIP compression format.
     */
    const GZIP       = 20;

    /**
     * BZIP2 compression format.
     */
    const BZIP2      = 30;


    /**
     * The entry or file number to which the iterator points. 
     *
     * The first $fileNumber starts with 0.
     * 
     * @var int
     */
    protected $fileNumber = 0; 

    /**
     * The number of entries currently read from the archive.
     *
     * @var int
     */
    protected $entriesRead = 0;

    /**
     * Is true when the archive is read until the end, otherwise false.
     *
     * @var bool 
     */
    protected $completed = false;

    /**
     * Stores the entries read from the archive.
     *
     * The array is not complete when the {@link $completed} variable is set to
     * false.  The array may be over-complete, so the {@link $entriesRead}
     * should be checked if the {@link $completed} variable is set to true.
     * 
     * @var array(ezcArchiveEntry) 
     */ 
    protected $entries;

    /**
     * Direct access to the archive file. 
     *
     * @var ezcArchiveFile  
     */
    protected $file = null; 

    /**
     * Use the {@link open()} method to get an instance of this class.
     */
    private function __construct() 
    { 
    }

    /**
     * Returns a new ezcArchive instance. 
     *
     * This method returns a new instance according to the mime-type or
     * the given $forceType.
     *
     * - If $forceType is set to null, this method will try to determine the
     *   archive format via the file data. Therefore the $forceType can only be
     *   null when the archive contains data. 
     * - If $forceType is set, it will use the specified algorithm. Even when
     *   the given archive is from another type than specified.
     *
     * @throws ezcArchiveUnknownTypeException if the type of the archive cannot be determined.
     *
     * @param string  $archiveName  Absolute or relative path to the archive.
     * @param int     $forceType    Open the archive with the $forceType 
     *        algorithm. Possible values are: {@link ezcArchive::ZIP}, 
     *        {@link ezcArchive::TAR}, {@link ezcArchive::TAR_V7}, {@link ezcArchive::TAR_USTAR}, 
     *        {@link ezcArchive::TAR_PAX}, {@link ezcArchive::TAR_GNU}. 
     *        TAR will use the TAR_USTAR algorithm by default.
     * 
     * @return ezcArchive
     */
    public static function open( $archiveName, $forceType = null)
    {
        if ( !ezcArchiveFile::fileExists( $archiveName ) && $forceType === null )
        {
            throw new ezcArchiveUnknownTypeException( $archiveName );
        }

        if ( $forceType !== null )
        {
            return self::createInstance( $archiveName, $forceType );
        }

        $h = ezcArchiveMime::detect( $archiveName );

        while ( $h == ezcArchive::GZIP || $h == ezcArchive::BZIP2 ) 
        {
            if ( $h == ezcArchive::GZIP )
            {
                $archiveName = "compress.zlib://$archiveName";
                $h = ezcArchiveMime::detect( $archiveName );
            }

            if ( $h == ezcArchive::BZIP2 )
            {
                $archiveName = "compress.bzip2://$archiveName";
                $h = ezcArchiveMime::detect( $archiveName );
            }
        }

        return self::createInstance( $archiveName, $h );
    }


    /**
     * Sets the property $name to $value.
     * 
     * Because there are no properties available, this method will always 
     * throw an {@link ezcBasePropertyNotFoundException}.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set( $name, $value )
    {
        throw new ezcBasePropertyNotFoundException( $name );
    }

    /**
     * Returns the property $name.
     *
     * Because there are no properties available, this method will always 
     * throw an {@link ezcBasePropertyNotFoundException}.
     *
     * @throws ezcBasePropertyNotFoundException if the property does not exist.
     * @param string $name
     * @return mixed
     */
    public function __get( $name )
    {
        throw new ezcBasePropertyNotFoundException( $name );
    }


    /**
     * Returns the algorithm that is used currently. 
     *
     * @return int   Possible values are: {@link ezcArchive::ZIP}, {@link ezcArchive::TAR}, {@link ezcArchive::TAR_V7}, 
     *               {@link ezcArchive::TAR_USTAR}, {@link ezcArchive::TAR_PAX}, or {@link ezcArchive::TAR_GNU}. 
     */
    public abstract function getAlgorithm();

    /**
     * Returns true if writing to the archive is implemented, otherwise false.
     *
     * @see isWritable()
     *
     * @return bool
     */
    public abstract function algorithmCanWrite();

    /**
     * Returns true if it is possible to write to the archive, otherwise false.
     *
     * This method returns false if the archive is read-only, the algorithm
     * didn't implement any write methods, or both.
     *
     * @see algorithmCanWrite()
     *
     * @return bool
     */
    public function isWritable()
    {
        return ( !$this->file->isReadOnly() && $this->algorithmCanWrite() );
    }


    /**
     * Returns an instance of the archive with the given type.
     *
     * Similar to {@link open()}, but the type is required.
     *
     * @param string $archiveName  The path of the archive. 
     * @param int    $type        Open the archive with the $forceType 
     *        algorithm. Possible values are: {@link ezcArchive::ZIP}, 
     *        {@link ezcArchive::TAR}, {@link ezcArchive::TAR_V7}, {@link ezcArchive::TAR_USTAR}, 
     *        {@link ezcArchive::TAR_PAX}, {@link ezcArchive::TAR_GNU}. 
     *        TAR will use the TAR_USTAR algorithm by default.
     *
     * @return ezcArchive  Subclass of ezcArchive: {@link ezcArchiveZip}, 
     *                     {@link ezcArchiveV7Tar}, {@link ezcArchivePax},
     *                     {@link ezcArchiveGnuTar}, or {@link ezcArchiveUstar}.
     */
    protected static function createInstance( $archiveName, $type )
    {
        if ( $type == self::ZIP )
        {
            $af = new ezcArchiveCharacterFile( $archiveName, true ); 
            return self::getZipInstance( $af, $type ); 
        }

        $af = new ezcArchiveBlockFile( $archiveName, true ); 
        return self::getTarInstance( $af, $type ); 
    }
 
    /**
     * Open a tar instance. 
     *
     * This method is made public for testing purposes, and should not be used.
     *
     * @param ezcArchiveBlockFile $blockFile 
     * @param int    $type         
     *        algorithm. Possible values are: 
     *        {@link ezcArchive::TAR}, {@link ezcArchive::TAR_V7}, {@link ezcArchive::TAR_USTAR}, 
     *        {@link ezcArchive::TAR_PAX}, {@link ezcArchive::TAR_GNU}. 
     *        TAR will use the TAR_USTAR algorithm by default.
     *
     * @return ezcArchive  Subclass of ezcArchive: 
     *                     {@link ezcArchiveV7Tar}, {@link ezcArchivePax},
     *                     {@link ezcArchiveGnuTar}, or {@link ezcArchiveUstar}.
     * @access private
     */
    public static function getTarInstance( ezcArchiveBlockFile $blockFile, $type) 
    {
        switch ( $type )
        {
            case self::TAR_V7:     return new ezcArchiveV7Tar( $blockFile );
            case self::TAR_USTAR:  return new ezcArchiveUstarTar( $blockFile );
            case self::TAR_PAX:    return new ezcArchivePaxTar( $blockFile );
            case self::TAR_GNU:    return new ezcArchiveGnuTar( $blockFile );
            case self::TAR:        return new ezcArchiveUstarTar( $blockFile ); // Default type.
        }

        return null;
    }

     /**
     * Open a zip instance. This method is made public for testing purposes, and
     * should not be used.
     *
     * @param ezcArchiveCharacterFile $charFile  The character file which
     *                                           contains the archive.
     * @return ezcArchive  Subclass of ezcArchive: {@link ezcArchiveZip}. 
     * @access private
     */
    public static function getZipInstance( ezcArchiveCharacterFile $charFile ) 
    {
        return new ezcArchiveZip( $charFile );
    }
     
    /**
     * Returns true if the iterator points to a valid entry, otherwise false.
     *
     * @return bool 
     */
    public function valid()
    {
        return ( $this->fileNumber >= 0 && $this->fileNumber < $this->entriesRead );
    }
    

    /**
     * Rewinds the iterator to the first entry. 
     *
     * @return void
     */
    public function rewind()
    {
        $this->fileNumber = 0;
    }

    /**
     * Returns the current ezcArchiveEntry if it is valid, otherwise false is returned.
     * 
     * @return ezcArchiveEntry
     */ 
    public function current()
    {
        return ( $this->valid() ? $this->entries[ $this->fileNumber ] : false );
    }

    /**
     * Returns the current key, entry number, if it is valid, otherwise false is returned. 
     *
     * @return int
     */
    public function key()
    {
        return ( $this->valid() ? $this->fileNumber : false );
    }


    /**
     * Forwards the iterator to the next entry. 
     *
     * If there is no next entry all iterator methods except for {@link
     * rewind()} will return false.
     *
     * @see rewind()
     *
     * @return ezcArchiveEntry  The next entry if it exists, otherwise false.
     */
    public function next()
    {
        if ( $this->valid() )
        {
            $this->fileNumber++;
            if ( $this->valid() ) return $this->current();

            if ( !$this->completed )
            {
                if ( $this->readCurrentFromArchive() )
                {
                    return $this->current();
                }
            }
        }

        return false;
    }

    /**
     * Extract the current entry to which the iterator points. 
     *
     * Extract the current entry to which the iterator points, and return true if the current entry is extracted. 
     * If the iterator doesn't point to a valid entry, this method returns false.
     *
     * True if the file is extracted correctly, otherwise false.
     *
     * @param string $target  
     *        The full path to which the target should be extracted.
     * @param bool $keepExisting  
     *        True if the file shouldn't be overwritten if they already exist.
     *        For the opposite behaviour, false should be given.
     *
     * @throws ezcArchiveValueException     if the archive contains invalid values.
     * @throws ezcBaseFileNotFoundException if the link cannot be found.
     *
     * @return bool  
     */
    public function extractCurrent( $target, $keepExisting = false )
    {
        if ( !$this->valid() ) return false;

        $isWindows = ( substr( php_uname('s'), 0, 7 ) == 'Windows' )?true:false;
        $entry = $this->current();
        $type = $entry->getType();
        $fileName = $target ."/". $entry->getPath(); 
        
        if ( $type == ezcArchiveEntry::IS_LINK )
        {
            $linkName = $target ."/".$entry->getLink();
            if ( !file_exists( $linkName ) )
            {
                throw new ezcBaseFileNotFoundException( $linkName, "link", "Hard link could not be created." );
            }
        }

        $this->createDefaultDirectory( $fileName );

        if ( !$keepExisting || ( !is_link( $fileName ) && !file_exists( $fileName ) ) )
        {
            if ( ( file_exists( $fileName ) || is_link( $fileName ) ) && !is_dir( $fileName ) )
            {
                unlink ( $fileName );
            }
                
            if ( !file_exists( $fileName ) ) // For example, directories are not removed.
            {
                switch ( $type )
                {
                    case ezcArchiveEntry::IS_CHARACTER_DEVICE:  
                        if ( function_exists('posix_mknod')) 
                        {
                            posix_mknod( $fileName, POSIX_S_IFCHR, $entry->getMajor(), $entry->getMinor() );
                        }
                        else
                        {
                            throw new ezcArchiveValueException( $type );
                        }
                        break;
                    case ezcArchiveEntry::IS_BLOCK_DEVICE:
                        if ( function_exists('posix_mknod') )
                        {
                            posix_mknod( $fileName, POSIX_S_IFBLK, $entry->getMajor(), $entry->getMinor() ); 
                        }
                        else
                        {
                            throw new ezcArchiveValueException( $type );
                        }
                        break;
                    case ezcArchiveEntry::IS_FIFO:              
                        if ( function_exists('posix_mknod') ) 
                        {
                            posix_mknod( $fileName, POSIX_S_IFIFO );
                        }
                        else
                        {
                            throw new ezcArchiveValueException( $type );
                        }
                        break;
                    case ezcArchiveEntry::IS_SYMBOLIC_LINK:
                        if ( $isWindows )
                        {
                            $pathParts = pathinfo($fileName);
                            $linkDir = $pathParts['dirname'];
                            copy( $linkDir.'/'.$entry->getLink(), $fileName );
                        }
                        else
                        {
                            symlink( $entry->getLink(), $fileName );
                        }
                        break;
                    case ezcArchiveEntry::IS_LINK:  
                        if ( $isWindows )
                        {
                            copy( $target ."/".$entry->getLink(), $fileName );
                        }
                        else
                        {
                            link( $target ."/". $entry->getLink(), $fileName );
                        }
                        break;
                    case ezcArchiveEntry::IS_DIRECTORY:         mkdir( $fileName, octdec( $entry->getPermissions() ), true );                    break;
                    case ezcArchiveEntry::IS_FILE:              $this->writeCurrentDataToFile( $fileName );                                      break;
                    default: throw new ezcArchiveValueException( $type );
                }

                // Change the file, iff the filename exists and iff the intension is to keep it as a file.
                // The Zip archive stores the symlinks in a file; thus don't change these.
                if ( file_exists( $fileName ) && $type == ezcArchiveEntry::IS_FILE )
                {
                    @chgrp( $fileName, $entry->getGroupId() );
                    @chown( $fileName, $entry->getUserId() );

                    if ( $entry->getPermissions() !== false ) chmod( $fileName, octdec( $entry->getPermissions() ) );

                    touch( $fileName, $entry->getModificationTime() );
                }
            }

            return true; 
        }

        return false;
    }

    /**
     * Search for the entry number. 
     * 
     * The two parameters here are the same as the PHP {@link http://www.php.net/fseek fseek()} method.
     * The internal iterator position will be set by $offset added to $whence iterations forward. 
     * Where $whence is:
     * - SEEK_SET, Set the position equal to $offset. 
     * - SEEK_CUR, Set the current position plus $offset. 
     * - SEEK_END, Set the last file in archive position plus $offset. 
     *
     * This method returns true if the new position is valid, otherwise false.
     *
     * @param int    $offset    
     * @param int    $whence    

     * @return bool 
     */
    public function seek( $offset, $whence = SEEK_SET )
    {
        // Cannot trust the current position if the current position is invalid.
        if ( $whence == SEEK_CUR && $this->valid() == false ) 
            return false;
         
        if ( $whence == SEEK_END && !$this->completed)
        {
            // read the entire archive.
             $this->fileNumber = $this->entriesRead;
             while ( $this->readCurrentFromArchive() ) $this->fileNumber++;
        }
 
        switch ( $whence )
        {
            case SEEK_SET: $requestedFileNumber = $offset; break;
            case SEEK_CUR: $requestedFileNumber = $offset + $this->fileNumber; break;
            case SEEK_END: $requestedFileNumber = $offset + $this->entriesRead - 1; break;
            default: return false; // Invalid whence.
        }

         $this->fileNumber = $requestedFileNumber;
         if ( $this->valid() ) return true;

         if ( !$this->completed )
         {
             $this->fileNumber = $this->entriesRead - 1;

             while ( $this->fileNumber != $requestedFileNumber )
             {
                $this->fileNumber++;
                if ( !$this->readCurrentFromArchive() ) break;
             } 

             return $this->valid();
         }

         return false;
    }
    
    /**
     * Creates all the directories needed to create the file $file.
     * 
     * @param string $file  Path to a file, where all the base directory names will be created.
     */
    protected function createDefaultDirectory( $file )
    {
        // Does the directory exist?
        $dirName = dirname( $file );
        
        if ( !file_exists( $dirName ) )
        {
            // Try to create the directory.
            if (substr( php_uname('s'), 0, 7 ) == 'Windows')  
            {
                $dirName = str_replace( '/', '\\', $dirName ); //make all slashes to be '/'
            }
            mkdir( $dirName, 0777, true );
        }
    }

    /**
     * Appends a file to the archive after the current entry. 
     *
     * One or multiple files can be added directly after the current file.
     * The remaining entries after the current are removed from the archive!
     *
     * The $files can either be a string or an array of strings. Which, respectively, represents a
     * single file or multiple files.
     *
     * $prefix specifies the begin part of the $files path that should not be included in the archive.
     * The files in the archive are always stored relatively.
     *
     * Example:
     * <code>
     * $tar = ezcArchive( "/tmp/my_archive.tar", ezcArchive::TAR );
     *
     * // Append two files to the end of the archive.
     * $tar->seek( 0, SEEK_END );
     * $tar->appendToCurrent( array( "/home/rb/file1.txt", "/home/rb/file2.txt" ), "/home/rb/" );
     * </code>
     *
     * When multiple files are added to the archive at the same time, thus using an array, does not 
     * necessarily produce the same archive as repeatively adding one file to the archive. 
     * For example, the Tar archive format, can detect that files hardlink to each other and will store
     * it in a more efficient way.
     * 
     * @throws ezcArchiveWriteException  if one of the files cannot be written to the archive.
     * @throws ezcFileReadException      if one of the files cannot be read from the local filesystem.
     *
     * @param string|array(string) $files  Array or a single path to a file. 
     * @param string $prefix               First part of the path used in $files. 
     *
     * @return void 
     */
    public abstract function appendToCurrent( $files, $prefix );

    /**
     * Truncate the archive to $fileNumber of files.
     *
     * The $fileNumber parameter specifies the amount of files that should remain.
     * If the default value, zero, is used then the entire archive file is cleared.
     *
     * @param int $fileNumber
     *
     * @return void
     */
    public abstract function truncate( $fileNumber = 0 );

    /**
     * Writes the file data from the current entry to the given file.
     *
     * @param string $targetPath  The absolute or relative path of the target file.
     * @return void
     */
    protected abstract function writeCurrentDataToFile( $targetPath );

	/**
	 * Returns an array that lists the content of the archive.
     * 
     * Use the getArchiveEntry method to get more information about an entry.
     *
     * @see __toString()
     * 
     * @return array(string)
	 */
    public function getListing()
    {
        $result = array();
        $this->rewind();

        do
        {
            $entry = $this->current();
            $result[] = rtrim( $entry->__toString(), "\n" ); // remove newline.
        }
        while ( $this->next() );

        return $result; 
    } 

    /**
     * Returns a string which represents all the entries from the archive.
     *
     * @return string
     */
    public function __toString()
    {
        $result = "";
        $this->rewind();

        while ( $this->valid() )
        {
            $result .= $this->current()->__toString()."\n";
            $this->next();
        }

        return $result; 
    }

	/**
	 * Extract entries from the archive to the target directory.
     *
     * All entries from the archive are extracted to the target directory.
     * By default the files in the target directory are overwritten.
     * If the $keepExisting is set to true, the files from the archive will not overwrite existing files.
     *
     * @see extractCurrent()
     *
     * @throws ezcArchiveReadException  if an entry cannot be extracted from the archive.
     **
     * @param string $target     Absolute or relative path of the directory.
     * @param bool $keepExisting If set to true then the file will be overwritten, otherwise not.
     * 
     * @return void
     */
    public function extract( $target, $keepExisting = false )
    {
        $this->rewind();
        if ( !$this->valid() ) 
        {
            throw new ezcArchiveEmptyException( ); 
        }

        while ( $this->valid() )
        {
            $this->extractCurrent( $target, $keepExisting );
            $this->next();
        }
    }

    /** 
     * Append a file or directory to the end of the archive. Multiple files or directory can 
     * be added to the archive when an array is used as input parameter.
     *
     * @see appendToCurrent()
     *
     * @throws ezcArchiveWriteException  if one of the files cannot be written to the archive.
     * @throws ezcFileReadException      if one of the files cannot be read from the local filesystem.
     *
     * @param string|array(string) $entries  Add the files and or directories to the archive.
     *
     * @return void
     */
    public function append( $files, $prefix )
    {
        if ( !$this->isWritable() )
        {
            throw new ezcArchiveException( "Archive is read-only", ezcArchiveException::ARCHIVE_NOT_WRITABLE );
        }

        $this->seek( 0, SEEK_END );
        $this->appendToCurrent( $files, $prefix ); 
     }

    /**
     * Returns true if the current archive is empty, otherwise false. 
     *
     * @return bool
     */
    public function isEmpty()
    {
        if ( !$this->completed && $this->entriesRead == 0 ) 
            $this->readCurrentFromArchive();

        return ( $this->entriesRead == 0 );
    }
}

?>