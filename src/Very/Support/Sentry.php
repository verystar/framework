<?php
/**
 * Created by PhpStorm.
 * User: fifsky
 * Date: 2015/11/17
 * Time: 14:03
 */

namespace Very\Sentry;

use InvalidArgumentException;


class Sentry
{

    private $dsn;
    private $options;
    private $level = 'debug';

    /**
     * Create a new sentry writer instance.
     */
    public function __construct($dsn, $options = [])
    {
        if (!$dsn) {
            throw new InvalidArgumentException('sentry dsn not fund');
        }

        $this->dsn        = $dsn;
        $options['trace'] = isset($options['trace']) ? $options['trace'] : false;
        $this->options    = $options;
    }

    /**
     * 发送到sentry
     */
    public function send($options = [])
    {
        $pattern_content = '^\[(.*)\] log\.([^:]+):\s+(.+)';

        $parrern_level = implode('|', array(
            'debug',
            'info',
            'warning',
            'error',
            'fatal',
        ));

        $client = new \Raven_Client($this->dsn, $this->options);

        while (($line = fgets(STDIN)) !== false) {
            if (!preg_match("/{$pattern_content}/", $line, $match)) {
                continue;
            }


            //过滤不需要看到的日志
            //过滤报表重复插入
            if (strpos($line, 'Duplicate') !== false && strpos($line, 'SQL Error') !== false) {
                continue;
            }

            list($line, $timestamp, $level, $message) = $match;

            $level = strtolower($level);

            if ($level == $this->level) {
                continue;
            }

            $timestamp = gmdate('Y-m-d\TH:i:s\Z', strtotime($timestamp));

            preg_match("/{$parrern_level}/i", strtolower($level), $match);

            $level = isset($match[0]) ? $match[0] : 'error';

            $d_options = [
                'timestamp'   => $timestamp,
                'level'       => $level,
            ];

            if ($level == 'info') {
                $d_options['figerprint'] = [trim(substr($message, 0, strpos($message, '{')))];
            }

            $d_options = array_merge($d_options, $options);

            $client->captureMessage($message, array(), $d_options);
        }
    }
}