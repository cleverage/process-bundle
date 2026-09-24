Performances Monitoring
=======================

For heavy work there are multiple solutions to improve speed (see [parallelization](../04-advanced_workflow.md#parallelization))
and memory consumption (see [memory usage analysis](memory_usage_graph.md)).

While developing custom tasks you might want to see how well your PHP code behaves, and one solution is to use [Blackfire](https://blackfire.io)
to analyse call graphs with timings & memory analysis.

## Setup

If you're using the official PHP docker image, you can add the Blackfire probe to your container ([Official documentation](https://blackfire.io/docs/integrations/docker/php-docker)).

Then, with your [own credentials](https://blackfire.io/my/settings/credentials), execute inside your container
```shell script
blackfire config --client-id=$CLIENT_ID --client-token=$CLIENT_TOKEN
```

You can also pass those ids during the run call.

## Usage

Just prefix `blackfire run` while executing your PHP commands inside your container, and Blackfire will provide you an URL
with the resulting call graph. Be careful to cleanup the cache before calling Blackfire, to avoid any noise.

```shell script
$ ./bin/console c:c --env=test

 // Clearing the cache for the test environment with debug true                                                         

                                                                                                                        
 [OK] Cache for the "test" environment (debug=true) was successfully cleared.                                           
                                                                                                                        

$ blackfire --client-id=xxx-xxx-xxx-xxx-xxx --client-token=xxxxxxxxxxx run php bin/console --env=test cleverage:process:execute test.simple_process
Starting process 'test.simple_process'...
Process 'test.simple_process' executed successfully

Blackfire Run completed
Graph URL https://blackfire.io/profiles/xxx-xxx-xxx-xxx-xxx/graph
No tests! Create some now https://blackfire.io/docs/cookbooks/tests
No recommendations

Wall Time     102ms
I/O Wait        n/a
CPU Time        n/a
Memory       5.35MB
Network         n/a     n/a     n/a
SQL             n/a     n/a
```

## Built-in timing information

Before profiling, the process logs already give some timing information:
- on success, the process manager logs `Process <process_code> succeed` (level `info`, channel `cleverage_process`)
  with the total `duration` of the process, in seconds, in the record context
- at `debug` level, the same channel logs each task execution (`Processing task <task_code>`, `Proceeding task ...`,
  `Flushing task ...`): with a formatter displaying milliseconds, it shows where the time is spent. With the Monolog
  console handler of the Symfony recipe, `-vvv` displays debug records in the console:

```bash
$ ./bin/console cleverage:process:execute app.file_import -vvv
```

For a precise analysis of a heavy task (e.g. a CSV reader or writer, or a custom task doing database or API calls),
profile the process with Blackfire as shown above, preferably with a production-like data set and in the `prod`
environment:

```bash
$ blackfire run php bin/console --env=prod cleverage:process:execute app.file_import
```
