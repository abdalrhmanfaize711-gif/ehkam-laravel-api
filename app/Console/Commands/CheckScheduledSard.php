<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScheduledSardCheckerService;

class CheckScheduledSard extends Command
{
    /*
    |--------------------------------------------------------------------------
    | Command Name
    |--------------------------------------------------------------------------
    */

    protected $signature = 'sard:check-scheduled';


    /*
    |--------------------------------------------------------------------------
    | Command Description
    |--------------------------------------------------------------------------
    */

    protected $description =
        'Check scheduled sard days and stages';


    /*
    |--------------------------------------------------------------------------
    | Execute
    |--------------------------------------------------------------------------
    */

    public function handle(
        ScheduledSardCheckerService $checker
    ) {

        $this->info(
            'Starting scheduled sard check...'
        );


        /*
        |--------------------------------------------------------------------------
        | تشغيل الفحص
        |--------------------------------------------------------------------------
        */

        $result = $checker->checkAll();


        /*
        |--------------------------------------------------------------------------
        | Sard Days
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Sard Days:'
        );

        $this->info(
            'Checked: ' .
            $result['sard_days']['checked']
        );

        $this->info(
            'Completed: ' .
            $result['sard_days']['completed']
        );

        $this->info(
            'Missed: ' .
            $result['sard_days']['missed']
        );


        /*
        |--------------------------------------------------------------------------
        | Sard Stages
        |--------------------------------------------------------------------------
        */

        $this->info(
            'Sard Stages:'
        );

        $this->info(
            'Checked: ' .
            $result['sard_stages']['checked']
        );

        $this->info(
            'Completed: ' .
            $result['sard_stages']['completed']
        );

        $this->info(
            'Missed: ' .
            $result['sard_stages']['missed']
        );


        $this->info(
            'Scheduled sard check completed successfully.'
        );


        return Command::SUCCESS;
    }
}