<?php

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class Output
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Table
     */
    protected $table;

    protected $strings = [];

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        $this->output->getFormatter()->setStyle('warning', new OutputFormatterStyle('red'));
        $this->output->getFormatter()->setStyle('p1nk', new OutputFormatterStyle('magenta'));
    }

    /**
     * Write a string as standard output.
     *
     * @param string $string
     * @param string $style
     * @return void
     */
    public function line($string, $style = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled);
    }

    /**
     * @param $string
     * @param $style
     * @return $this
     */
    public function mixed($string, $style = null)
    {
        $this->strings[] = $style ? "<$style>$string</$style>" : $string;
        return $this;
    }

    public function mixedPrint($separator = ' ')
    {
        $this->output->writeln(implode($separator, $this->strings));
        $this->strings = [];
    }

    /**
     * Write a string as information output.
     *
     * @param string $string
     * @return void
     */
    public function info($string)
    {
        $this->line($string, 'info');
    }

    /**
     * Write a string as comment output.
     *
     * @param string $string
     * @return void
     */
    public function comment($string)
    {
        $this->line($string, 'comment');
    }

    /**
     * Write a string as question output.
     *
     * @param string $string
     * @return void
     */
    public function question($string)
    {
        $this->line($string, 'question');
    }

    /**
     * Write a string as error output.
     *
     * @param string $string
     * @return void
     */
    public function error($string)
    {
        $this->line($string, 'error');
    }

    /**
     * Write a string as warning output.
     *
     * @param string $string
     * @return void
     */
    public function warning($string)
    {
        $this->line($string, 'warning');
    }

    /**
     * Write a string as pink output.
     *
     * @param string $string
     * @return void
     */
    public function pink($string)
    {
        $this->line($string, 'p1nk');
    }



    /**
     * @param array $headers
     * @return $this
     */
    public function newTable($headers)
    {
        $this->table = new Table($this->output);
        $this->table->setHeaders($headers);
        return $this;
    }

    /**
     * @param array $rows
     * @return $this
     */
    public function setRows($rows)
    {
        $this->table->setRows($rows);
        return $this;
    }

    /**
     * @param array $row
     * @return $this
     */
    public function addRow($row)
    {
        $this->table->addRow($row);
        return $this;
    }

    /**
     * @param array $array
     * @return $this
     */
    public function setColumnWidths($array)
    {
        $this->table->setColumnWidths($array);
        return $this;
    }

    /**
     * @return void
     */
    public function render()
    {
        $this->table->render();
    }

}