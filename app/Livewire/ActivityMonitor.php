<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

class ActivityMonitor extends Component
{
    public ?string $header = null;

    public $activityId;

    public $eventToDispatch = 'activityFinished';

    public $isPollingActive = false;

    public bool $fullHeight = false;

    public bool $showWaiting = false;

    protected $activity;

    public static $eventDispatched = false;

    protected $listeners = ['activityMonitor' => 'newMonitorActivity'];

    public function newMonitorActivity($activityId, $eventToDispatch = 'activityFinished')
    {
        $this->activityId = $activityId;
        $this->eventToDispatch = $eventToDispatch;

        $this->hydrateActivity();

        $this->isPollingActive = true;
    }

    public function hydrateActivity()
    {
        $this->activity = Activity::find($this->activityId);
    }

    public function polling()
    {
        $this->hydrateActivity();
        $exit_code = data_get($this->activity, 'properties.exitCode');
        if ($exit_code !== null) {
            $this->isPollingActive = false;
            if ($exit_code === 0) {
                if ($this->eventToDispatch !== null) {
                    if (str($this->eventToDispatch)->startsWith('App\\Events\\')) {
                        $causer_id = data_get($this->activity, 'causer_id');
                        $user = User::find($causer_id);
                        if ($user) {
                            $teamId = $user->currentTeam()->id;
                            if (! self::$eventDispatched) {
                                $this->eventToDispatch::dispatch($teamId);
                                self::$eventDispatched = true;
                            }
                        }

                        return;
                    }
                    if (! self::$eventDispatched) {
                        $this->dispatch($this->eventToDispatch);
                        self::$eventDispatched = true;
                    }
                }
            }
        }
    }
}
