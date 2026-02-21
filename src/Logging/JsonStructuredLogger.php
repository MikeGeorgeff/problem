<?php

namespace Georgeff\Problem\Logging;

use Stringable;
use RuntimeException;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Georgeff\Problem\Contract\ContextEnricher;
use Georgeff\Problem\Contract\StructuredLogger;
use Georgeff\Problem\Exception\DomainException;

final class JsonStructuredLogger implements StructuredLogger
{
    private const array LOG_LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    private const array SEVERITY_MAP = [
        'emergency' => 'critical',
        'alert'     => 'critical',
        'critical'  => 'critical',
        'error'     => 'error',
        'warning'   => 'warning',
        'notice'    => 'info',
        'info'      => 'info',
        'debug'     => 'debug',
    ];

    /**
     * @var ContextEnricher[]
     */
    private array $enrichers = [];

    /**
     * @var resource
     */
    private $output;

    /**
     * Minimum log level
     */
    private string $logLevel;

    public function __construct(mixed $output, string $logLevel = 'debug', ContextEnricher ...$enrichers)
    {
        if (!is_resource($output)) {
            throw new InvalidArgumentException(sprintf(
                'Resource parameter must be a valid resource [%s] given',
                gettype($output)
            ));
        }

        $logLevel = strtolower($logLevel);

        if (!in_array($logLevel, self::LOG_LEVELS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Minimum log level [%s] is not valid. Valid levels: %s',
                $logLevel,
                implode(', ', self::LOG_LEVELS)
            ));
        }

        $this->output    = $output;
        $this->logLevel  = $logLevel;
        $this->enrichers = $enrichers;
    }

    public function logException(DomainException $exception): void
    {
        $this->log(
            $exception->severity,
            $exception->getMessage(),
            $exception->structuredData
        );
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(__FUNCTION__, $message, $context);
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (!is_string($level) && !($level instanceof Stringable)) {
            throw new InvalidArgumentException('Log level must be a string');
        }

        $level = strtolower((string) $level);

        if (!in_array($level, self::LOG_LEVELS, true)) {
            throw new InvalidArgumentException(sprintf(
                'Log level [%s] is not valid. Valid levels: %s',
                $level,
                implode(', ', self::LOG_LEVELS)
            ));
        }

        $this->writeLog($level, $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    private function writeLog(string $level, string|Stringable $message, array $context = []): void
    {
        if (!$this->shouldLog($level)) {
            return;
        }

        $enrichedContext = $context;

        foreach ($this->enrichers as $enricher) {
            $enrichedContext = $enricher->enrich($enrichedContext);
        }

        $logEntry = [
            'timestamp' => new DateTimeImmutable()->format(DateTimeInterface::RFC3339_EXTENDED),
            'level'     => $level,
            'severity'  => self::SEVERITY_MAP[$level] ?? 'info',
            'message'   => (string) $message,
            'context'   => $enrichedContext,
        ];

        $json = json_encode($logEntry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (false === $json) {
            throw new RuntimeException('Failed to json_encode log entry ' . json_last_error_msg());
        }

        fwrite($this->output, $json . PHP_EOL);
    }

    private function shouldLog(string $level): bool
    {
        $minIndex = array_search($this->logLevel, self::LOG_LEVELS);
        $currentIndex = array_search($level, self::LOG_LEVELS);

        return $currentIndex >= $minIndex;
    }
}
