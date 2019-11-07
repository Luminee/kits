<?php

use Symfony\Component\Console\Output\ConsoleOutput;

trait Handler
{
    /**
     * @var Output
     */
    protected $output;

    protected $query = [];

    protected $slow = [];

    protected $fail = [];

    public function init()
    {
        if (is_null($this->output)) {
            $output = new ConsoleOutput();
            $this->output = new Output($output);
        }
        return $this;
    }

    /**
     * @return Listener
     */
    private function listener()
    {
        return app()->make('Tests\Query\Listener');
    }

    protected function getPonds()
    {
        $ponds = $this->listener()->getPonds();
        $this->query = $ponds['query'];
        $this->slow = $ponds['slow'];
        $this->fail = $ponds['fail'];
    }

    public function queryCorrect()
    {
        $this->getPonds();
        return empty($this->fail);
    }

    public function showQueries()
    {
        $this->init()->getPonds();
        foreach ($this->query as $item) {
            $k = $item['key'];
            $this->output->mixed('Query:', 'p1nk')->mixed($item['query']);
            if (isset($this->slow[$k]))
                $this->output->mixed($this->slow[$k], 'comment');
            $this->output->mixedPrint();
            if (isset($this->fail[$k])) {
                $this->output->newTable(['Section', 'Value', 'Level']);
                foreach ($this->fail[$k] as $v) {
                    $this->output->addRow([$v['section'], isset($v['value']) ? $v['value'] : '', $this->levelStyle($v['level'])]);
                }
                $this->output->render();
            }
            $this->output->line('>');
        }
    }

    protected function levelStyle($level)
    {
        $styles = [5 => 'warning', 4 => 'comment', 3 => 'p1nk', 2 => null, 1 => 'info'];
        $style = $styles[$level];
        return $style ? "<$style>$level</$style>" : $level;
    }
}