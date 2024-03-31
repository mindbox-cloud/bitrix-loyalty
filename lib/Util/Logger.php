<?php

namespace Mindbox\Loyalty\Util;

use Psr\Log\LogLevel;

/**
 * Класс, реализующий запись логирования данных в файл.
 * Логи записываются в директорию /logs/ в корне проекта.
 * Доступные уровни логирования PSR-3 Psr\Log\LogLevel.
 *
 *
 * @see https://www.php-fig.org/psr/psr-3/
 */
class Logger implements \Psr\Log\LoggerInterface
{
    /**
     * Шаблон лога
     */
    const LOG_TEMPLATE = '[{date}][:{level}] {message} {context}';

    /**
     * @var array Массив каналов записи
     */
    private static $channels = [];

    /**
     * @var string Конечный путь к директории логов. Определяется в методе initLogDir()
     */
    private $logDirectory = '';

    /**
     * @var string Канал логирования
     */
    private $loggerChannel = '';

    /**
     * @var bool
     */
    private $enableLoggingFlag = false;

    /**
     * @param $channel
     */
    private function __construct($channel)
    {
        $this->setLoggerChannel($channel);
        $this->enableLoggingFlag = true;
    }

    /**
     * Статический метод для определения канала логирования.
     * Проверяет существования канала в хранилище, если канала нет,
     * то создает соответствующий объект в хранилище
     *
     * @param string $channel
     * @return Logger|mixed
     */
    public static function channel(string $channel = 'mindbox-dev')
    {
        if (!array_key_exists($channel, self::$channels)) {
            self::$channels[$channel] = new self($channel);
        }

        return self::$channels[$channel];
    }

    /**
     * @param string $value
     * @return void
     */
    private function setLoggerChannel(string $value)
    {
        $this->loggerChannel = $value;
    }

    /**
     * @return string
     */
    private function getLoggerChannel(): string
    {
        return $this->loggerChannel;
    }

    /**
     * Получение директории для текущего дня
     *
     * @return string
     */
    private function getLogsDirectory(): string
    {
        $path = realpath(__DIR__ . '/../../logs');
        $day = date('Y-m-d');

        return $path . '/' . $day;
    }

    /**
     * Инициализирует директорию для записи логов
     * Метод создает директорию для текущего дня формата YYYY-MM-DD
     *
     * @return string
     */
    private function initLogDir(): string
    {
        if (empty($this->logDirectory)) {

            $logsDirectory = $this->getLogsDirectory();

            if (file_exists($logsDirectory)) {
                $this->logDirectory = $logsDirectory;
            } else {

                if (mkdir($logsDirectory, 0775, true)) {
                    @chmod($logsDirectory, 0775);
                    $this->logDirectory = $logsDirectory;
                }
            }
        }

        return $this->logDirectory;
    }

    /**
     * Принудительно разрешает логирование несмотря на установленное значение в настройках
     * @return $this
     */
    public function enableLogging(): Logger
    {
        $this->enableLoggingFlag = true;
        return $this;
    }

    /**
     * Формирует лог-запись
     * @param string $level Уровень логирования
     * @param string $message Сообщение лога
     * @param array $context Массив дополнительных параметров логирования
     * @return string
     */
    private function prepareLogRecord(string $level, string $message, array $context): string
    {
        $result = str_replace(
            [
                '{date}',
                '{level}',
                '{message}',
                '{context}'
            ],
            [
                date('Y-m-d H:i:s'),
                $level,
                $message,
                print_r($context, true)
            ],
            self::LOG_TEMPLATE
        );

        return $result . PHP_EOL;
    }

    /**
     * @param string $directory Директория, в которую будет записан лог
     * @return string
     */
    private function getLogFile(string $directory): string
    {
        return $directory . '/' . $this->getLoggerChannel() . '.log';
    }

    /**
     * @param string $level Уровень логирования
     * @param string $message Сообщение лога
     * @param array $context Массив дополнительных параметров логирования
     * @return void
     */
    public function log($level, \Stringable|string  $message, array $context = array()): void
    {
        if ($this->enableLoggingFlag) {
            $directory = $this->initLogDir();

            if (!empty($directory)) {
                $logData = $this->prepareLogRecord($level, $message, $context);

                $file = fopen($this->getLogFile($directory), 'a');
                fwrite($file, $logData);
                fclose($file);
            }
        }
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function emergency(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function alert(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function critical(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function error(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function warning(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function notice(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function debug(\Stringable|string $message, array $context = array()): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * @param $message
     * @param array $context
     * @return void
     */
    public function info(\Stringable|string $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * @return void
     */
    protected function __clone()
    {
    }
}
