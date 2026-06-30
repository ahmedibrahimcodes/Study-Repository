# Queue

## When To Use Queue

- When you have a task that needs to run in the background such as ProcessPayment, GenerateInvoice etc... to reduce stress on server and requests would be quick and delegate the task to background

## Set Queue Connection

- you can review config/queue.php
- in .env .. set QUEUE_CONNECTION=database or QUEUE_CONNECTION=redis
- then in .env set DB_QUEUE_CONNECTION=mysql or DB_QUEUE_CONNECTION=pgsql etc..

## Migrate Queue Structure in database

- `php artisan queue:table`
- `php artisan queue:failed-table`
- `php artisan migrate`

## Create Job

`php artisan make:job SendWelcomeEmail`

```php

// app/Jobs/SendWelcomeEmail.php
class SendWelcomeEmail implements ShouldQueue //ShouldQueue Interface “Don’t run this job immediately — push it to the queue.”
{
    //This trait adds dispatch static method, SendWelcomeEmail::dispatch() instead of dispatch(new SendWelcomeEmail($user));
    use Dispatchable,

    // This trait adds helper methods for when your job is being processed by the queue worker
    // $this->release(30); // Requeue the job to try again after 30 seconds
    // $this->delete();    // Manually delete the job
    // $this->fail($exception); // Mark it as failed
    // Useful when You want to retry later if an API call failed
    // Useful when you want to fail it maybe at specific point
    InteractsWithQueue,

    // this trait tells Laravel: “Store only the model’s ID when serializing, and automatically re-fetch it from the database when the job runs.”
    // if you dont use the trait laravel would try to serialize all eloquent model properties and relationships .. takes too much data
    // to use this, parameter passed in the constructor must be a property of the class
    // so if public function __construct(User $user) {} .. won't work
    //serializing is about to serialize class and its properties, without this trait it will serialize all properties data with all its relations which consume queue space
    SerializesModels,

    // This trait is just the three traits above
    Queueable,

    public function __construct(public User $user) {}

    public function handle()
    {
        Mail::to($this->user)->send(new WelcomeMail($this->user));
    }
}

```

## What can be put into queue?

- Jobs .. the most common thing you push to a queue.
- Listeners (that ShouldQueue) .. A listener normally runs when an event is fired, but if it implements ShouldQueue, then it’s queued instead of running immediately.
- Mailables.. call ->queue() instead of ->send() `Mail::to($user)->queue(new WelcomeMail($user));`
- Notifications (that ShouldQueue)

## Queued Job workflow

- when job is added to queue by TestJob::dispatch()

  ```json
  {
    "id": 101,
    "queue": "default",
    "payload": "{\"uuid\":\"9d1b54a2...\",\"displayName\":\"App\\\\Jobs\\\\TestJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{...}}",
    "attempts": 0,
    "reserved_at": null,
    "available_at": 1714742400,
    "created_at": 1714742400
  }
  ```

- when job is picked from queue .. it will straight away mark it as reserved_at and increment attempts in db
  - so inside job for first trial if you called $this->attempts() it will give you 1

    ```json
    {
      "id": 101,
      "queue": "default",
      "payload": "{\"uuid\":\"9d1b54a2...\",\"displayName\":\"App\\\\Jobs\\\\TestJob\",\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{...}}",
      "attempts": 1,
      "reserved_at": 1714742500,
      "available_at": 1714742400,
      "created_at": 1714742400
    }
    ```

- if job succeeded, it will delete the record from jobs database (Happy Scenario)

- if job failed with exception, it will move the record into failed_jobs table because by default tries = 1


## tries
- if job failed with exception but job had more trials declared with it. after it fails it will set reserved_at as null and keep attempts incremented
  till attempts >= trials then it will move it to failed_jobs

  ```php
  class TestJob implements ShouldQueue
  {
      use Queueable;

      public $tries = 5;

      public function tries(): int
      {
          return 5;
      }
  }
  ```

## backoff

- if backoff value, means next try should delay for some time, it mark reserved_at as null and update available_at to respect backoff value

  ```php
  class TestJob implements ShouldQueue
  {
      use Queueable;

      public $tries = 5;

      public $backoff = 30; //notice its backoff not backOff

      public $backoff = [10, 30, 60]; //or can be .. wait 10 then 30 then 60

      public function backoff(): int
      {
          return 30;
      }
  }
  ```

## delay

- if delay property is set means delay before first execution

```php

class ProcessOrder implements ShouldQueue
{
  public $delay = 60; //wait 60 seconds before first trial

  public function withDelay(OrderShipped $event): int
  {
    return rand(0,60);
  }
}

```

## timeout

-  If a job is processing for longer than the number of seconds specified by the timeout value, the worker processing the job will exit with an error.
  - By default, the timeout value is 60 seconds
  - IO blocking processes such as sockets or outgoing HTTP connections may not respect your specified timeout. Therefore, when using these features, you should always attempt to specify a timeout using their APIs as well. For example, when using Guzzle, you should always specify a connection and request timeout value.
  - By default, when a job times out, it consumes one attempt and is released back to the queue (if retries are allowed). However, if you configure the job to fail on timeout, it will not be retried, regardless of the value set for tries.
  - The $timeout property on a Laravel job doesn't work on Windows. only works on Linux (and macOS/Unix-based systems). 
    - Laravel relies on a built-in PHP extension called PCNTL (Process Control) to handle job timeouts.
    - When a job starts, Laravel uses PCNTL to configure a system alarm. If the alarm goes off before the job finishes, the operating system sends a signal (SIGALRM) to kill that specific background child process.
    - The catch? The PCNTL extension is not available on Windows.

```php
class ProcessOrder implements ShouldQueue
{
  public $timeout = 120;
}
```

note:
- queue:work for laravel vapor is called VaporWorker.php and if timeout it will throw exception VaporJobTimedOutException


## release

- by calling $this->release(30) it will action straight away setting reserved_at with null and update available_at = now + 30 seconds
  - its recommended to do it at the end of the exeuction because it doesnt make sense to do it on middle of execution
  - if next run find attempts incremented so it will move it to failed_jobs straight away without execution again
  - if you called $this->release(30) and return with exception, it doesnt matter it will do same as above
      
  ```php
      public function handle(): void
      {
          try {
              $this->doSomeOperation();
          } catch (\CustomException $ex) {
              //in case this custom exception happened, then release and return
              //so that it will be retried multiple times till attemps >= tries
              Log::debug('Custom Exception is throwing from class and we will try again in few ' );
              $this->release(30);
              return;
          } catch (\Throwable $th) {
              //in case any other error, then fail the job into failed_jobs
              $this->fail($th);
          }
      }
  ```

- so what is the difference between release and throwing exception as usual?
  - on release, there won't be logging as execution is ending safely and you have to log error yourself.
  - on release, you can delay next execution however you like but in exception you will have to rely on backoff property value for this


## retry_after in config

- if something fatal caused the queue:work process to exist such as below, reserved_at will have a value
  and queue worker will use the value of retry_after in config/queue.php that after this time of reserving a job. worker will retry it
  and notice because of this retry after functionality, its not desired that you have long running jobs
  - server is down
  - one of the jobs called `exit`
  - one of the commands caused memory limit exception
  - queue worker has lost connection to database
  - Notice: this won't cause fatal exist of queue:work (typical exceptions and errors in jobs 1/0 or file syntax error )


- in sqs, it doesn't have this retry_after logic.. but it has something similar called Visibility Timeout
  - The Visibility Timeout is the amount of time that Amazon SQS hides a message from other consumers after one consumer receives it. 
  - it serve the same purpose as retry_after
  - Visibility Timeout is not set on laravel config but its set on sqs aws itself
  - in laravel vapor, you can set visibility timeout + lambda execution limit in vapor.yml by setting queue-timeout: 300



## Configure Job Processing

- By default job will be tried only once and if failed it will be moved to failed jobs with no delay

- you can configure job for no of trial and delay before first procesing and time between trials

```php

class ProcessOrder implements ShouldQueue
{
    public $tries = 5; //max attempts .. after 5 tries it will be sent to failed jobs
    public $backoff = 10; //wait 10 seconds between trials
    public $backoff = [10, 30, 60]; //wait 10 then 30 then 60
    public $delay = 60; //wait 60 seconds before first trial
}

```

- you can override whatever job says by `php artisan queue:work --tries=5 --backoff=10`

- you can also declare only delay when you are dispatching `SendEmailJob::dispatch($user)->delay(now()->addSeconds(30));`

### Run queue

`php artisan queue:work` .. run queue

Typially if job throw exception that wouldnt exit queue, but queue worker can crash though for multiple reasons .. to be safe its recommended to use supervisor to keep worker running always

### Process

`php artisan queue:work`

- Laravel continuously polls the jobs table (or Redis list) for pending jobs. When it finds one, it reserves it then no other workers can use it `UPDATE jobs SET reserved_at = NOW(), attempts = attempts + 1 WHERE id = 1`
- if job succeeded `DELETE FROM jobs WHERE id = 1`
- If job fails with exception .. Has it exceeded max attempts .. if yesn it delete it and insert it into failed jobs
- if job fails with exception.. it didnt exceed max attempts .. then release `update job reserved_at = null and available_at = now + backoff`

Tip: if you dont want job to be moved to failed_jobs at all, you can on the job to try and catch and instead of throwing exception, you will catch the exception but instead of returning normally which means success job.. you will instead call $this->release($backoff); which will trigger `update job reserved_at = null and available_at = now + backoff`

### Failed Jobs

Laravel does not touch Failed Jobs again unless you manually retry them. by moving them from failed jobs back to normal table

- `php artisan queue:retry {id}`
- `php artisan queue:retry all`

If you want failed jobs to be automatically retried

- Option 1 — Use release() inside the job then job doesnt move to failed_jobs ever

```php

public function handle()
{
    try {
        $this->processSomething();
    } catch (\Throwable $e) {
        // Retry again after 30 seconds
        $this->release(30);
    }
}

```

- Option 2 — Schedule a command to retry failed jobs

```php

// in your app/Console/Kernel.php scheduler:

protected function schedule(Schedule $schedule)
{
    $schedule->command('queue:retry all')->everyFiveMinutes();
}

```

## pass eloquent into listener

listener is the object which gets serialized but it contains reference to event object.. and event itself which can contain the eloquent data .. we have used SerializeModels then event object to serialize only the id of the model and not all the data to reduce space usage

```php

OrderCreated::dispatch(Order::find(1));


// app/Events/OrderCreated.php
namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Order $order)
    {
    }
}


namespace App\Listeners;

use App\Events\OrderCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendOrderEmail implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(OrderCreated $event)
    {
        // You can safely access the model here
        $order = $event->order;

        \Log::info('Sending order email for order #' . $order->id);

        // Example: send mail
        // Mail::to($order->user->email)->send(new OrderConfirmationMail($order));
    }
}

```

## Priority Queue

Problem here.. you may have like 1000 email in the queue, while you have other background tasks which is more important.. but because its all on queue you have to wait for 1000 email to be sent for your other background task to be processed..

Laravel doesn’t assign a priority number directly to jobs.

Dispatching Jobs to Different Queues

```php
SendEmailJob::dispatch($user)->onQueue('high');

//or inside job

class SendEmailJob implements ShouldQueue
{
    public $queue = 'high';
}
```

Process Queues

- `php artisan queue:work --queue=high,low` .. Worker will always check high first. If high is empty, it will process low.

- you can work them separately
  - `php artisan queue:work --queue=high --sleep=0 --tries=3`
  - `php artisan queue:work --queue=low --sleep=5 --tries=1`

👉 So, Laravel priority queues = multiple queues + ordered workers.
There’s no “priority=10 vs priority=5” built-in — you achieve it by structuring queues.

Tip: --sleep .. refer to if queue worker find queue empty.. then sleep for sometime before try again.. it helps a little then you dont hammer the queue if queue is empty

## can i use redis for queue?

- `QUEUE_CONNECTION=redis`

- config/queue.php

```php

'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90,
        'block_for' => null,
    ],
]

```

This means Laravel will use the default Redis connection (from config/database.php).

- config/database.php

- you use the typical laravel jobs, event listeners etc... nothing changed

- behind the scene, laravel add a key in redis called laravel_database_queues:default which is type list and every entry will be json encoded data and this is queue where worker will read from to process

```json
{
  "uuid": "1531c3b3-87b8-4921-ac19-9fdc41aa3e93",
  "displayName": "App\\\\Listeners\\\\SendWelcomeEmail",
  "job": "Illuminate\\\\Queue\\\\CallQueuedHandler@call",
  "maxTries": 5,
  "maxExceptions": null,
  "failOnTimeout": false,
  "backoff": null,
  "timeout": null,
  "retryUntil": null,
  "data": {
    // job serialized
  },
  "id": "mZQwJRmakeHHWnuxzPrbJBcWcv7anZl2",
  "attempts": 0
}
```

- if you try to add a job with delay, it will be added to a sorted set called laravel_database_queues:default:delayed .. then it can use the score feature to quickly lookup what are the jobs that are ready now and can be processed. and if it finds jobs it will move them to the default list queue

- when processing it moves the processor into laravel_database_queues:default:reserved and its type of sorted set .. laravel uses reserved_at + timeout as the score .. then it can quickly query redis for expired or to retry jobs with O(1)

- when job fails, it doesnt move to failed jobs in redis.. instead it send them to database .. its separately configured in config/queue.php on failed section and this because Laravel’s failed job mechanism is meant for persistent storage and easy inspection. and laravel doesnt provide failed jobs in redis out of the box. only databases

## Laravel Horizon

horizon has two jobs

- Supervisor: Keeps track of your defined worker configurations (config/horizon.php). Starts and restarts workers automatically.
- Workers : Horizon spawns real queue:work processes behind the scenes — same code, just managed automatically.
- UI to show you what is the progress of jobs

`composer require laravel/horizon:"^5.0"`
`php artisan horizon:install`
`php artisan horizon`
`https://localhost/horizon`

## Reserving Message

when a message being read by queue it reserve it by filling reserved_at then no other workers can use it

after worker done with the message, he can either delete it on success or release it by nulling reserved_at

Q: if job is reserved and the worker crashed before releasing it, how long worker will take before retrying it?

retry_after is defined in your config/queue.php under redis/database

```php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'default',
        'queue' => env('REDIS_QUEUE', 'default'),
        'retry_after' => 90, // seconds
        'block_for' => null,
    ],
],
```

Q: why for reserving, reserving is done by flagging reserved_at instead of select for update?

- to be generic, because select for update is only for databases and even it differ from database to another

- also select for update is blocking, which may affect performance

## Race Conditions

if two processes are trying to get messages to process what if both of them select in same time, surely both they will get same job, so it will be handled twice?

laravel handle this race condition

### Get Jobs

Worker A runs: `SELECT * FROM jobs WHERE reserved_at IS NULL LIMIT 1;`
Worker B runs: `SELECT * FROM jobs WHERE reserved_at IS NULL LIMIT 1;`

Both get job #1

### Reserving

Worker A try to reserve `UPDATE jobs SET reserved_at = NOW(), attempts = attempts + 1 WHERE id = 1 AND (reserved_at IS NULL OR reserved_at < NOW() - INTERVAL 60 SECOND)`

Worker B try to reserve `UPDATE jobs SET reserved_at = NOW(), attempts = attempts + 1 WHERE id = 1 AND (reserved_at IS NULL OR reserved_at < NOW() - INTERVAL 60 SECOND)`

Worker A success and will get back affected_rows=1, so it will process. worker B will get back affected_rows=0, so it will try to go and find another job

Tip: Databases Update are atomic, once you try to update it locks till it finish. so these two queries are guranteed they won't race as of database locking and redis is atomic as well as its single threaded anyway
