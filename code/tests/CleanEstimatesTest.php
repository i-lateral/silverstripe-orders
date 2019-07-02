<?php

class CleanEstimatesTest extends FunctionalTest
{
    static $fixture_file = 'orders/code/tests/CleanEstimatesTest.yml'; 

    public function testClean()
    {
        $task = Injector::inst()->create('CleanExpiredEstimatesTask');
        $task->setSilent(true);
        $request = Controller::curr()->getRequest();
    
        //first run cleaner before any of our estimate
        SS_Datetime::set_mock_now('2019-06-06 10:00:00');
        $task->run($request);
        $estimates = Estimate::get();

        $this->assertEquals(7, $estimates->count());

        //then nun cleaner after the early estimates
        SS_Datetime::set_mock_now('2019-06-06 17:00:00');
        $task->run($request);
        $estimates = Estimate::get();

        $this->assertEquals(6, $estimates->count());

        //then nun cleaner after the mid estimates
        SS_Datetime::set_mock_now('2019-06-10 17:00:00');
        $task->run($request);
        $estimates = Estimate::get();
        $this->assertEquals(5, $estimates->count());

        //then nun cleaner after the late estimates
        SS_Datetime::set_mock_now('2019-06-28 17:00:00');
        $task->run($request);
        $estimates = Estimate::get();

        $this->assertEquals(4, $estimates->count());

        // clear mock date after testing
        SS_Datetime::clear_mock_now();
    }
}