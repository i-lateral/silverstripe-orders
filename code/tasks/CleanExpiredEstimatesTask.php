<?php

class CleanExpiredEstimatesTask extends BuildTask {
 
    protected $title = 'Clean expired estimates';
 
    protected $description = 'Clean all estimates that are past their expiration date and have no users assifgned';
 
    protected $enabled = true;

    /**
     * Undocumented variable
     *
     * @var boolean
     */
    protected $silent = false;

    /**
     * should estimates made by registered users be deleted
     *
     * @var boolean
     * @config
     */
    private static $remove_customer_estimates = false;

    /**
     * Undocumented function
     *
     * @return void
     */
    public function getSilent()
    {
        return $this->silent;
    }

    /**
     * set the silent parameter
     *
     * @param boolean $set
     * @return FetchRSSTask
     */
    public function setSilent($set)
    {
        $this->silent = $set;
        return $this;
    }

    function run($request) {
        $now = new DateTime(Ss_DateTime::now());
        $seconds = Estimate::config()->default_end;
        $past = $now->modify("-{$seconds} seconds");
        $estimates = Estimate::get()->filter([
            'Cart' => true,
            "Date:LessThan" => $past->format('Y-m-d H:i:s')
        ]);
        
        $i = 0;
        $c = $estimates->count();
        foreach ($estimates as $estimate) {
            if (!$estimate->Payments()->exists()) {
                if ($estimate->Company == 'earlypaid') {
                    Debug::show('NOT THIS ONE!');
                }
                if (!$estimate->CustomerID || $this->config()->remove_customer_estimates) {
                    $estimate->delete();
                    $i++;
                    $this->log('removing '.$i.'/'.$c, true);
                }
            }
        }

        $this->log('removed '.$i.' expired estimates.');
    }

    /**
     * Log a message to the terminal/browser
     * 
     * @param string $message   Message to log
     * @param bool   $linestart Set cursor to start (instead of return)
     * 
     * @return null
     */
    protected function log($message, $linestart = false)
    {
        if ($this->getSilent()) {
            return;
        }

        if (Director::is_cli()) {
            $end = ($linestart) ? "\r" : "\n";
            print_r($message . $end);
        } else {
            print_r($message . "<br/>");
        }
    }
}