<?php

namespace Mindbox\Loyalty\Support;

use Mindbox\Loyalty\Support\SettingsFactory;

class LogsRotation
{
    protected static string $logFileName = 'mindbox.log';

    protected static string $pathSaveArchive = 'archive';

    protected const LOG_DIRECTORY = 'mindbox';

    protected static function getMindboxLogPath(string $pathLog): string
    {
        return self::replaceSlashes($pathLog . DIRECTORY_SEPARATOR . self::LOG_DIRECTORY);
    }

    protected static function replaceSlashes(string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $path);
    }

    protected static function getArchivePath(string $pathLog): string
    {
        $pathArchive = self::replaceSlashes($pathLog . DIRECTORY_SEPARATOR . self::LOG_DIRECTORY . DIRECTORY_SEPARATOR . self::$pathSaveArchive);

        if (!is_dir($pathArchive)) {
            mkdir($pathArchive, defined('BX_DIR_PERMISSIONS') ? BX_DIR_PERMISSIONS : 0755);
        }

        return $pathArchive;
    }

    public static function agentRotationLogs(): string
    {
        self::processRotation();
        return '\\' . __METHOD__ . '();';
    }

    public static function processRotation(): void
    {
        $settings = SettingsFactory::create();

        $optionLifeDay = $settings->getLogLifeTime();

        if ($optionLifeDay <=  0) {
            return;
        }

        if (!$settings->getLogPath()) {
            return;
        }

        $optionPathLogs = self::getMindboxLogPath($settings->getLogPath());
        $optionPathArchive = self::getArchivePath($settings->getLogPath());

        if (!is_dir($optionPathLogs) || !is_dir($optionPathArchive)) {
            return;
        }

        $logFiles = self::findLogFiles($optionPathLogs, (new \DateTime())->setTime(0, 0, 0));

        if (extension_loaded('zlib')) {
            self::createArchiveZlib($optionPathArchive, $logFiles);
        } elseif (extension_loaded('zip')) {
            self::createArchiveZip($optionPathArchive, $logFiles);
        } elseif (extension_loaded('bz2')) {
            self::createArchiveBzip2($optionPathArchive, $logFiles);
        }

        self::removeLogs($optionPathLogs, (new \DateTime())->setTime(0, 0, 0));

        $lifeDays = new \DateTime(sprintf('-%s days', $optionLifeDay));
        self::removeArchive($optionPathArchive, $lifeDays);
    }

    public static function findLogFiles($path, \DateTime $date): array
    {
        $logFiles = [];

        /** @var $item \SplFileInfo */
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::SELF_FIRST) as $item) {

            if ($item->isFile()
                && $item->isReadable()
                && $item->getFilename() === self::$logFileName
                && $date->getTimestamp() >= $item->getMTime()
            ) {
                $logFiles[] = $item;
            }
        }

        return $logFiles;
    }

    /**
     * remove old file logs
     * @param $path
     * @param \DateTime $date
     * @return void
     */
    public static function removeLogs($path, \DateTime $date): void
    {
        /** @var $item \SplFileInfo */
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::CHILD_FIRST) as $item
        ) {
            if ($item->isFile() && $date->getTimestamp() >= $item->getMTime()) {
                unlink($item->getPathname());
            } elseif ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            }
        }
    }

    /**
     * remove old archive
     *
     * @param $pathToArchive
     * @param \DateTime $lifeDays
     * @return void
     */
    public static function removeArchive($pathToArchive, \DateTime $lifeDays): void
    {
        if (!is_dir($pathToArchive)) {
            return;
        }

        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pathToArchive, \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS),
            \RecursiveIteratorIterator::CHILD_FIRST) as $item
        ) {
            $matches = null;

            if ($item->isFile() && preg_match("/(\d{4})-(\d{2})-(\d{2})/ms".BX_UTF_PCRE_MODIFIER, $item->getFilename(), $matches)) {
                $archiveCreateTime = \DateTime::createFromFormat('Y-m-d', $matches[0]);

                if ($archiveCreateTime->getTimestamp() < $lifeDays->getTimestamp()) {
                    unlink($item->getPathname());
                }
            }
        }
    }

    /**
     * Adds a file to a GZ archive
     * @param $pathToSave
     * @param $arFiles
     * @return void
     */
    public static function createArchiveZlib($pathToSave, $arFiles): void
    {
        if (!empty($arFiles)) {
            /** @var $item \SplFileInfo */
            foreach ($arFiles as $item) {
                $dateTime = (new \Datetime())->setTimestamp($item->getMTime());

                $archiveName = $pathToSave . DIRECTORY_SEPARATOR . $dateTime->format('Y-m-d') . '_' . $item->getFilename() . '.gz';

                if ($resource = gzopen($archiveName, 'w')) {

                    if ($resourceLogFile = fopen($item->getRealPath(),'rb')) {
                        while(!feof($resourceLogFile)) {
                            gzwrite($resource, fread($resourceLogFile,1024*512));
                        }

                        fclose($resourceLogFile);
                    }

                    gzclose($resource);
                }
            }
        }
    }

    /**
     * Adds a file to a ZIP archive
     * @param $pathToSave
     * @param $arFiles
     * @return void
     */
    public static function createArchiveZip($pathToSave, $arFiles): void
    {
        if (!empty($arFiles)) {
            /** @var $item \SplFileInfo */
            foreach ($arFiles as $item) {
                $zip = new \ZipArchive();
                $dateTime = (new \Datetime())->setTimestamp($item->getMTime());

                $archiveName = $pathToSave . DIRECTORY_SEPARATOR . $dateTime->format('Y-m-d') . '_' . $item->getFilename() . '.zip';

                if ($zip->open($archiveName, \ZipArchive::CREATE) === true) {
                    $zip->addFile($item->getRealPath(), $dateTime->format('Y-m-d') . '_' . $item->getFilename());
                    $zip->close();
                }
            }
        }
    }

    /**
     * Adds a file to a BZ2 archive
     * @param $pathToSave
     * @param $arFiles
     * @return void
     */
    public static function createArchiveBzip2($pathToSave, $arFiles): void
    {
        if (!empty($arFiles)) {
            /** @var $item \SplFileInfo */
            foreach ($arFiles as $item) {
                $dateTime = (new \Datetime())->setTimestamp($item->getMTime());

                $archiveName = $pathToSave . DIRECTORY_SEPARATOR . $dateTime->format('Y-m-d') . '_' . $item->getFilename() . '.bz2';

                if ($resource = bzopen($archiveName, 'w')) {
                    if ($resourceLogFile = fopen($item->getRealPath(),'rb')) {
                        while(!feof($resourceLogFile)) {
                            bzwrite($resource, fread($resourceLogFile,1024*512));
                        }

                        fclose($resourceLogFile);
                    }

                    bzclose($resource);
                }
            }
        }
    }
}
