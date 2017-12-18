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
        $estimates = Estimate::get()->filter('Cart',true);
        $now = new DateTime();
        $days = Estimate::config()->default_end;
        $i = 0;
        foreach ($estimates as $estimate) {
            $remove = false;

            if (!$estimate->CustomerID) {
                $date = new DateTime($estimate->dbObject('Date')->Rfc822());
                $age = (int) $date->diff($now)->format('%d') + ($date->diff($now)->format('%m') * 30);
                if ($age > $days) {
                    $remove = true;
                }
            }

            if ($remove) {
                $estimate->delete();
                $i++;
            }
        }
        $this->log('removed '.$i.' expired estimates.');
    }

    private function log($message)
    {
        if (!$this->silent) {
            if(Director::is_cli()) {
                echo $message . "\n";
            } else {
                echo $message . "<br/>";
            }
        }
    }
}