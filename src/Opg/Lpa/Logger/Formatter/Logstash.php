<?php
namespace Opg\Lpa\Logger\Formatter;

use Zend\Log\Formatter\FormatterInterface;
use DateTime;
use Zend\Escaper\Escaper;

class Logstash implements FormatterInterface
{
    /**
     * @var Escaper instance
     */
    protected $escaper;
    
    /**
     * @var string Encoding to use in JSON
     */
    protected $encoding;
    
    /**
     * Format specifier for DateTime objects in event data (default: ISO 8601)
     *
     * @see http://php.net/manual/en/function.date.php
     * @var string
     */
    protected $dateTimeFormat = self::DEFAULT_DATETIME_FORMAT;
    
    /**
     * Class constructor
     * (the default encoding is UTF-8)
     */
    public function __construct($options = [])
    {
        if (!array_key_exists('encoding', $options)) {
            $options['encoding'] = 'UTF-8';
        }
        
        $this->setEncoding($options['encoding']);
    }
    
    /**
     * Formats data into a single line to be written by the writer.
     *
     * @param array $event event data
     * @return string formatted line to write to the log
     */
    public function format($event)
    {
        if (isset($event['timestamp']) && $event['timestamp'] instanceof DateTime) {
            $event['timestamp'] = $event['timestamp']->format($this->getDateTimeFormat());
        }
    
        $dataToInsert = $event;
    
        $escaper = $this->getEscaper();
        
        $logstashArray = [
            '@version' => 1,
            '@timestamp' =>  $event['timestamp'],
            'host' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'N/A',
        ];
        
        foreach ($dataToInsert as $key => $value) {
            if (empty($value)
                || is_scalar($value)
                || (is_object($value) && method_exists($value, '__toString'))
            ) {
                if ($key == "message") {
                    $value = $escaper->escapeHtml($value);
                } elseif ($key == "extra" && empty($value)) {
                    continue;
                }
                $logstashArray[$key] = $value;
            }
        }
    
        $json = json_encode($logstashArray);
            
        return $json;
    }

    /**
     * Get encoding
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }
    
    /**
     * Set encoding
     *
     * @param string $value
     * @return Logstash
     */
    public function setEncoding($value)
    {
        $this->encoding = (string) $value;
        return $this;
    }
    
    /**
     * Set Escaper instance
     *
     * @param  Escaper $escaper
     * @return Xml
     */
    public function setEscaper(Escaper $escaper)
    {
        $this->escaper = $escaper;
        return $this;
    }
    
    /**
     * Get Escaper instance
     *
     * Lazy-loads an instance with the current encoding if none registered.
     *
     * @return Escaper
     */
    public function getEscaper()
    {
        if (null === $this->escaper) {
            $this->setEscaper(new Escaper($this->getEncoding()));
        }
        return $this->escaper;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getDateTimeFormat()
    {
        return $this->dateTimeFormat;
    }
    
    /**
     * {@inheritDoc}
     */
    public function setDateTimeFormat($dateTimeFormat)
    {
        $this->dateTimeFormat = (string) $dateTimeFormat;
        return $this;
    }
}